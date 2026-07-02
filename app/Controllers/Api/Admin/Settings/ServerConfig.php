<?php

namespace App\Controllers\Api\Admin\Settings;

use App\Services\{AdminAccess, GalleryConfig, Json};

class ServerConfig
{
    public function __construct()
    {
        if (!AdminAccess::requireOwner()) {
            return;
        }

        $postedRoot = $_POST['root'] ?? null;
        if (!is_array($postedRoot)) {
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Некорректные данные конфигурации',
            ]);
            return;
        }

        $result = GalleryConfig::saveRootConfig($postedRoot);
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