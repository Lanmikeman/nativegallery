<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class GalleryConfig
{
    public static function path(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/ngallery.yaml';
    }

    public static function loadFull(): array
    {
        $path = self::path();
        if (!file_exists($path)) {
            return ['ngallery' => ['root' => []]];
        }

        $parsed = Yaml::parseFile($path);
        return is_array($parsed) ? $parsed : ['ngallery' => ['root' => []]];
    }

    public static function saveFull(array $data): bool
    {
        $yaml = Yaml::dump($data, 6, 2);
        return file_put_contents(self::path(), $yaml, LOCK_EX) !== false;
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
        $data = self::loadFull();
        if (!isset($data['ngallery']['root']) || !is_array($data['ngallery']['root'])) {
            $data['ngallery']['root'] = [];
        }

        $root = &$data['ngallery']['root'];

        if (array_key_exists('registration_public', $settings)) {
            if (!isset($root['registration']) || !is_array($root['registration'])) {
                $root['registration'] = [];
            }
            if (!isset($root['registration']['access']) || !is_array($root['registration']['access'])) {
                $root['registration']['access'] = [];
            }
            $root['registration']['access']['public'] = (bool) $settings['registration_public'];
        }

        if (!isset($root['openvk']) || !is_array($root['openvk'])) {
            $root['openvk'] = [];
        }

        if (array_key_exists('openvk_enabled', $settings)) {
            $root['openvk']['enabled'] = (bool) $settings['openvk_enabled'];
        }

        if (array_key_exists('openvk_auto_register', $settings)) {
            $root['openvk']['auto_register'] = (bool) $settings['openvk_auto_register'];
        }

        if (!empty($settings['providers']) && is_array($settings['providers'])) {
            if (!isset($root['openvk']['providers']) || !is_array($root['openvk']['providers'])) {
                $root['openvk']['providers'] = [];
            }

            foreach ($settings['providers'] as $providerId => $enabled) {
                $providerId = (string) $providerId;
                if ($providerId === '' || !isset($root['openvk']['providers'][$providerId])) {
                    continue;
                }
                if (!is_array($root['openvk']['providers'][$providerId])) {
                    $root['openvk']['providers'][$providerId] = [];
                }
                $root['openvk']['providers'][$providerId]['enabled'] = (bool) $enabled;
            }
        }

        if (!self::saveFull($data)) {
            return ['ok' => false, 'message' => 'Не удалось сохранить ngallery.yaml'];
        }

        return ['ok' => true, 'message' => 'Настройки сохранены'];
    }
}