<?php

namespace App\Services;

use App\Models\User;

class AdminAccess
{
    public static function isFullAdmin(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::userid();
        if ($userId <= 0) {
            return false;
        }

        $user = new User($userId);
        return (int) $user->i('admin') === 1;
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

    /** @return array{0: int, 1: string} */
    public static function roleLabel(int $admin): array
    {
        return match ($admin) {
            1 => ['Администратор', 'danger'],
            2 => ['Фотомодератор', 'warning'],
            3 => ['Модератор', 'info'],
            default => ['Пользователь', 'secondary'],
        };
    }
}