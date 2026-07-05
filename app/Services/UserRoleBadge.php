<?php

namespace App\Services;

class UserRoleBadge
{
    public static function starHtml(int $admin, int $width = 32): string
    {
        return match ($admin) {
            AdminAccess::ROLE_OWNER => '<img width="' . $width . '" src="/static/img/star.png" class="role-star role-star--owner" alt="" title="Владелец сервера">',
            AdminAccess::ROLE_ADMIN => '<img width="' . $width . '" src="/static/img/star.png" class="role-star role-star--admin" alt="" title="Администратор сервера">',
            default => '',
        };
    }

    public static function roleTitle(int $admin): string
    {
        return match ($admin) {
            AdminAccess::ROLE_OWNER => 'Владелец сервера',
            AdminAccess::ROLE_ADMIN => 'Администратор сервера',
            AdminAccess::ROLE_PHOTO_MOD => 'Фотомодератор',
            AdminAccess::ROLE_MOD => 'Модератор',
            default => '',
        };
    }
}