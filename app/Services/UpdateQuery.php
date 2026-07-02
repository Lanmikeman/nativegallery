<?php

namespace App\Services;

class UpdateQuery
{
    public const PER_PAGE = 30;
    public const ARCHIVE_DAYS_PER_PAGE = 7;

    public static function parseParams(array $get): array
    {
        $time = isset($get['time']) ? max(1, (int) $get['time']) : null;
        $date = trim((string) ($get['date'] ?? ''));

        return [
            'time' => $time,
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '',
            'cid' => isset($get['cid']) ? (int) $get['cid'] : null,
            'aid' => isset($get['aid']) ? (int) $get['aid'] : null,
            't' => isset($get['t']) ? (int) $get['t'] : null,
            'st' => max(0, (int) ($get['st'] ?? 0)),
        ];
    }

    public static function mode(array $params): string
    {
        if ($params['date'] !== '') {
            return 'date';
        }
        if ($params['time'] !== null) {
            return 'recent';
        }
        return 'archive';
    }

    public static function buildPhotoFilter(array $params): array
    {
        $where = ['p.moderated = 1'];
        $bind = [];

        if ($params['date'] !== '') {
            $start = strtotime($params['date'] . ' 00:00:00');
            $end = strtotime($params['date'] . ' 23:59:59');
            if ($start !== false && $end !== false) {
                $where[] = 'p.timeupload >= :date_from';
                $where[] = 'p.timeupload <= :date_to';
                $bind[':date_from'] = $start;
                $bind[':date_to'] = $end;
            }
        } elseif ($params['time'] !== null) {
            $where[] = 'p.timeupload >= :since';
            $bind[':since'] = time() - ($params['time'] * 3600);
        }

        if ($params['aid'] !== null && $params['aid'] > 0) {
            $where[] = 'p.user_id = :author_id';
            $bind[':author_id'] = $params['aid'];
        }

        if ($params['t'] !== null) {
            if ($params['t'] === 0) {
                $where[] = '(p.entitydata_id = 0 OR p.entitydata_id IS NULL)';
            } else {
                $where[] = 'EXISTS (SELECT 1 FROM entities_data ed WHERE ed.id = p.entitydata_id AND ed.entityid = :entity_type)';
                $bind[':entity_type'] = $params['t'];
            }
        }

        if ($params['cid'] !== null) {
            if ($params['cid'] === 0) {
                $where[] = self::noCityClause();
            } else {
                $geo = DB::query('SELECT title FROM geodb WHERE id = :id', [':id' => $params['cid']]);
                if (!empty($geo)) {
                    $where[] = 'p.place LIKE :place_city';
                    $bind[':place_city'] = '%' . $geo[0]['title'] . '%';
                }
            }
        }

        return [$where, $bind];
    }

    private static function noCityClause(): string
    {
        $cities = DB::query('SELECT title FROM geodb WHERE title != \'\' ORDER BY LENGTH(title) DESC');
        if (empty($cities)) {
            return '1=1';
        }

        $parts = [];
        foreach ($cities as $i => $city) {
            $parts[] = 'p.place NOT LIKE :no_city_' . $i;
        }
        return '(' . implode(' AND ', $parts) . ')';
    }

    private static function bindNoCity(array &$bind): void
    {
        $cities = DB::query('SELECT title FROM geodb WHERE title != \'\' ORDER BY LENGTH(title) DESC');
        foreach ($cities as $i => $city) {
            $bind[':no_city_' . $i] = '%' . $city['title'] . '%';
        }
    }

    private static function placeContainsCity(mixed $place, string $cityTitle): bool
    {
        if ($cityTitle === '' || $place === null || $place === '') {
            return false;
        }

        return mb_stripos((string) $place, $cityTitle) !== false;
    }

    public static function fetchPhotos(array $params): array
    {
        [$where, $bind] = self::buildPhotoFilter($params);
        if ($params['cid'] === 0) {
            self::bindNoCity($bind);
        }

        $sql = 'SELECT p.*, u.username,
            ed.title AS entity_title, ed.id AS entity_data_id,
            ent.title AS entity_type_title, ent.id AS entity_type_id,
            g.title AS gallery_title, g.id AS gallery_ref_id
            FROM photos p
            LEFT JOIN users u ON u.id = p.user_id
            LEFT JOIN entities_data ed ON ed.id = p.entitydata_id
            LEFT JOIN entities ent ON ent.id = ed.entityid
            LEFT JOIN galleries g ON g.id = p.gallery_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.timeupload DESC
            LIMIT ' . self::PER_PAGE . ' OFFSET ' . $params['st'];

        return DB::query($sql, $bind);
    }

