<?php

namespace App\Services;

class OpenVKAuth
{
    private static ?string $lastApiError = null;

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
            if (array_key_exists('enabled', $provider) && !$provider['enabled']) {
                continue;
            }
            $domain = rtrim((string) $provider['domain'], '/');
            $apiDomain = rtrim((string) ($provider['api_domain'] ?? self::defaultApiDomain($domain)), '/');
            $resolved[$id] = array_merge([
                'id' => (string) $id,
                'label' => (string) ($provider['label'] ?? $id),
                'domain' => $domain,
                'api_domain' => $apiDomain,
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

        $returnUrl = (string) ($_GET['return'] ?? $_GET['ref'] ?? '/');
        $state = self::buildState($providerId, $mode === 'link' ? 'link' : 'login', $returnUrl);

        $_SESSION['ovk_provider'] = $providerId;
        $_SESSION['ovk_mode'] = $mode === 'link' ? 'link' : 'login';
        $_SESSION['ovk_state'] = $state;
        $_SESSION['ovk_return'] = $returnUrl;

        return $provider['domain'] . '/authorize?' . http_build_query([
            'client_name' => self::clientName(),
            'redirect_uri' => self::redirectUri(),
            'display' => 'page',
            'response_type' => self::responseType(),
            'revoke' => 0,
            'state' => $state,
        ]);
    }

    public static function restoreContext(?string $state): void
    {
        if (!empty($_SESSION['ovk_provider'])) {
            return;
        }

        $parsed = self::parseState($state);
        if ($parsed === null) {
            return;
        }

        $_SESSION['ovk_provider'] = $parsed['provider'];
        $_SESSION['ovk_mode'] = $parsed['mode'];
        $_SESSION['ovk_return'] = $parsed['return'];
    }

    public static function extractAccessToken(): string
    {
        if (!empty($_GET['access_token'])) {
            return trim((string) $_GET['access_token']);
        }
        if (!empty($_GET['token'])) {
            return trim((string) $_GET['token']);
        }

        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if (preg_match('/(?:^|&)access_token=([^&]*)/', $query, $matches)) {
            return trim(rawurldecode($matches[1]));
        }

        return '';
    }

    public static function complete(string $accessToken, ?int $reportedUserId = null, ?string $state = null): array
    {
        self::restoreContext($state);
        self::$lastApiError = null;

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

        $apiDomain = (string) ($provider['api_domain'] ?? $provider['domain']);
        $profile = self::fetchProfile($apiDomain, $accessToken, $reportedUserId);
        if ($profile === null) {
            $message = 'Не удалось проверить токен OpenVK.';
            if (!empty(NGALLERY['root']['debug']) && self::$lastApiError) {
                $message .= ' (' . self::$lastApiError . ')';
            }
            return self::error($message);
        }

        $ovkUserId = (int) ($profile['id'] ?? 0);
        if ($ovkUserId <= 0) {
            return self::error('OpenVK не вернул ID пользователя.');
        }

        if ($reportedUserId !== null && $reportedUserId > 0 && $reportedUserId !== $ovkUserId) {
            return self::error('ID пользователя OpenVK не совпадает с токеном.');
        }

        $screenName = (string) ($profile['screen_name'] ?? '');
        $ovkDomain = (string) ($profile['domain'] ?? '');
        if ($screenName === '' && $ovkDomain !== '') {
            $screenName = $ovkDomain;
        }

        $linkPayload = [
            'id' => $ovkUserId,
            'domain' => $provider['domain'],
            'label' => $provider['label'],
            'screen_name' => $screenName,
            'ovk_domain' => $ovkDomain,
            'first_name' => (string) ($profile['first_name'] ?? ''),
            'last_name' => (string) ($profile['last_name'] ?? ''),
            'photo' => (string) ($profile['photo_200'] ?? $profile['photo_100'] ?? ''),
            'linked_at' => time(),
        ];
        $linkPayload['profile_url'] = self::profileUrl($linkPayload);

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

    /** @return list<array{provider: array<string, mixed>, link: array<string, mixed>, url: string, name: string}> */
    public static function linkedProfilesForUser(int $userId): array
    {
        $links = self::linksForUser($userId);
        $providers = self::providers();
        $result = [];

        foreach ($links as $providerId => $link) {
            if (!is_array($link) || !isset($providers[$providerId])) {
                continue;
            }

            $result[] = [
                'provider' => $providers[$providerId],
                'link' => $link,
                'url' => self::profileUrl($link),
                'name' => self::profileDisplayName($link),
            ];
        }

        return $result;
    }

    public static function profileUrl(array $link): string
    {
        if (!empty($link['profile_url'])) {
            return (string) $link['profile_url'];
        }

        $site = rtrim((string) ($link['domain'] ?? ''), '/');
        $id = (int) ($link['id'] ?? 0);
        $slug = trim((string) ($link['ovk_domain'] ?? $link['screen_name'] ?? ''));

        if ($slug !== '' && !preg_match('/^id\d+$/i', $slug)) {
            return $site . '/' . $slug;
        }

        if ($id > 0) {
            return $site . '/id' . $id;
        }

        return $site;
    }

    public static function profileDisplayName(array $link): string
    {
        $screenName = trim((string) ($link['screen_name'] ?? ''));
        if ($screenName !== '') {
            return $screenName;
        }

        $fullName = trim(((string) ($link['first_name'] ?? '')) . ' ' . ((string) ($link['last_name'] ?? '')));
        if ($fullName !== '') {
            return $fullName;
        }

        $id = (int) ($link['id'] ?? 0);
        return $id > 0 ? 'id' . $id : 'Профиль';
    }

    private static function defaultApiDomain(string $domain): string
    {
        if (preg_match('#^https?://(www\.)?openvk\.org$#i', $domain)) {
            return 'https://api.openvk.org';
        }

        return $domain;
    }

    private static function buildState(string $providerId, string $mode, string $returnUrl): string
    {
        $payload = $providerId . '|' . $mode . '|' . base64_encode($returnUrl);
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    private static function parseState(?string $state): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        $decoded = base64_decode(strtr($state, '-_', '+/'), true);
        if ($decoded === false) {
            return null;
        }

        $parts = explode('|', $decoded, 3);
        if (count($parts) < 2 || self::provider($parts[0]) === null) {
            return null;
        }

        return [
            'provider' => $parts[0],
            'mode' => $parts[1] === 'link' ? 'link' : 'login',
            'return' => isset($parts[2]) ? (string) base64_decode($parts[2]) : '/',
        ];
    }

    private static function fetchProfile(string $apiDomain, string $accessToken, ?int $userId = null): ?array
    {
        foreach (['Account.getProfileInfo', 'account.getProfileInfo'] as $method) {
            $response = self::apiRequest($apiDomain, $method, $accessToken);
            if (isset($response['response']) && is_array($response['response'])) {
                $profile = $response['response'];
                if (!empty($profile['id']) || !empty($profile['first_name'])) {
                    if (empty($profile['id']) && $userId > 0) {
                        $profile['id'] = $userId;
                    }
                    return $profile;
                }
            }
        }

        $userIds = [];
        if ($userId > 0) {
            $userIds[] = (string) $userId;
        }
        $userIds[] = '0';

        foreach ($userIds as $uid) {
            foreach (['Users.get', 'users.get'] as $method) {
                $userResponse = self::apiRequest($apiDomain, $method, $accessToken, [
                    'user_ids' => $uid,
                    'fields' => 'photo_200,photo_100,screen_name,domain,first_name,last_name',
                ]);
                if (isset($userResponse['response'][0]) && is_array($userResponse['response'][0])) {
                    return $userResponse['response'][0];
                }
            }
        }

        return null;
    }

    private static function apiRequest(string $domain, string $method, string $accessToken, array $params = []): ?array
    {
        $query = array_merge($params, [
            'access_token' => $accessToken,
            'v' => '5.126',
        ]);
        $url = rtrim($domain, '/') . '/method/' . $method . '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; NativeGallery/1.4; +https://github.com/Lanmikeman/nativegallery)',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false || $body === '') {
            self::$lastApiError = $curlError !== '' ? $curlError : 'пустой ответ API';
            return null;
        }

        if (str_starts_with(ltrim($body), '<')) {
            self::$lastApiError = 'API вернул HTML вместо JSON (проверьте api_domain)';
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            self::$lastApiError = 'некорректный JSON от API';
            return null;
        }

        if (isset($decoded['error_code']) && (int) $decoded['error_code'] !== 0) {
            self::$lastApiError = (string) ($decoded['error_msg'] ?? 'ошибка API #' . $decoded['error_code']);
            return null;
        }

        if (isset($decoded['error'])) {
            $error = $decoded['error'];
            if (is_array($error)) {
                self::$lastApiError = (string) ($error['error_msg'] ?? $error['message'] ?? 'ошибка API');
            } else {
                self::$lastApiError = 'ошибка API #' . (string) ($decoded['error_code'] ?? '');
            }
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