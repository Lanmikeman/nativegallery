<?php

namespace App\Controllers\Api\Admin\Settings;

use App\Services\{AdminAccess, GalleryConfig, Json};

class AuthProvider
{
    public function __construct()
    {
        if (!AdminAccess::requireFullAdmin()) {
            return;
        }

        $uri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        $parts = explode('/', (string) $uri);
        $providerId = '';
        $action = '';

        // /api/admin/settings/auth/providers/{id}/delete
        if (($parts[6] ?? '') === 'providers') {
            $providerId = (string) ($parts[7] ?? '');
            $action = (string) ($parts[8] ?? '');
        }

        if ($providerId === '' && $action === '') {
            $this->create();
            return;
        }

        if ($action === 'delete') {
            $this->delete($providerId);
            return;
        }

        $this->update($providerId);
    }

    private function create(): void
    {
        $result = GalleryConfig::saveCustomProvider([
            'provider_id' => $_POST['provider_id'] ?? '',
            'label' => $_POST['label'] ?? '',
            'domain' => $_POST['domain'] ?? '',
            'api_domain' => $_POST['api_domain'] ?? '',
            'accent' => $_POST['accent'] ?? '',
            'icon' => $_POST['icon'] ?? '',
            'enabled' => $_POST['enabled'] ?? true,
        ]);

        $this->respond($result);
    }

    private function update(string $providerId): void
    {
        if (!GalleryConfig::isCustomProvider($providerId)) {
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Редактировать можно только дополнительные инстансы',
            ]);
            return;
        }

        $result = GalleryConfig::saveCustomProvider([
            'label' => $_POST['label'] ?? '',
            'domain' => $_POST['domain'] ?? '',
            'api_domain' => $_POST['api_domain'] ?? '',
            'accent' => $_POST['accent'] ?? '',
            'icon' => $_POST['icon'] ?? '',
            'enabled' => $_POST['enabled'] ?? true,
        ], $providerId);

        $this->respond($result);
    }

    private function delete(string $providerId): void
    {
        $result = GalleryConfig::deleteCustomProvider($providerId);
        $this->respond($result);
    }

    /** @param array{ok: bool, message: string, id?: string} $result */
    private function respond(array $result): void
    {
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
            'id' => $result['id'] ?? null,
        ]);
    }
}