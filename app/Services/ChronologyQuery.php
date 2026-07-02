<?php

namespace App\Services;

class ChronologyQuery
{
    public const PER_PAGE = 30;

    public const TRANSIT_TYPES = [
        0 => 'Любой',
        1 => 'Трамвай',
        2 => 'Троллейбус',
        3 => 'Метро',
        4 => 'Монорельс',
        5 => 'Фуникулёр',
        6 => 'GLT',
        7 => 'AGT',
        8 => 'Маглев',
        9 => 'Электробус',
    ];

    public static function buildFilter(array $params): array
    {
        $where = ['1=1'];
        $bind = [];

        if (empty($params['all'])) {
            $where[] = '`main` = 1';
        }

        if (!empty($params['geodb_id'])) {
            $where[] = '`geodb_id` = :geodb_id';
            $bind[':geodb_id'] = (int) $params['geodb_id'];
        }

        if (isset($params['t']) && $params['t'] !== '' && (int) $params['t'] > 0) {
            $where[] = '`transit_type` = :transit_type';
            $bind[':transit_type'] = (int) $params['t'];
        }

        if (!empty($params['date_from'])) {
            $from = strtotime($params['date_from']);
            if ($from !== false) {
                $where[] = '`time` >= :date_from';
                $bind[':date_from'] = $from;
            }
        }

        if (!empty($params['date_to'])) {
            $to = strtotime($params['date_to'] . ' 23:59:59');
            if ($to !== false) {
                $where[] = '`time` <= :date_to';
                $bind[':date_to'] = $to;
            }
        }

        if (!empty($params['q'])) {
            $where[] = '(`body` LIKE :q OR `city` LIKE :q)';
            $bind[':q'] = '%' . $params['q'] . '%';
        }

        return [$where, $bind];
    }

    public static function fetch(array $params): array
    {
        [$where, $bind] = self::buildFilter($params);
        $offset = max(0, (int) ($params['st'] ?? 0));
        $sql = 'SELECT * FROM chronology WHERE ' . implode(' AND ', $where)
            . ' ORDER BY `time` DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset;

        return DB::query($sql, $bind);
    }

    public static function count(array $params): int
    {
        [$where, $bind] = self::buildFilter($params);
        $sql = 'SELECT COUNT(*) AS cnt FROM chronology WHERE ' . implode(' AND ', $where);
        $result = DB::query($sql, $bind);

        return (int) ($result[0]['cnt'] ?? 0);
    }

    public static function cityLabel(int $geodbId, string $city): string
    {
        if ($geodbId > 0) {
            $row = DB::query('SELECT title FROM geodb WHERE id = :id', [':id' => $geodbId]);
            if (!empty($row)) {
                return $row[0]['title'];
            }
        }

        return $city;
    }
}