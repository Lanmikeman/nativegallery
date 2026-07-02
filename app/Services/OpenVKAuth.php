<?php

namespace App\Services;

class OpenVKAuth
{
    public static function isEnabled(): bool
    {
        return !empty(NGALLERY['root']['openvk']['enabled'])
            && !empty(NGALLERY['root']['openvk']['providers']);
    }

    /** @return array<string, array<string, mixed>> */
    public static function providers(): array
    {
        if (!self::isEnabled()) {
            return [];
        }

        $providers = NGALLERY['root']['openvk']['providers'];
        if (!is_array($providers)) {
            return [];
        }

        $resolved = [];
        foreach ($providers as $id => $provider) {
            if (!is_array($provider) || empty($provider['domain'])) {
                continue;
            }
            $domain = rtrim((string) $provider['domain'], '/');
            $resolved[$id] = array_merge([
                'id' => (string) $id,
                'label' => (string) ($provider['label'] ?? $id),
                'domain' => $domain,
                'accent' => (string) ($provider['accent'] ?? '#5181b8'),
                'icon' => (string) ($provider['icon'] ?? ($domain . '/assets/packages/static/openvk/img/favicon.ico')),
            ], $provider);
        }

        return $resolved;
    }

    public static function provider(string $id): ?array
    {
        $providers = self::providers();
        return $providers[$id] ?? null;
    }

