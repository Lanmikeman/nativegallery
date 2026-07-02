<?php

namespace App\Controllers\Api;

use App\Services\{Auth, Json, OpenVKAuth as OpenVKAuthService};

class OpenVKAuth
{
    public function __construct()
    {
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'login');

        if ($action === 'unlink') {
            $this->unlink();
            return;
        }

        $this->login();
    }

    private function login(): void
    {
        $raw = file_get_contents('php://input');
        $payload = [];
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $token = trim((string) ($payload['access_token'] ?? $_POST['access_token'] ?? OpenVKAuthService::extractAccessToken()));
        $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : (isset($_POST['user_id']) ? (int) $_POST['user_id'] : null);
        $state = isset($payload['state']) ? (string) $payload['state'] : (isset($_GET['state']) ? (string) $_GET['state'] : null);

        $result = OpenVKAuthService::complete($token, $userId, $state);
        echo Json::return($result);
    }

    private function unlink(): void
    {
        if (Auth::userid() <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Нужна авторизация']);
            return;
        }

        $providerId = (string) ($_POST['provider'] ?? '');
        $result = OpenVKAuthService::unlink(Auth::userid(), $providerId);
        echo Json::return($result);
    }
}