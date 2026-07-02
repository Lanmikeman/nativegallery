<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, Json, AudioLibrary};

class Metadata
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

        $url = trim((string) ($_GET['url'] ?? ''));
        if (!AudioLibrary::canProxyUrl($userId, $url)) {
            echo Json::return(['errorcode' => 'FORBIDDEN', 'error' => 1, 'message' => 'URL недоступен']);
            return;
        }

        $meta = AudioLibrary::fetchIcyMetadata($url);

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'title' => $meta['title'],
            'artist' => $meta['artist'],
            'station' => $meta['station'],
        ]);
    }
}