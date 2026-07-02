<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, Json, AudioLibrary};

class Library
{
    public function __construct()
    {
        $userId = Auth::userid();
        if ($userId <= 0) {
            echo Json::return(['errorcode' => 'NO_AUTH', 'error' => 1, 'message' => 'Требуется авторизация']);
            return;
        }
        if ($disabled = AudioLibrary::disabledResponse()) {
            echo Json::return($disabled);
            return;
        }

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'library' => AudioLibrary::libraryForUser($userId),
            'tables_exist' => AudioLibrary::tablesExist(),
            'global_streams_table_exist' => AudioLibrary::globalStreamsTableExist(),
        ]);
    }
}