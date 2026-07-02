<?php

namespace App\Services\Search;

use App\Services\DB;

class AuthorSearch
{
    public const PER_PAGE = 50;

    public static function fetch(array $params): array
    {
        $where = ['1=1'];
        $bind = [];

        if (!empty($params['q'])) {
            $where[] = '(u.username LIKE :q OR u.email LIKE :q2)';
            $bind[':q'] = '%' . $params['q'] . '%';
            $bind[':q2'] = '%' . $params['q'] . '%';
        }

        $offset = max(0, (int) ($params['st'] ?? 0));

        $sql = 'SELECT u.*,
            (SELECT COUNT(*) FROM photos p WHERE p.user_id = u.id AND p.moderated = 1) AS photo_count
            FROM users u
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY photo_count DESC, u.username ASC
            LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset;

        return DB::query($sql, $bind);
    }

    public static function count(array $params): int
    {
        $where = ['1=1'];
        $bind = [];

        if (!empty($params['q'])) {
            $where[] = '(u.username LIKE :q OR u.email LIKE :q2)';
            $bind[':q'] = '%' . $params['q'] . '%';
            $bind[':q2'] = '%' . $params['q'] . '%';
        }

        $sql = 'SELECT COUNT(*) AS cnt FROM users u WHERE ' . implode(' AND ', $where);
        $result = DB::query($sql, $bind);

        return (int) ($result[0]['cnt'] ?? 0);
    }
}