<?php

namespace App\Services\Search;

use App\Services\DB;

class VehicleSearch
{
    public const PER_PAGE = 50;

    public static function fetch(array $params): array
    {
        $where = ['1=1'];
        $bind = [];

        if (!empty($params['etype'])) {
            $where[] = 'ed.entityid = :entity_id';
            $bind[':entity_id'] = (int) $params['etype'];
        }

        if (!empty($params['num'])) {
            $where[] = '(LOWER(ed.title) LIKE :num OR CAST(ed.id AS CHAR) LIKE :num_id)';
            $bind[':num'] = '%' . mb_strtolower($params['num']) . '%';
            $bind[':num_id'] = '%' . $params['num'] . '%';
        }

        if (!empty($params['q'])) {
            $where[] = '(ed.title LIKE :q OR ed.comment LIKE :q2)';
            $bind[':q'] = '%' . $params['q'] . '%';
            $bind[':q2'] = '%' . $params['q'] . '%';
        }

        $offset = max(0, (int) ($params['st'] ?? 0));

        $sql = 'SELECT ed.*, e.title AS entity_type, e.color
            FROM entities_data ed
            LEFT JOIN entities e ON e.id = ed.entityid
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ed.id DESC
            LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset;

        return DB::query($sql, $bind);
    }

    public static function count(array $params): int
    {
        $where = ['1=1'];
        $bind = [];

        if (!empty($params['etype'])) {
            $where[] = 'ed.entityid = :entity_id';
            $bind[':entity_id'] = (int) $params['etype'];
        }

        if (!empty($params['num'])) {
            $where[] = '(LOWER(ed.title) LIKE :num OR CAST(ed.id AS CHAR) LIKE :num_id)';
            $bind[':num'] = '%' . mb_strtolower($params['num']) . '%';
            $bind[':num_id'] = '%' . $params['num'] . '%';
        }

        if (!empty($params['q'])) {
            $where[] = '(ed.title LIKE :q OR ed.comment LIKE :q2)';
            $bind[':q'] = '%' . $params['q'] . '%';
            $bind[':q2'] = '%' . $params['q'] . '%';
        }

        $sql = 'SELECT COUNT(*) AS cnt FROM entities_data ed WHERE ' . implode(' AND ', $where);
        $result = DB::query($sql, $bind);

        return (int) ($result[0]['cnt'] ?? 0);
    }
}