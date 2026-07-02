<?php

namespace App\Services\Search;

use App\Services\DB;

class CommentSearch
{
    public const PER_PAGE = 30;

    public static function fetch(array $params): array
    {
        $where = ['1=1'];
        $bind = [];

        if (!empty($params['q'])) {
            $where[] = 'c.body LIKE :q';
            $bind[':q'] = '%' . $params['q'] . '%';
        }

        if (!empty($params['id'])) {
            $where[] = 'c.user_id = :user_id';
            $bind[':user_id'] = (int) $params['id'];
        }

        if (!empty($params['photo_id'])) {
            $where[] = 'c.photo_id = :photo_id';
            $bind[':photo_id'] = (int) $params['photo_id'];
        }

        if (!empty($params['date_from'])) {
            $from = strtotime($params['date_from']);
            if ($from !== false) {
                $where[] = 'c.posted_at >= :date_from';
                $bind[':date_from'] = $from;
            }
        }

        if (!empty($params['date_to'])) {
            $to = strtotime($params['date_to'] . ' 23:59:59');
            if ($to !== false) {
                $where[] = 'c.posted_at <= :date_to';
                $bind[':date_to'] = $to;
            }
        }

        $offset = max(0, (int) ($params['st'] ?? 0));

        $sql = 'SELECT c.*, u.username, p.photourl, p.moderated AS photo_moderated
            FROM photos_comments c
            LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN photos p ON p.id = c.photo_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY c.posted_at DESC
            LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset;

        return DB::query($sql, $bind);
    }

    public static function count(array $params): int
    {
        $where = ['1=1'];
        $bind = [];

        if (!empty($params['q'])) {
            $where[] = 'c.body LIKE :q';
            $bind[':q'] = '%' . $params['q'] . '%';
        }

        if (!empty($params['id'])) {
            $where[] = 'c.user_id = :user_id';
            $bind[':user_id'] = (int) $params['id'];
        }

        if (!empty($params['photo_id'])) {
            $where[] = 'c.photo_id = :photo_id';
            $bind[':photo_id'] = (int) $params['photo_id'];
        }

        if (!empty($params['date_from'])) {
            $from = strtotime($params['date_from']);
            if ($from !== false) {
                $where[] = 'c.posted_at >= :date_from';
                $bind[':date_from'] = $from;
            }
        }

        if (!empty($params['date_to'])) {
            $to = strtotime($params['date_to'] . ' 23:59:59');
            if ($to !== false) {
                $where[] = 'c.posted_at <= :date_to';
                $bind[':date_to'] = $to;
            }
        }

        $sql = 'SELECT COUNT(*) AS cnt FROM photos_comments c WHERE ' . implode(' AND ', $where);
        $result = DB::query($sql, $bind);

        return (int) ($result[0]['cnt'] ?? 0);
    }
}