<?php

namespace App\Controllers\Api\Admin\Settings;

use App\Services\{AdminAccess, GalleryConfig, Json};

class Auth
{
    public function __construct()
    {
        if (!AdminAccess::requireFullAdmin()) {
            return;
        }

        $providers = [];
        if (isset($_POST['providers']) && is_array($_POST['providers'])) {
            foreach ($_POST['providers'] as $id => $value) {
                $providers[(string) $id] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $result = GalleryConfig::updateAuthSettings([
            'registration_public' => filter_var($_POST['registration_public'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'openvk_enabled' => filter_var($_POST['openvk_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'openvk_auto_register' => filter_var($_POST['openvk_auto_register'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'providers' => $providers,
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