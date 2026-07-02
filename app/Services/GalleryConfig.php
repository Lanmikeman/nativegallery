<?php

namespace App\Services;

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
                if (!is_array($providerOverlay) || !array_key_exists('enabled', $providerOverlay)) {
                    continue;
                }
                if (!isset($ngallery['root']['openvk']['providers'][$providerId])) {
                    continue;
                }
                if (!is_array($ngallery['root']['openvk']['providers'][$providerId])) {
                    $ngallery['root']['openvk']['providers'][$providerId] = [];
                }
                $ngallery['root']['openvk']['providers'][$providerId]['enabled'] = (bool) $providerOverlay['enabled'];
            }
        }

        return $ngallery;
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
                if ($providerId === '') {
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
}