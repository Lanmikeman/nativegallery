<?php

namespace App\Services;

class AdminNav
{
    public static function sections(string $nonr = '', string $nonrE = ''): array
    {
        $sections = [
            'Управление' => [
                ['type' => 'General', 'href' => '/admin', 'icon' => 'fa-users-cog', 'label' => 'Пользователи'],
                ['type' => 'AuthSettings', 'href' => '/admin?type=AuthSettings', 'icon' => 'fa-key', 'label' => 'Авторизация'],
            ],
            'Контент' => [
                ['type' => 'Photo', 'href' => '/admin?type=Photo', 'icon' => 'fa-camera', 'label' => 'Фотографии', 'badge' => $nonr],
                ['type' => 'Galleries', 'href' => '/admin?type=Galleries', 'icon' => 'fa-images', 'label' => 'Галереи'],
                ['type' => 'News', 'href' => '/admin?type=News', 'icon' => 'fa-bullhorn', 'label' => 'Новости сайта'],
                ['type' => 'Chronology', 'href' => '/admin?type=Chronology', 'icon' => 'fa-clock', 'label' => 'Хронология'],
                ['type' => 'Links', 'href' => '/admin?type=Links', 'icon' => 'fa-link', 'label' => 'Ссылки'],
                ['type' => 'MusicSettings', 'href' => '/admin?type=MusicSettings', 'icon' => 'fa-music', 'label' => 'Музыка'],
                ['type' => 'RadioStations', 'href' => '/admin?type=RadioStations', 'icon' => 'fa-broadcast-tower', 'label' => 'Радиостанции'],
                ['type' => 'Contests', 'href' => '/admin?type=Contests', 'icon' => 'fa-trophy', 'label' => 'Фотоконкурсы'],
                ['type' => 'Pages', 'href' => '/admin?type=Pages', 'icon' => 'fa-file-alt', 'label' => 'Страницы'],
            ],
            'Справочники' => [
                ['type' => 'Entities', 'href' => '/admin?type=Entities', 'icon' => 'fa-cubes', 'label' => 'Сущности'],
                ['type' => 'Models', 'href' => '/admin?type=Models', 'icon' => 'fa-database', 'label' => 'База моделей', 'badge' => $nonrE],
                ['type' => 'GeoDB', 'href' => '/admin?type=GeoDB', 'icon' => 'fa-globe', 'label' => 'GeoDB'],
            ],
            'Система' => [
                ['type' => 'Settings', 'href' => '/admin?type=Settings', 'icon' => 'fa-cog', 'label' => 'Настройки'],
            ],
        ];

        if (AdminAccess::isOwner()) {
            $sections['Система'][] = [
                'type' => 'ServerSettings',
                'href' => '/admin?type=ServerSettings',
                'icon' => 'fa-server',
                'label' => 'Сервер',
                'badge' => '<span class="badge text-bg-danger admin-nav__badge">OWNER</span>',
            ];
        }

        return $sections;
    }

    public static function label(string $type): string
    {
        foreach (self::sections() as $items) {
            foreach ($items as $item) {
                if ($item['type'] === $type) {
                    return $item['label'];
                }
            }
        }

        return match ($type) {
            'UserEdit' => 'Редактирование пользователя',
            'EntityEdit', 'EntityCreate' => 'Сущности',
            'ModelsCreate' => 'База моделей',
            'PageCreate' => 'Страницы',
            default => $type,
        };
    }
}