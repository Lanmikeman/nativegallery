<?php

namespace App\Controllers\Api\Admin\News;

use App\Services\{DB, Json};

class Get
{
    public function __construct()
    {
        $postId = (int) (explode('/', strtok($_SERVER['REQUEST_URI'], '?'))[4] ?? 0);
        if ($postId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Новость не найдена']);
            return;
        }

        $rows = DB::query('SELECT * FROM news WHERE id = :id', [':id' => $postId]);
        if (empty($rows)) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Новость не найдена']);
            return;
        }

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'news' => $rows[0],
        ]);
    }
}