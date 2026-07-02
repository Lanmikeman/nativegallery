<?php

namespace App\Controllers;

use App\Core\Page;
use App\Services\{Auth, OpenVKAuth, Router};

class AuthController
{
    public static function openvkStart(): void
    {
        if (!OpenVKAuth::isEnabled()) {
            Router::redirect('/login?ovk_error=' . rawurlencode('Вход через OpenVK отключён'));
            return;
        }

        $providerId = (string) ($_GET['provider'] ?? '');
        if (OpenVKAuth::provider($providerId) === null) {
            Router::redirect('/login?ovk_error=' . rawurlencode('Неизвестный провайдер OpenVK'));
            return;
        }

        $mode = (string) ($_GET['mode'] ?? 'login');
        if ($mode !== 'link') {
            $mode = 'login';
        }

        if ($mode === 'link' && Auth::userid() <= 0) {
            Router::redirect('/login?return=' . rawurlencode('/lk/profile?type=OpenVK'));
            return;
        }

        $url = OpenVKAuth::authorizeUrl($providerId, $mode);
        header('Location: ' . $url);
        exit;
    }

    public static function openvkCallback(): void
    {
        if (!OpenVKAuth::isEnabled()) {
            Router::redirect('/login');
            return;
        }

        $token = OpenVKAuth::extractAccessToken();
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
        $state = isset($_GET['state']) ? (string) $_GET['state'] : null;

        if ($token !== '' && OpenVKAuth::responseType() === 'php') {
            self::finish($token, $userId, $state);
            return;
        }

        Page::set('Auth/Callback');
    }

    public static function finish(string $token, ?int $userId, ?string $state = null): void
    {
        $result = OpenVKAuth::complete($token, $userId, $state);

        if ((int) ($result['errorcode'] ?? 1) !== 0) {
            $redirect = (string) ($result['redirect'] ?? '/login');
            Router::redirect($redirect);
            return;
        }

        Router::redirect((string) ($result['redirect'] ?? '/'));
    }
}