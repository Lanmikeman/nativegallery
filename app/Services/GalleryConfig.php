<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class GalleryConfig
{
    public static function yamlPath(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/ngallery.yaml';
    }

    public static function overlayPath(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/storage/auth-settings.json';
    }

    /** @return array<string, mixed> */
    public static function loadYamlRoot(): array
    {
        $path = self::yamlPath();
        if (!is_readable($path)) {
            return [];
        }

        $parsed = Yaml::parseFile($path);
        if (!is_array($parsed) || !isset($parsed['ngallery']['root']) || !is_array($parsed['ngallery']['root'])) {
            return [];
        }

        return $parsed['ngallery']['root'];
    }

    /** @return array<string, mixed> */
    public static function loadOverlay(): array
    {
        $path = self::overlayPath();
        if (!is_readable($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $ngallery
     * @return array<string, mixed>
     */
    public static function applyAuthOverlay(array $ngallery): array
    {
        $overlay = self::loadOverlay();
        if ($overlay === []) {
            return $ngallery;
        }

        if (!isset($ngallery['root']) || !is_array($ngallery['root'])) {
            $ngallery['root'] = [];
        }

        if (isset($overlay['registration']['access']['public'])) {
            if (!isset($ngallery['root']['registration']) || !is_array($ngallery['root']['registration'])) {
                $ngallery['root']['registration'] = [];
            }
            if (!isset($ngallery['root']['registration']['access']) || !is_array($ngallery['root']['registration']['access'])) {
                $ngallery['root']['registration']['access'] = [];
            }
            $ngallery['root']['registration']['access']['public'] = (bool) $overlay['registration']['access']['public'];
        }

        if (!isset($ngallery['root']['openvk']) || !is_array($ngallery['root']['openvk'])) {
            $ngallery['root']['openvk'] = [];
        }

        if (array_key_exists('enabled', $overlay['openvk'] ?? null)) {
            $ngallery['root']['openvk']['enabled'] = (bool) $overlay['openvk']['enabled'];
        }

        if (array_key_exists('auto_register', $overlay['openvk'] ?? null)) {
            $ngallery['root']['openvk']['auto_register'] = (bool) $overlay['openvk']['auto_register'];
        }

        if (!empty($overlay['openvk']['providers']) && is_array($overlay['openvk']['providers'])) {
            if (!isset($ngallery['root']['openvk']['providers']) || !is_array($ngallery['root']['openvk']['providers'])) {
                $ngallery['root']['openvk']['providers'] = [];
            }

            foreach ($overlay['openvk']['providers'] as $providerId => $providerOverlay) {
                if (!is_array($providerOverlay)) {
                    continue;
                }

                if (!empty($providerOverlay['removed'])) {
                    unset($ngallery['root']['openvk']['providers'][$providerId]);
                    continue;
                }

                if (!empty($providerOverlay['custom']) || !empty($providerOverlay['override'])) {
                    $ngallery['root']['openvk']['providers'][$providerId] = self::providerForRuntime($providerOverlay);
                    continue;
                }

                if (!isset($ngallery['root']['openvk']['providers'][$providerId])) {
                    continue;
                }
                if (!is_array($ngallery['root']['openvk']['providers'][$providerId])) {
                    $ngallery['root']['openvk']['providers'][$providerId] = [];
                }
                if (array_key_exists('enabled', $providerOverlay)) {
                    $ngallery['root']['openvk']['providers'][$providerId]['enabled'] = (bool) $providerOverlay['enabled'];
                }
            }
        }

        return $ngallery;
    }

    /** @return array<string, array<string, mixed>> */
    public static function listProvidersForAdmin(): array
    {
        $yamlRoot = self::loadYamlRoot();
        $yamlProviders = $yamlRoot['openvk']['providers'] ?? [];
        if (!is_array($yamlProviders)) {
            $yamlProviders = [];
        }

        $overlay = self::loadOverlay();
        $overlayProviders = $overlay['openvk']['providers'] ?? [];
        if (!is_array($overlayProviders)) {
            $overlayProviders = [];
        }

        $result = [];

        foreach ($yamlProviders as $id => $provider) {
            $id = (string) $id;
            if (!is_array($provider)) {
                continue;
            }
            if (self::isProviderRemovedInOverlay($id, $overlayProviders)) {
                continue;
            }

            $merged = self::mergeProviderDefinition($provider, $overlayProviders[$id] ?? null);
            $result[$id] = self::formatProviderRow($id, $merged, 'yaml', self::providerEnabled($merged, $overlayProviders[$id] ?? null));
        }

        foreach ($overlayProviders as $id => $provider) {
            $id = (string) $id;
            if (!is_array($provider) || empty($provider['custom']) || isset($result[$id])) {
                continue;
            }
            if (!empty($provider['removed'])) {
                continue;
            }

            $result[$id] = self::formatProviderRow($id, $provider, 'custom', self::providerEnabled($provider, null));
        }

        return $result;
    }

    public static function providerExists(string $providerId): bool
    {
        return isset(self::listProvidersForAdmin()[$providerId]);
    }

    /**
     * @param array{provider_id?: string, label?: string, domain?: string, api_domain?: string, accent?: string, icon?: string, enabled?: bool} $input
     * @return array{ok: bool, message: string, id?: string}
     */
    public static function saveProvider(array $input, ?string $existingId = null): array
    {
        $normalized = self::normalizeProviderInput($input, $existingId);
        if (!$normalized['ok']) {
            return ['ok' => false, 'message' => $normalized['message']];
        }

        /** @var string $id */
        $id = $normalized['id'];
        /** @var array<string, mixed> $provider */
        $provider = $normalized['provider'];

        if ($existingId === null && (self::providerExistsInYaml($id) || self::isCustomInOverlay($id))) {
            return ['ok' => false, 'message' => 'Инстанс с таким ID уже существует'];
        }

        if ($existingId !== null && !self::providerExists($existingId)) {
            return ['ok' => false, 'message' => 'Инстанс не найден'];
        }

        $overlay = self::loadOverlay();
        if (!isset($overlay['openvk']) || !is_array($overlay['openvk'])) {
            $overlay['openvk'] = [];
        }
        if (!isset($overlay['openvk']['providers']) || !is_array($overlay['openvk']['providers'])) {
            $overlay['openvk']['providers'] = [];
        }

        $saveId = $existingId ?? $id;
        if ($existingId !== null && self::providerExistsInYaml($existingId)) {
            $provider['override'] = true;
            unset($provider['custom']);
        } else {
            $provider['custom'] = true;
            unset($provider['override']);
        }
        unset($provider['removed']);

        $overlay['openvk']['providers'][$saveId] = $provider;

        if (!self::saveOverlay($overlay)) {
            return ['ok' => false, 'message' => self::writableErrorMessage()];
        }

        if ($existingId !== null) {
            self::refreshUserOpenVKLinksForProvider($saveId, self::providerForRuntime($provider));
        }

        return ['ok' => true, 'message' => 'Инстанс сохранён', 'id' => $saveId];
    }

    /** @return array{ok: bool, message: string} */
    public static function deleteProvider(string $providerId, ?string $replaceWithId = null): array
    {
        $providerId = self::normalizeProviderId($providerId);
        if ($providerId === null) {
            return ['ok' => false, 'message' => 'Некорректный ID инстанса'];
        }

        if (!self::providerExists($providerId)) {
            return ['ok' => false, 'message' => 'Инстанс не найден'];
        }

        $replaceWithId = $replaceWithId !== null && $replaceWithId !== ''
            ? self::normalizeProviderId($replaceWithId)
            : null;

        if ($replaceWithId !== null) {
            if ($replaceWithId === $providerId) {
                return ['ok' => false, 'message' => 'Нельзя заменить инстанс сам на себя'];
            }
            if (!self::providerExists($replaceWithId)) {
                return ['ok' => false, 'message' => 'Инстанс для замены не найден'];
            }
            self::migrateUserOpenVKLinks($providerId, $replaceWithId);
        }

        $overlay = self::loadOverlay();
        if (!isset($overlay['openvk']) || !is_array($overlay['openvk'])) {
            $overlay['openvk'] = [];
        }
        if (!isset($overlay['openvk']['providers']) || !is_array($overlay['openvk']['providers'])) {
            $overlay['openvk']['providers'] = [];
        }

        if (self::providerExistsInYaml($providerId)) {
            $overlay['openvk']['providers'][$providerId] = ['removed' => true];
        } else {
            unset($overlay['openvk']['providers'][$providerId]);
        }

        if (!self::saveOverlay($overlay)) {
            return ['ok' => false, 'message' => self::writableErrorMessage()];
        }

        $message = 'Инстанс удалён из конфигурации';
        if ($replaceWithId !== null) {
            $message .= ' (привязки пользователей перенесены на «' . $replaceWithId . '»)';
        }

        return ['ok' => true, 'message' => $message];
    }

    /**
     * @param array{
     *   registration_public?: bool,
     *   openvk_enabled?: bool,
     *   openvk_auto_register?: bool,
     *   providers?: array<string, bool>
     * } $settings
     * @return array{ok: bool, message: string}
     */
    public static function updateAuthSettings(array $settings): array
    {
        $overlay = self::loadOverlay();

        if (array_key_exists('registration_public', $settings)) {
            if (!isset($overlay['registration']) || !is_array($overlay['registration'])) {
                $overlay['registration'] = [];
            }
            if (!isset($overlay['registration']['access']) || !is_array($overlay['registration']['access'])) {
                $overlay['registration']['access'] = [];
            }
            $overlay['registration']['access']['public'] = (bool) $settings['registration_public'];
        }

        if (!isset($overlay['openvk']) || !is_array($overlay['openvk'])) {
            $overlay['openvk'] = [];
        }

        if (array_key_exists('openvk_enabled', $settings)) {
            $overlay['openvk']['enabled'] = (bool) $settings['openvk_enabled'];
        }

        if (array_key_exists('openvk_auto_register', $settings)) {
            $overlay['openvk']['auto_register'] = (bool) $settings['openvk_auto_register'];
        }

        if (!empty($settings['providers']) && is_array($settings['providers'])) {
            if (!isset($overlay['openvk']['providers']) || !is_array($overlay['openvk']['providers'])) {
                $overlay['openvk']['providers'] = [];
            }

            foreach ($settings['providers'] as $providerId => $enabled) {
                $providerId = (string) $providerId;
                if ($providerId === '' || !self::providerExists($providerId)) {
                    continue;
                }

                if (!isset($overlay['openvk']['providers'][$providerId]) || !is_array($overlay['openvk']['providers'][$providerId])) {
                    $overlay['openvk']['providers'][$providerId] = [];
                }
                $overlay['openvk']['providers'][$providerId]['enabled'] = (bool) $enabled;
            }
        }

        if (!self::saveOverlay($overlay)) {
            return ['ok' => false, 'message' => self::writableErrorMessage()];
        }

        return ['ok' => true, 'message' => 'Настройки сохранены в storage/auth-settings.json'];
    }

    /** @param array<string, mixed> $runtime */
    private static function refreshUserOpenVKLinksForProvider(string $providerId, array $runtime): void
    {
        $rows = DB::query("SELECT id, content FROM users WHERE content LIKE '%\"openvk\"%'");
        foreach ($rows as $row) {
            $content = json_decode((string) $row['content'], true);
            if (!is_array($content) || empty($content['openvk'][$providerId]) || !is_array($content['openvk'][$providerId])) {
                continue;
            }

            $link = $content['openvk'][$providerId];
            $link['domain'] = (string) $runtime['domain'];
            $link['label'] = (string) $runtime['label'];
            $link['profile_url'] = OpenVKAuth::profileUrl($link);
            $content['openvk'][$providerId] = $link;

            DB::query('UPDATE users SET content = :content WHERE id = :id', [
                ':content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                ':id' => (int) $row['id'],
            ]);
        }
    }

    private static function migrateUserOpenVKLinks(string $fromId, string $toId): void
    {
        $target = self::listProvidersForAdmin()[$toId] ?? null;
        if ($target === null) {
            return;
        }

        $rows = DB::query("SELECT id, content FROM users WHERE content LIKE '%\"openvk\"%'");
        foreach ($rows as $row) {
            $content = json_decode((string) $row['content'], true);
            if (!is_array($content) || empty($content['openvk'][$fromId]) || !is_array($content['openvk'][$fromId])) {
                continue;
            }

            $link = $content['openvk'][$fromId];
            unset($content['openvk'][$fromId]);

            if (empty($content['openvk'][$toId])) {
                $link['domain'] = $target['domain'];
                $link['label'] = $target['label'];
                $link['profile_url'] = OpenVKAuth::profileUrl($link);
                $content['openvk'][$toId] = $link;
            }

            DB::query('UPDATE users SET content = :content WHERE id = :id', [
                ':content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                ':id' => (int) $row['id'],
            ]);
        }
    }

    /** @param array<string, mixed> $overlayProviders */
    private static function isProviderRemovedInOverlay(string $id, array $overlayProviders): bool
    {
        return !empty($overlayProviders[$id]['removed']);
    }

    private static function isCustomInOverlay(string $providerId): bool
    {
        $overlay = self::loadOverlay();
        $provider = $overlay['openvk']['providers'][$providerId] ?? null;
        return is_array($provider) && !empty($provider['custom']) && empty($provider['removed']);
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed>|null $overlay
     * @return array<string, mixed>
     */
    private static function mergeProviderDefinition(array $base, ?array $overlay): array
    {
        if ($overlay === null || !empty($overlay['removed'])) {
            return $base;
        }

        if (empty($overlay['override']) && empty($overlay['custom'])) {
            return $base;
        }

        return array_merge($base, $overlay);
    }

    /**
     * @param array<string, mixed> $provider
     * @param array<string, mixed>|null $overlay
     */
    private static function providerEnabled(array $provider, ?array $overlay): bool
    {
        if ($overlay !== null && array_key_exists('enabled', $overlay)) {
            return (bool) $overlay['enabled'];
        }

        return !array_key_exists('enabled', $provider) || !empty($provider['enabled']);
    }

    /** @param array<string, mixed> $overlay */
    private static function saveOverlay(array $overlay): bool
    {
        $path = self::overlayPath();
        $dir = dirname($path);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $json = json_encode($overlay, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $tmp = $path . '.tmp.' . getmypid();
        if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
            @unlink($tmp);
            return false;
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            return @file_put_contents($path, $json . "\n", LOCK_EX) !== false;
        }

        @chmod($path, 0664);
        return true;
    }

    private static function writableErrorMessage(): string
    {
        return 'Не удалось сохранить настройки. Выдайте права на запись каталогу storage/: '
            . 'chown -R www-data:www-data storage && chmod -R 775 storage';
    }

    private static function providerExistsInYaml(string $providerId): bool
    {
        $yamlRoot = self::loadYamlRoot();
        return isset($yamlRoot['openvk']['providers'][$providerId]);
    }

    /**
     * @param array<string, mixed> $provider
     * @return array<string, mixed>
     */
    private static function providerForRuntime(array $provider): array
    {
        $domain = rtrim((string) ($provider['domain'] ?? ''), '/');
        $apiDomain = trim((string) ($provider['api_domain'] ?? ''));
        if ($apiDomain === '') {
            $apiDomain = $domain;
        }

        return [
            'enabled' => !array_key_exists('enabled', $provider) || !empty($provider['enabled']),
            'label' => (string) ($provider['label'] ?? ''),
            'domain' => $domain,
            'api_domain' => rtrim($apiDomain, '/'),
            'accent' => (string) ($provider['accent'] ?? '#5181b8'),
            'icon' => (string) ($provider['icon'] ?? ($domain . '/assets/packages/static/openvk/img/favicon.ico')),
        ];
    }

    /**
     * @param array<string, mixed> $provider
     * @return array<string, mixed>
     */
    private static function formatProviderRow(string $id, array $provider, string $source, bool $enabled): array
    {
        $runtime = self::providerForRuntime($provider);
        return [
            'id' => $id,
            'source' => $source,
            'enabled' => $enabled,
            'label' => $runtime['label'] !== '' ? $runtime['label'] : $id,
            'domain' => $runtime['domain'],
            'api_domain' => $runtime['api_domain'],
            'accent' => $runtime['accent'],
            'icon' => $runtime['icon'],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, message: string, id?: string, provider?: array<string, mixed>}
     */
    private static function normalizeProviderInput(array $input, ?string $existingId = null): array
    {
        $id = $existingId ?? self::normalizeProviderId((string) ($input['provider_id'] ?? ''));
        if ($id === null || $id === '') {
            $domain = self::normalizeUrl((string) ($input['domain'] ?? ''));
            if ($domain === null) {
                return ['ok' => false, 'message' => 'Укажите корректный домен (https://…)'];
            }
            $id = self::idFromDomain($domain);
        }

        if ($id === null) {
            return ['ok' => false, 'message' => 'ID инстанса: латиница, цифры и _, от 3 до 32 символов'];
        }

        $label = trim((string) ($input['label'] ?? ''));
        if ($label === '' || mb_strlen($label) > 64) {
            return ['ok' => false, 'message' => 'Название инстанса обязательно (до 64 символов)'];
        }

        $domain = self::normalizeUrl((string) ($input['domain'] ?? ''));
        if ($domain === null) {
            return ['ok' => false, 'message' => 'Домен инстанса должен начинаться с https://'];
        }

        $apiDomain = trim((string) ($input['api_domain'] ?? ''));
        if ($apiDomain !== '') {
            $apiDomain = self::normalizeUrl($apiDomain);
            if ($apiDomain === null) {
                return ['ok' => false, 'message' => 'API-домен должен начинаться с https://'];
            }
        }

        $accent = trim((string) ($input['accent'] ?? '#5181b8'));
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
            $accent = '#5181b8';
        }

        $icon = trim((string) ($input['icon'] ?? ''));
        if ($icon !== '' && !preg_match('#^https?://#i', $icon)) {
            return ['ok' => false, 'message' => 'Иконка должна быть URL (https://…)'];
        }
        if ($icon === '') {
            $icon = $domain . '/assets/packages/static/openvk/img/favicon.ico';
        }

        $enabled = !isset($input['enabled']) || filter_var($input['enabled'], FILTER_VALIDATE_BOOLEAN);

        return [
            'ok' => true,
            'message' => '',
            'id' => $id,
            'provider' => [
                'enabled' => $enabled,
                'label' => $label,
                'domain' => $domain,
                'api_domain' => $apiDomain,
                'accent' => $accent,
                'icon' => $icon,
            ],
        ];
    }

    private static function normalizeProviderId(string $id): ?string
    {
        $id = strtolower(trim($id));
        if ($id === '' || !preg_match('/^[a-z][a-z0-9_]{2,31}$/', $id)) {
            return null;
        }

        return $id;
    }

    private static function idFromDomain(string $domain): ?string
    {
        $host = parse_url($domain, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $id = strtolower(preg_replace('/[^a-z0-9]+/', '_', $host) ?? '');
        $id = trim($id, '_');
        if ($id === '') {
            return null;
        }
        if (ctype_digit($id[0])) {
            $id = 'ovk_' . $id;
        }

        return self::normalizeProviderId(mb_substr($id, 0, 32));
    }

    private static function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https://#i', $url)) {
            return null;
        }

        return rtrim($url, '/');
    }
}