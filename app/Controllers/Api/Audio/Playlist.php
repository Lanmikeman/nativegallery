<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, DB, Json, AudioLibrary};

class Playlist
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

        $action = trim((string) ($_POST['action'] ?? 'create'));

        if ($action === 'create') {
            $this->create($userId);
            return;
        }
        if ($action === 'rename') {
            $this->rename($userId);
            return;
        }
        if ($action === 'add_item') {
            $this->addItem($userId);
            return;
        }
        if ($action === 'remove_item') {
            $this->removeItem($userId);
            return;
        }
        if ($action === 'reorder') {
            $this->reorder($userId);
            return;
        }

        echo Json::return(['errorcode' => 'BAD_ACTION', 'error' => 1, 'message' => 'Неизвестное действие']);
    }

    private function create(int $userId): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $title = 'Новый плейлист';
        }
        $now = time();
        DB::query(
            'INSERT INTO audio_playlists (user_id, title, created_at, updated_at) VALUES (:uid, :title, :c, :u)',
            [':uid' => $userId, ':title' => $title, ':c' => $now, ':u' => $now]
        );
        $id = (int) DB::lastInsertId();
        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'playlist' => [
                'id' => $id,
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now,
                'items' => [],
            ],
        ]);
    }

    private function rename(int $userId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($id <= 0 || $title === '') {
            echo Json::return(['errorcode' => 'BAD_INPUT', 'error' => 1, 'message' => 'Укажите ID и название']);
            return;
        }
        if (!AudioLibrary::ownsPlaylist($id, $userId)) {
            echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Нет доступа']);
            return;
        }
        $now = time();
        DB::query(
            'UPDATE audio_playlists SET title = :title, updated_at = :u WHERE id = :id',
            [':title' => $title, ':u' => $now, ':id' => $id]
        );
        echo Json::return(['errorcode' => 0, 'error' => 0, 'id' => $id, 'title' => $title]);
    }

    private function addItem(int $userId): void
    {
        $playlistId = (int) ($_POST['playlist_id'] ?? 0);
        $itemType = trim((string) ($_POST['item_type'] ?? 'track'));
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $url = trim((string) ($_POST['url'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));

        if ($playlistId <= 0 || !AudioLibrary::ownsPlaylist($playlistId, $userId)) {
            echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Плейлист не найден']);
            return;
        }

        if ($itemType === 'track' && $itemId > 0) {
            if (!AudioLibrary::ownsTrack($itemId, $userId)) {
                echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Трек не найден']);
                return;
            }
        } elseif ($itemType === 'stream' && $itemId > 0) {
            if (!AudioLibrary::ownsStream($itemId, $userId)) {
                echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Поток не найден']);
                return;
            }
        } elseif ($itemType === 'url') {
            if (!AudioLibrary::isValidStreamUrl($url)) {
                echo Json::return(['errorcode' => 'BAD_URL', 'error' => 1, 'message' => 'Некорректный URL']);
                return;
            }
            if ($title === '') {
                $title = basename(parse_url($url, PHP_URL_PATH) ?: '') ?: $url;
            }
        } else {
            echo Json::return(['errorcode' => 'BAD_ITEM', 'error' => 1, 'message' => 'Некорректный элемент']);
            return;
        }

        $maxOrder = DB::query(
            'SELECT COALESCE(MAX(sort_order), 0) AS m FROM audio_playlist_items WHERE playlist_id = :pid',
            [':pid' => $playlistId]
        );
        $order = (int) ($maxOrder[0]['m'] ?? 0) + 1;

        DB::query(
            'INSERT INTO audio_playlist_items (playlist_id, item_type, item_id, url, title, sort_order)
             VALUES (:pid, :type, :iid, :url, :title, :ord)',
            [
                ':pid' => $playlistId,
                ':type' => $itemType,
                ':iid' => $itemId,
                ':url' => $url,
                ':title' => $title,
                ':ord' => $order,
            ]
        );

        $now = time();
        DB::query('UPDATE audio_playlists SET updated_at = :u WHERE id = :id', [':u' => $now, ':id' => $playlistId]);

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'playlist_id' => $playlistId,
            'items' => AudioLibrary::playlistItems($playlistId),
        ]);
    }

    private function removeItem(int $userId): void
    {
        $playlistId = (int) ($_POST['playlist_id'] ?? 0);
        $itemRowId = (int) ($_POST['item_id'] ?? 0);

        if ($playlistId <= 0 || $itemRowId <= 0 || !AudioLibrary::ownsPlaylist($playlistId, $userId)) {
            echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'Нет доступа']);
            return;
        }

        DB::query(
            'DELETE FROM audio_playlist_items WHERE id = :iid AND playlist_id = :pid',
            [':iid' => $itemRowId, ':pid' => $playlistId]
        );
        DB::query('UPDATE audio_playlists SET updated_at = :u WHERE id = :id', [':u' => time(), ':id' => $playlistId]);

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'items' => AudioLibrary::playlistItems($playlistId),
        ]);
    }

    private function reorder(int $userId): void
    {
        $playlistId = (int) ($_POST['playlist_id'] ?? 0);
        $orderRaw = $_POST['order'] ?? '[]';
        $order = is_array($orderRaw) ? $orderRaw : json_decode((string) $orderRaw, true);

        if ($playlistId <= 0 || !AudioLibrary::ownsPlaylist($playlistId, $userId) || !is_array($order)) {
            echo Json::return(['errorcode' => 'BAD_INPUT', 'error' => 1, 'message' => 'Некорректные данные']);
            return;
        }

        $pos = 0;
        foreach ($order as $itemRowId) {
            $pos++;
            DB::query(
                'UPDATE audio_playlist_items SET sort_order = :ord
                 WHERE id = :iid AND playlist_id = :pid',
                [':ord' => $pos, ':iid' => (int) $itemRowId, ':pid' => $playlistId]
            );
        }

        echo Json::return(['errorcode' => 0, 'error' => 0]);
    }
}