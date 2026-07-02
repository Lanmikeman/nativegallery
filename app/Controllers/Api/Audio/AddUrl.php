<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, DB, Json, AudioLibrary};

class AddUrl
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
            echo Json::return(['errorcode' => 'DISABLED', 'error' => 1, 'message' => 'Внешние ссылки отключены']);
            return;
        }

        $url = trim((string) ($_POST['url'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $artist = trim((string) ($_POST['artist'] ?? ''));

        if (!AudioLibrary::isValidStreamUrl($url)) {
            echo Json::return(['errorcode' => 'BAD_URL', 'error' => 1, 'message' => 'Некорректный URL']);
            return;
        }
        if ($title === '') {
            $title = basename(parse_url($url, PHP_URL_PATH) ?: '') ?: (parse_url($url, PHP_URL_HOST) ?: $url);
        }

        $now = time();
        DB::query(
            'INSERT INTO audio_tracks (user_id, title, artist, source_type, src, duration, file_size, created_at)
             VALUES (:uid, :title, :artist, \'url\', :src, 0, 0, :created)',
            [
                ':uid' => $userId,
                ':title' => $title,
                ':artist' => $artist,
                ':src' => $url,
                ':created' => $now,
            ]
        );

        $id = (int) DB::lastInsertId();
        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'track' => AudioLibrary::trackRow([
                'id' => $id,
                'title' => $title,
                'artist' => $artist,
                'source_type' => 'url',
                'src' => $url,
                'duration' => 0,
                'created_at' => $now,
            ]),
        ]);
    }
}