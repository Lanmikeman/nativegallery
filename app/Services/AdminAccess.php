<?php

namespace App\Services;

use App\Models\User;

class AdminAccess
{
    public const ROLE_OWNER = 4;
    public const ROLE_ADMIN = 1;
    public const ROLE_PHOTO_MOD = 2;
    public const ROLE_MOD = 3;

    public static function isOwner(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::userid();
        if ($userId <= 0) {
            return false;
        }

        $user = new User($userId);
        return (int) $user->i('admin') === self::ROLE_OWNER;
    }

    public static function isFullAdmin(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::userid();
        if ($userId <= 0) {
            return false;
        }

        $user = new User($userId);
        $admin = (int) $user->i('admin');

        return $admin === self::ROLE_ADMIN || $admin === self::ROLE_OWNER;
    }

    public static function requireFullAdmin(): bool
    {
        if (!self::isFullAdmin()) {
            header('Content-Type: application/json; charset=utf-8');
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Требуются права администратора',
            ]);
            return false;
        }

        return true;
    }

    public static function requireOwner(): bool
    {
        if (!self::isOwner()) {
            header('Content-Type: application/json; charset=utf-8');
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Требуются права владельца сервера',
            ]);
            return false;
        }

        return true;
    }

    /** @return array{0: string, 1: string} */
    public static function roleLabel(int $admin): array
    {
        return match ($admin) {
            self::ROLE_OWNER => ['Владелец', 'dark'],
            self::ROLE_ADMIN => ['Администратор', 'danger'],
            self::ROLE_PHOTO_MOD => ['Фотомодератор', 'warning'],
            self::ROLE_MOD => ['Модератор', 'info'],
            default => ['Пользователь', 'secondary'],
        };
    }
}