<?php

namespace App\Controllers\Api\Admin\Pages;

use App\Services\{DB, Json};

class Get
{
    public function __construct()
    {
        $pageId = (int) (explode('/', strtok($_SERVER['REQUEST_URI'], '?'))[4] ?? 0);
        if ($pageId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Страница не найдена']);
            return;
        }

        $rows = DB::query('SELECT * FROM pages WHERE id = :id', [':id' => $pageId]);
        if (empty($rows)) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Страница не найдена']);
            return;
        }

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'page' => $rows[0],
        ]);
    }
}