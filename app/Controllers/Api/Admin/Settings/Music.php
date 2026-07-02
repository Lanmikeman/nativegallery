<?php

namespace App\Controllers\Api\Admin\Settings;

use App\Services\{AdminAccess, GalleryConfig, Json};

class Music
{
    public function __construct()
    {
        if (!AdminAccess::requireFullAdmin()) {
            return;
        }

        $result = GalleryConfig::updateMusicSettings([
            'enabled' => filter_var($_POST['audio_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        if (!$result['ok']) {
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => $result['message'],
            ]);
            return;
        }

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'message' => $result['message'],
        ]);
    }
}