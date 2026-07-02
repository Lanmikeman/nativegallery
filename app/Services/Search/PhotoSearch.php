<?php

namespace App\Services\Search;

use App\Services\DB;

class PhotoSearch
{
    public const PER_PAGE = 30;

    public static function buildFilter(array $params): array
    {
        $where = ['p.moderated = 1'];
        $bind = [];

        if (!empty($params['id'])) {
            $where[] = 'p.user_id = :user_id';
            $bind[':user_id'] = (int) $params['id'];
        }

        if (!empty($params['gid'])) {
            $where[] = 'p.gallery_id = :gallery_id';
            $bind[':gallery_id'] = (int) $params['gid'];
        }

        if (!empty($params['nid'])) {
            $where[] = 'p.entitydata_id = :entitydata_id';
            $bind[':entitydata_id'] = (int) $params['nid'];
        }

        if (!empty($params['etype'])) {
            $where[] = 'EXISTS (SELECT 1 FROM entities_data ed WHERE ed.id = p.entitydata_id AND ed.entityid = :entity_id)';
            $bind[':entity_id'] = (int) $params['etype'];
        }

        if (!empty($params['cid'])) {
            $geo = DB::query('SELECT title FROM geodb WHERE id = :id', [':id' => (int) $params['cid']]);
            if (!empty($geo)) {
                $where[] = 'p.place LIKE :place_city';
                $bind[':place_city'] = '%' . $geo[0]['title'] . '%';
            }
        }

        if (!empty($params['place'])) {
            $where[] = 'p.place LIKE :place';
            $bind[':place'] = '%' . $params['place'] . '%';
        }

        if (!empty($params['route'])) {
            $where[] = "JSON_UNQUOTE(JSON_EXTRACT(p.content, '$.entityroute')) LIKE :route";
            $bind[':route'] = '%' . $params['route'] . '%';
        }

        if (!empty($params['q'])) {
            $where[] = '(p.postbody LIKE :q OR p.place LIKE :q2)';
            $bind[':q'] = '%' . $params['q'] . '%';
            $bind[':q2'] = '%' . $params['q'] . '%';
        }

        if (!empty($params['camera'])) {
            $where[] = 'p.exif LIKE :camera';
            $bind[':camera'] = '%' . $params['camera'] . '%';
        }

        if (!empty($params['date_shot_from'])) {
            $from = strtotime($params['date_shot_from']);
            if ($from !== false) {
                $where[] = 'p.posted_at >= :date_shot_from';
                $bind[':date_shot_from'] = $from;
            }
        }

        if (!empty($params['date_shot_to'])) {
            $to = strtotime($params['date_shot_to'] . ' 23:59:59');
            if ($to !== false) {
                $where[] = 'p.posted_at <= :date_shot_to';
                $bind[':date_shot_to'] = $to;
            }
        }

        if (!empty($params['date_pub_from'])) {
            $from = strtotime($params['date_pub_from']);
            if ($from !== false) {
                $where[] = 'p.timeupload >= :date_pub_from';
                $bind[':date_pub_from'] = $from;
            }
        }

        if (!empty($params['date_pub_to'])) {
            $to = strtotime($params['date_pub_to'] . ' 23:59:59');
            if ($to !== false) {
                $where[] = 'p.timeupload <= :date_pub_to';
                $bind[':date_pub_to'] = $to;
            }
        }

        return [$where, $bind];
    }

    public static function fetch(array $params): array
    {
        [$where, $bind] = self::buildFilter($params);
        $offset = max(0, (int) ($params['st'] ?? 0));

        $order = ($params['sort'] ?? '') === 'shot'
            ? 'p.posted_at DESC'
            : 'p.timeupload DESC';

        $sql = 'SELECT p.*, u.username FROM photos p
            LEFT JOIN users u ON u.id = p.user_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $order . '
            LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset;

        return DB::query($sql, $bind);
    }

    public static function count(array $params): int
    {
        [$where, $bind] = self::buildFilter($params);
        $sql = 'SELECT COUNT(*) AS cnt FROM photos p WHERE ' . implode(' AND ', $where);
        $result = DB::query($sql, $bind);

        return (int) ($result[0]['cnt'] ?? 0);
    }

    public static function hasCriteria(array $params): bool
    {
        $keys = [
            'id', 'gid', 'nid', 'etype', 'cid', 'place', 'route',
            'q', 'camera', 'date_shot_from', 'date_shot_to',
            'date_pub_from', 'date_pub_to',
        ];

        foreach ($keys as $key) {
            if (!empty($params[$key])) {
                return true;
            }
        }

        return false;
    }
}