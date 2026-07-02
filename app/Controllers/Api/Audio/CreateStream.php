<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, DB, Json, AudioLibrary};

class CreateStream
{
    public function __construct()
    {
        $userId = Auth::userid();
        if ($userId <= 0) {
            echo Json::return(['errorcode' => 'NO_AUTH', 'error' => 1, 'message' => 'Требуется авторизация']);
            return;
        }
        if (!AudioLibrary::tablesExist()) {
            echo Json::return(['errorcode' => 'NO_TABLES', 'error' => 1, 'message' => 'Примените миграцию sql_0010.sql']);
            return;
        }
        if (!AudioLibrary::streamsAllowed()) {
            echo Json::return(['errorcode' => 'DISABLED', 'error' => 1, 'message' => 'Потоки отключены']);
            return;
        }

        $url = trim((string) ($_POST['url'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));

        if (!AudioLibrary::isValidStreamUrl($url)) {
            echo Json::return(['errorcode' => 'BAD_URL', 'error' => 1, 'message' => 'Некорректный URL (только http/https)']);
            return;
        }
        if ($title === '') {
            $title = parse_url($url, PHP_URL_HOST) ?: $url;
        }

        $now = time();
        DB::query(
            'INSERT INTO audio_streams (user_id, title, url, created_at) VALUES (:uid, :title, :url, :created)',
            [':uid' => $userId, ':title' => $title, ':url' => $url, ':created' => $now]
        );

        $id = (int) DB::lastInsertId();
        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'stream' => AudioLibrary::streamRow([
                'id' => $id,
                'title' => $title,
                'url' => $url,
                'created_at' => $now,
            ]),
        ]);
    }
}