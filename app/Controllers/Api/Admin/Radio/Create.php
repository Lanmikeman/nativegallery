<?php

namespace App\Controllers\Api\Admin\Radio;

use App\Services\{Auth, DB, Json, AudioLibrary};

class Create
{
    public function __construct()
    {
        $userId = Auth::userid();
        $user = new \App\Models\User($userId);
        if ($userId <= 0 || (int) $user->i('admin') <= 0) {
            echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Нет доступа']);
            return;
        }
        if (!AudioLibrary::globalStreamsTableExist()) {
            echo Json::return(['errorcode' => 'NO_TABLES', 'error' => 1, 'message' => 'Примените миграцию sql_0011.sql']);
            return;
        }

        $url = trim((string) ($_POST['url'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $sort = (int) ($_POST['sort_order'] ?? $_POST['sort'] ?? 0);

        if (!AudioLibrary::isValidStreamUrl($url)) {
            echo Json::return(['errorcode' => 'BAD_URL', 'error' => 1, 'message' => 'Некорректный URL']);
            return;
        }
        if ($title === '') {
            $title = parse_url($url, PHP_URL_HOST) ?: $url;
        }

        $now = time();
        DB::query(
            'INSERT INTO audio_global_streams (title, url, sort_order, enabled, created_at)
             VALUES (:title, :url, :sort, 1, :created)',
            [':title' => $title, ':url' => $url, ':sort' => $sort, ':created' => $now]
        );

        $id = (int) DB::lastInsertId();
        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'station' => AudioLibrary::globalStreamRow([
                'id' => $id,
                'title' => $title,
                'url' => $url,
                'sort_order' => $sort,
                'enabled' => 1,
                'created_at' => $now,
            ]),
        ]);
    }
}