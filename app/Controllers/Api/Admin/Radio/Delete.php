<?php

namespace App\Controllers\Api\Admin\Radio;

use App\Services\{Auth, DB, Json, AudioLibrary};

class Delete
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

        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            echo Json::return(['errorcode' => 'BAD_ID', 'error' => 1, 'message' => 'Некорректный ID']);
            return;
        }

        DB::query('DELETE FROM audio_global_streams WHERE id = :id', [':id' => $id]);
        echo Json::return(['errorcode' => 0, 'error' => 0]);
    }
}