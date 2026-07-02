<?php

namespace App\Controllers\Api\Admin\Settings;

use App\Services\{AdminAccess, GalleryConfig, Json};

class Debug
{
    public function __construct()
    {
        if (!AdminAccess::requireOwner()) {
            return;
        }

        $enabled = filter_var($_POST['debug'] ?? $_GET['debug'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $result = GalleryConfig::setDebug($enabled);

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
            'debug' => $result['debug'] ?? $enabled,
        ]);
    }
}