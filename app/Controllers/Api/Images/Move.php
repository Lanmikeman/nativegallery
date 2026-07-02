<?php

namespace App\Controllers\Api\Images;

use App\Services\DB;

class Move
{
    public function __construct()
    {
        header('Content-Type: text/plain; charset=UTF-8');

        $pid = (int) ($_GET['pid'] ?? 0);
        $vid = (int) ($_GET['vid'] ?? 0);
        $gid = (int) ($_GET['gid'] ?? 0);
        $aid = (int) ($_GET['aid'] ?? 0);
        $next = (int) ($_GET['next'] ?? 0);

        if ($pid <= 0) {
            echo '0';
            return;
        }

        $where = ['moderated = 1'];
        $bind = [':pid' => $pid];

        if ($vid > 0) {
            $where[] = 'entitydata_id = :vid';
            $bind[':vid'] = $vid;
        } elseif ($gid > 0) {
            $where[] = 'gallery_id = :gid';
            $bind[':gid'] = $gid;
        } elseif ($aid > 0) {
            $where[] = 'user_id = :aid';
            $bind[':aid'] = $aid;
        }

        $whereSql = implode(' AND ', $where);

        if ($next) {
            $rows = DB::query(
                "SELECT id FROM photos WHERE {$whereSql} AND id < :pid ORDER BY id DESC LIMIT 1",
                $bind
            );
        } else {
            $rows = DB::query(
                "SELECT id FROM photos WHERE {$whereSql} AND id > :pid ORDER BY id ASC LIMIT 1",
                $bind
            );
        }

        echo (string) ((int) ($rows[0]['id'] ?? 0));
    }
}