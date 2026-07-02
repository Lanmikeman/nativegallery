<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, DB, Json, AudioLibrary};

class Delete
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

        $kind = trim((string) ($_POST['kind'] ?? $_GET['kind'] ?? ''));
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            echo Json::return(['errorcode' => 'BAD_ID', 'error' => 1, 'message' => 'Некорректный ID']);
            return;
        }

        if ($kind === 'track') {
            if (!AudioLibrary::ownsTrack($id, $userId)) {
                echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Нет доступа']);
                return;
            }
            DB::query('DELETE FROM audio_playlist_items WHERE item_type = \'track\' AND item_id = :id', [':id' => $id]);
            DB::query('DELETE FROM audio_tracks WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $userId]);
        } elseif ($kind === 'stream') {
            if (!AudioLibrary::ownsStream($id, $userId)) {
                echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Нет доступа']);
                return;
            }
            DB::query('DELETE FROM audio_playlist_items WHERE item_type = \'stream\' AND item_id = :id', [':id' => $id]);
            DB::query('DELETE FROM audio_streams WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $userId]);
        } elseif ($kind === 'playlist') {
            if (!AudioLibrary::ownsPlaylist($id, $userId)) {
                echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Нет доступа']);
                return;
            }
            DB::query('DELETE FROM audio_playlist_items WHERE playlist_id = :id', [':id' => $id]);
            DB::query('DELETE FROM audio_playlists WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $userId]);
        } else {
            echo Json::return(['errorcode' => 'BAD_KIND', 'error' => 1, 'message' => 'Неизвестный тип']);
            return;
        }

        echo Json::return(['errorcode' => 0, 'error' => 0]);
    }
}