    public static function countPhotos(array $params): int
    {
        [$where, $bind] = self::buildPhotoFilter($params);
        if ($params['cid'] === 0) {
            self::bindNoCity($bind);
        }

        $result = DB::query(
            'SELECT COUNT(*) AS cnt FROM photos p WHERE ' . implode(' AND ', $where),
            $bind
        );

        return (int) ($result[0]['cnt'] ?? 0);
    }

    private static function facetParams(array $params): array
    {
        return [
            'time' => $params['time'],
            'date' => $params['date'],
            'cid' => null,
            'aid' => null,
            't' => null,
            'st' => 0,
        ];
    }

    public static function fetchFacetAuthors(array $params): array
    {
        [$where, $bind] = self::buildPhotoFilter(self::facetParams($params));

        return DB::query(
            'SELECT p.user_id AS id, u.username, COUNT(*) AS cnt
             FROM photos p
             LEFT JOIN users u ON u.id = p.user_id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY p.user_id, u.username
             ORDER BY u.username ASC',
            $bind
        );
    }

    public static function fetchFacetCities(array $params): array
    {
        [$where, $bind] = self::buildPhotoFilter(self::facetParams($params));

        $photos = DB::query(
            'SELECT p.place FROM photos p WHERE ' . implode(' AND ', $where),
            $bind
        );

        $counts = [];
        $geodb = DB::query('SELECT id, title FROM geodb ORDER BY LENGTH(title) DESC');
        $noCity = 0;

        foreach ($photos as $photo) {
            $matched = false;
            foreach ($geodb as $city) {
                if (self::placeContainsCity($photo['place'] ?? null, $city['title'])) {
                    $counts[(int) $city['id']] = ($counts[(int) $city['id']] ?? 0) + 1;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $noCity++;
            }
        }

        $result = [];
        if ($noCity > 0) {
            $result[] = ['id' => 0, 'title' => '(без города)', 'cnt' => $noCity];
        }
        foreach ($geodb as $city) {
            $id = (int) $city['id'];
            if (!empty($counts[$id])) {
                $result[] = ['id' => $id, 'title' => $city['title'], 'cnt' => $counts[$id]];
            }
        }

        usort($result, fn($a, $b) => strcmp($a['title'], $b['title']));
        return $result;
    }

    public static function fetchFacetTypes(array $params): array
    {
        [$where, $bind] = self::buildPhotoFilter(self::facetParams($params));

        $rows = DB::query(
            'SELECT ent.id, ent.title, COUNT(*) AS cnt
             FROM photos p
             LEFT JOIN entities_data ed ON ed.id = p.entitydata_id
             LEFT JOIN entities ent ON ent.id = ed.entityid
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY COALESCE(ent.id, 0), COALESCE(ent.title, \'\')
             ORDER BY ent.title ASC',
            $bind
        );

        $noType = DB::query(
            'SELECT COUNT(*) AS cnt FROM photos p
             WHERE ' . implode(' AND ', $where) . ' AND (p.entitydata_id = 0 OR p.entitydata_id IS NULL)',
            $bind
        );

        $result = [];
        $noTypeCnt = (int) ($noType[0]['cnt'] ?? 0);
        if ($noTypeCnt > 0) {
            $result[] = ['id' => 0, 'title' => '(не указан)', 'cnt' => $noTypeCnt];
        }
        foreach ($rows as $row) {
            if ((int) $row['id'] > 0) {
                $result[] = ['id' => (int) $row['id'], 'title' => $row['title'], 'cnt' => (int) $row['cnt']];
            }
        }
        return $result;
    }

    public static function fetchArchiveDays(int $offset): array
    {
        return DB::query(
            'SELECT DATE(FROM_UNIXTIME(timeupload)) AS upload_date, COUNT(*) AS cnt
             FROM photos
             WHERE moderated = 1
             GROUP BY DATE(FROM_UNIXTIME(timeupload))
             ORDER BY upload_date DESC
             LIMIT ' . self::ARCHIVE_DAYS_PER_PAGE . ' OFFSET ' . max(0, $offset)
        );
    }

    public static function countArchiveDays(): int
    {
        $result = DB::query(
            'SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(timeupload))) AS cnt FROM photos WHERE moderated = 1'
        );
        return (int) ($result[0]['cnt'] ?? 0);
    }

    public static function fetchDaySummary(string $date): array
    {
        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');
        if ($start === false || $end === false) {
            return [];
        }

        $photos = DB::query(
            'SELECT p.place, p.gallery_id, p.entitydata_id, g.title AS gallery_title,
                    ed.title AS entity_title, ent.title AS entity_type_title, ent.id AS entity_type_id
             FROM photos p
             LEFT JOIN galleries g ON g.id = p.gallery_id
             LEFT JOIN entities_data ed ON ed.id = p.entitydata_id
             LEFT JOIN entities ent ON ent.id = ed.entityid
             WHERE p.moderated = 1 AND p.timeupload >= :from AND p.timeupload <= :to',
            [':from' => $start, ':to' => $end]
        );

        $geodb = DB::query('SELECT id, title FROM geodb ORDER BY LENGTH(title) DESC');
        $summary = [];

        foreach ($photos as $photo) {
            $cityId = 0;
            $cityTitle = '(без города)';
            foreach ($geodb as $city) {
                if (self::placeContainsCity($photo['place'] ?? null, $city['title'])) {
                    $cityId = (int) $city['id'];
                    $cityTitle = $city['title'];
                    break;
                }
            }

            if (!isset($summary[$cityId])) {
                $summary[$cityId] = [
                    'id' => $cityId,
                    'title' => $cityTitle,
                    'galleries' => [],
                    'types' => [],
                ];
            }

            if ((int) $photo['gallery_id'] > 0 && $photo['gallery_title']) {
                $gid = (int) $photo['gallery_id'];
                $summary[$cityId]['galleries'][$gid] = $photo['gallery_title'];
            }

            if ((int) $photo['entitydata_id'] > 0 && $photo['entity_type_id']) {
                $tid = (int) $photo['entity_type_id'];
                if (!isset($summary[$cityId]['types'][$tid])) {
                    $summary[$cityId]['types'][$tid] = [
                        'id' => $tid,
                        'title' => $photo['entity_type_title'],
                        'entities' => [],
                    ];
                }
                $summary[$cityId]['types'][$tid]['entities'][] = $photo['entity_title'];
            }
        }

        uasort($summary, fn($a, $b) => strcmp($a['title'], $b['title']));
        return $summary;
    }

    public static function buildUrl(array $params, array $overrides = []): string
    {
        $merged = array_merge($params, $overrides);
        $query = [];

        if ($merged['date'] !== '') {
            $query['date'] = $merged['date'];
        } elseif ($merged['time'] !== null) {
            $query['time'] = $merged['time'];
        }

        foreach (['cid', 'aid', 't'] as $filterKey) {
            if (array_key_exists($filterKey, $overrides)) {
                if ($merged[$filterKey] !== null) {
                    $query[$filterKey] = $merged[$filterKey];
                }
            } elseif ($merged[$filterKey] !== null && ($filterKey !== 'aid' || $merged[$filterKey] > 0)) {
                $query[$filterKey] = $merged[$filterKey];
            }
        }
        if (!empty($merged['st'])) {
            $query['st'] = $merged['st'];
        }

        $qs = http_build_query($query);
        return '/update' . ($qs ? '?' . $qs : '');
    }

    public static function renderFilterLine(string $label, array $items, callable $buildUrl, string $key, $activeValue): void
    {
        echo '<p class="sm" style="margin:8px 0"><b>' . htmlspecialchars($label) . ':</b> ';
        $allActive = $activeValue === null;
        echo $allActive ? '<b>(все)</b>' : '<a href="' . htmlspecialchars($buildUrl([$key => null, 'st' => 0])) . '">(все)</a>';

        foreach ($items as $item) {
            $id = (int) $item['id'];
            $isActive = $activeValue !== null && (int) $activeValue === $id;
            echo ' · ';
            if ($isActive) {
                echo '<b>' . htmlspecialchars($item['title'] ?? $item['username'] ?? '') . '</b>';
            } else {
                $title = $item['title'] ?? $item['username'] ?? '';
                echo '<a href="' . htmlspecialchars($buildUrl([$key => $id, 'st' => 0])) . '">' . htmlspecialchars($title) . '</a>';
            }
        }
        echo '</p>';
    }

    public static function periodLabel(array $params): string
    {
        if ($params['date'] !== '') {
            return Date::chronologyDate(strtotime($params['date'] . ' 12:00:00'));
        }
        if ($params['time'] === 24) {
            return '24 часа';
        }
        if ($params['time'] === 72) {
            return '72 часа';
        }
        if ($params['time'] !== null) {
            return $params['time'] . ' ч.';
        }
        return '';
    }
}