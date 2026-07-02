<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, DB, Json, Upload as FileUpload, AudioLibrary};

class Upload
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
        if (!AudioLibrary::uploadAllowed()) {
            echo Json::return(['errorcode' => 'DISABLED', 'error' => 1, 'message' => 'Загрузка аудио отключена']);
            return;
        }
        if (empty($_FILES['audio']) || $_FILES['audio']['error'] === UPLOAD_ERR_NO_FILE) {
            echo Json::return(['errorcode' => 'NO_FILE', 'error' => 1, 'message' => 'Файл не выбран']);
            return;
        }
        if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            echo Json::return(['errorcode' => 'UPLOAD_ERR', 'error' => 1, 'message' => 'Ошибка загрузки файла']);
            return;
        }
        if ($_FILES['audio']['size'] > AudioLibrary::maxUploadBytes()) {
            echo Json::return(['errorcode' => 'TOO_LARGE', 'error' => 1, 'message' => 'Файл слишком большой']);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['audio']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, AudioLibrary::allowedMimeTypes(), true)) {
            echo Json::return(['errorcode' => 'FILE_NOTSUPPORTED', 'error' => 1, 'message' => 'Неподдерживаемый формат']);
            return;
        }

        $upload = new FileUpload($_FILES['audio'], 'cdn/audio/');
        $title = trim((string) ($_POST['title'] ?? ''));
        $artist = trim((string) ($_POST['artist'] ?? ''));
        if ($title === '') {
            $title = pathinfo($_FILES['audio']['name'], PATHINFO_FILENAME);
        }

        $now = time();
        DB::query(
            'INSERT INTO audio_tracks (user_id, title, artist, source_type, src, duration, file_size, created_at)
             VALUES (:uid, :title, :artist, \'upload\', :src, 0, :size, :created)',
            [
                ':uid' => $userId,
                ':title' => $title,
                ':artist' => $artist,
                ':src' => $upload->src,
                ':size' => (int) $_FILES['audio']['size'],
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
                'source_type' => 'upload',
                'src' => $upload->src,
                'duration' => 0,
                'created_at' => $now,
            ]),
        ]);
    }
}