    public static function redirectUri(): string
    {
        $configured = trim((string) (NGALLERY['root']['openvk']['redirect_uri'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/auth/callback';
    }

    public static function clientName(): string
    {
        $name = trim((string) (NGALLERY['root']['openvk']['client_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        return (string) (NGALLERY['root']['title'] ?? 'NativeGallery');
    }

    public static function responseType(): string
    {
        $type = (string) (NGALLERY['root']['openvk']['response_type'] ?? 'php');
        return in_array($type, ['php', 'token'], true) ? $type : 'php';
    }

    public static function authorizeUrl(string $providerId, string $mode = 'login'): string
    {
        $provider = self::provider($providerId);
        if ($provider === null) {
            throw new \InvalidArgumentException('Unknown OpenVK provider');
        }

        $_SESSION['ovk_provider'] = $providerId;
        $_SESSION['ovk_mode'] = $mode === 'link' ? 'link' : 'login';
        $_SESSION['ovk_state'] = GenerateRandomStr::gen_uuid();
        $_SESSION['ovk_return'] = (string) ($_GET['return'] ?? $_GET['ref'] ?? '/');

        return $provider['domain'] . '/authorize?' . http_build_query([
            'client_name' => self::clientName(),
            'redirect_uri' => self::redirectUri(),
            'display' => 'page',
            'response_type' => self::responseType(),
            'revoke' => 0,
            'state' => $_SESSION['ovk_state'],
        ]);
    }

    public static function complete(string $accessToken, ?int $reportedUserId = null): array
    {
        $providerId = (string) ($_SESSION['ovk_provider'] ?? '');
        $mode = (string) ($_SESSION['ovk_mode'] ?? 'login');
        $returnUrl = (string) ($_SESSION['ovk_return'] ?? '/');
        $provider = self::provider($providerId);

        if ($provider === null) {
            return self::error('Провайдер OpenVK не выбран. Повторите вход.');
        }

        if ($accessToken === '') {
            return self::error('Токен OpenVK не получен.');
        }

        $profile = self::fetchProfile($provider['domain'], $accessToken);
        if ($profile === null) {
            return self::error('Не удалось проверить токен OpenVK.');
        }

        $ovkUserId = (int) ($profile['id'] ?? 0);
        if ($ovkUserId <= 0) {
            return self::error('OpenVK не вернул ID пользователя.');
        }

        if ($reportedUserId !== null && $reportedUserId > 0 && $reportedUserId !== $ovkUserId) {
            return self::error('ID пользователя OpenVK не совпадает с токеном.');
        }

        $linkPayload = [
            'id' => $ovkUserId,
            'domain' => $provider['domain'],
            'label' => $provider['label'],
            'screen_name' => (string) ($profile['screen_name'] ?? $profile['domain'] ?? ''),
            'first_name' => (string) ($profile['first_name'] ?? ''),
            'last_name' => (string) ($profile['last_name'] ?? ''),
            'photo' => (string) ($profile['photo_200'] ?? $profile['photo_100'] ?? ''),
            'linked_at' => time(),
        ];

        $existingUserId = self::findUserIdByLink($providerId, $ovkUserId);
        $currentUserId = Auth::userid();

        if ($mode === 'link') {
            if ($currentUserId <= 0) {
                return self::error('Для привязки нужно войти в аккаунт галереи.');
            }
            if ($existingUserId !== null && $existingUserId !== $currentUserId) {
                return self::error('Этот профиль OpenVK уже привязан к другому аккаунту.');
            }

            self::attachLink($currentUserId, $providerId, $linkPayload);
            self::clearOvkSession();

            return [
                'errorcode' => 0,
                'error' => 0,
                'mode' => 'link',
                'redirect' => '/lk/profile?type=OpenVK&linked=1',
            ];
        }

        if ($existingUserId !== null) {
            AuthSession::establish($existingUserId);
            self::attachLink($existingUserId, $providerId, $linkPayload);
            self::clearOvkSession();

            return [
                'errorcode' => 0,
                'error' => 0,
                'mode' => 'login',
                'redirect' => $returnUrl !== '' ? $returnUrl : '/',
            ];
        }

        if (empty(NGALLERY['root']['openvk']['auto_register'])) {
            return self::error('Аккаунт не найден. Войдите обычным способом и привяжите OpenVK в профиле.');
        }

        $newUserId = self::createUserFromProfile($providerId, $linkPayload);
        AuthSession::establish($newUserId);
        self::clearOvkSession();

        return [
            'errorcode' => 0,
            'error' => 0,
            'mode' => 'register',
            'redirect' => $returnUrl !== '' ? $returnUrl : '/',
        ];
    }

    public static function unlink(int $userId, string $providerId): array
    {
        $provider = self::provider($providerId);
        if ($provider === null) {
            return self::error('Неизвестный провайдер.');
        }

        $rows = DB::query('SELECT content FROM users WHERE id = :id', [':id' => $userId]);
        if (empty($rows)) {
            return self::error('Пользователь не найден.');
        }

        $content = json_decode((string) $rows[0]['content'], true);
        if (!is_array($content)) {
            $content = [];
        }

        if (empty($content['openvk'][$providerId])) {
            return self::error('Привязка не найдена.');
        }

        unset($content['openvk'][$providerId]);
        DB::query('UPDATE users SET content = :content WHERE id = :id', [
            ':content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            ':id' => $userId,
        ]);

        return ['errorcode' => 0, 'error' => 0];
    }

    /** @return array<string, array<string, mixed>> */
    public static function linksForUser(int $userId): array
    {
        $rows = DB::query('SELECT content FROM users WHERE id = :id', [':id' => $userId]);
        if (empty($rows)) {
            return [];
        }

        $content = json_decode((string) $rows[0]['content'], true);
        if (!is_array($content) || empty($content['openvk']) || !is_array($content['openvk'])) {
            return [];
        }

        return $content['openvk'];
    }

    private static function fetchProfile(string $domain, string $accessToken): ?array
    {
        $response = self::apiRequest($domain, 'Account.getProfileInfo', $accessToken);
        if (isset($response['response']) && is_array($response['response'])) {
            return $response['response'];
        }

        $userResponse = self::apiRequest($domain, 'Users.get', $accessToken, [
            'user_ids' => '0',
            'fields' => 'photo_200,screen_name,domain',
        ]);
        if (isset($userResponse['response'][0]) && is_array($userResponse['response'][0])) {
            return $userResponse['response'][0];
        }

        return null;
    }

    private static function apiRequest(string $domain, string $method, string $accessToken, array $params = []): ?array
    {
        $query = array_merge($params, ['access_token' => $accessToken]);
        $url = rtrim($domain, '/') . '/method/' . $method . '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'NativeGallery/OpenVKAuth',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        if ($body === false || $body === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || isset($decoded['error'])) {
            return null;
        }

        return $decoded;
    }

    private static function findUserIdByLink(string $providerId, int $ovkUserId): ?int
    {
        $safeProvider = preg_replace('/[^a-zA-Z0-9_]/', '', $providerId);
        if ($safeProvider === '') {
            return null;
        }

        $rows = DB::query(
            "SELECT id FROM users
             WHERE JSON_UNQUOTE(JSON_EXTRACT(content, '$.openvk.{$safeProvider}.id')) = :ovk_id",
            [':ovk_id' => (string) $ovkUserId]
        );

        if (empty($rows)) {
            return null;
        }

        return (int) $rows[0]['id'];
    }

    private static function attachLink(int $userId, string $providerId, array $linkPayload): void
    {
        $rows = DB::query('SELECT content, photourl FROM users WHERE id = :id', [':id' => $userId]);
        if (empty($rows)) {
            return;
        }

        $content = json_decode((string) $rows[0]['content'], true);
        if (!is_array($content)) {
            $content = [];
        }
        if (empty($content['openvk']) || !is_array($content['openvk'])) {
            $content['openvk'] = [];
        }

        $content['openvk'][$providerId] = $linkPayload;

        $updates = [':content' => json_encode($content, JSON_UNESCAPED_UNICODE), ':id' => $userId];
        $sql = 'UPDATE users SET content = :content';

        $photo = (string) ($linkPayload['photo'] ?? '');
        $currentPhoto = (string) ($rows[0]['photourl'] ?? '');
        if ($photo !== '' && ($currentPhoto === '' || $currentPhoto === '/static/img/avatar.png')) {
            $sql .= ', photourl = :photourl';
            $updates[':photourl'] = $photo;
        }

        $sql .= ' WHERE id = :id';
        DB::query($sql, $updates);
    }

    private static function createUserFromProfile(string $providerId, array $link): int
    {
        $baseName = trim((string) ($link['screen_name'] ?? ''));
        if ($baseName === '') {
            $baseName = trim(((string) ($link['first_name'] ?? '')) . ' ' . ((string) ($link['last_name'] ?? '')));
        }
        if ($baseName === '') {
            $baseName = 'ovk_' . (int) $link['id'];
        }

        $username = self::uniqueUsername(preg_replace('/[^\p{L}\p{N}_\-\.]/u', '', $baseName) ?: ('ovk_' . (int) $link['id']));
        $email = 'ovk+' . $providerId . '_' . (int) $link['id'] . '@openid.local';
        $photo = (string) ($link['photo'] ?? '/static/img/avatar.png');
        if ($photo === '') {
            $photo = '/static/img/avatar.png';
        }

        $content = [
            'route' => 'NONE',
            'regdate' => time(),
            'auth' => 'openvk',
            'openvk' => [
                $providerId => $link,
            ],
        ];

        DB::query(
            'INSERT INTO users (id, username, email, password, photourl, uploadindex, online, admin, status, content)
             VALUES (\'0\', :username, :email, :password, :photourl, 5, :online, 0, 0, :content)',
            [
                ':username' => $username,
                ':email' => $email,
                ':password' => password_hash(GenerateRandomStr::gen_uuid(), PASSWORD_BCRYPT),
                ':photourl' => $photo,
                ':online' => time(),
                ':content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            ]
        );

        $rows = DB::query('SELECT id FROM users WHERE email = :email', [':email' => $email]);
        return (int) ($rows[0]['id'] ?? 0);
    }

    private static function uniqueUsername(string $base): string
    {
        $base = mb_substr(trim($base), 0, 20);
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $suffix = 1;
        while (DB::query('SELECT id FROM users WHERE LOWER(username) = LOWER(:u)', [':u' => $candidate])) {
            $suffix++;
            $candidate = mb_substr($base, 0, 16) . $suffix;
        }

        return $candidate;
    }

    private static function clearOvkSession(): void
    {
        unset($_SESSION['ovk_provider'], $_SESSION['ovk_mode'], $_SESSION['ovk_state'], $_SESSION['ovk_return']);
    }

    private static function error(string $message): array
    {
        self::clearOvkSession();
        return [
            'errorcode' => 1,
            'error' => 1,
            'message' => $message,
            'redirect' => '/login?ovk_error=' . rawurlencode($message),
        ];
    }
}