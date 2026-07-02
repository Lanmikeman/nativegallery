<?php

namespace App\Services;

use App\Models\User;

class SitePage
{
    public static function hasMetaColumns(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $rows = DB::query(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'pages\' AND COLUMN_NAME = \'updated_at\''
        );
        $cached = $rows !== [];

        return $cached;
    }

    /** @return array<string, mixed>|null */
    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $rows = DB::query('SELECT * FROM pages WHERE id = :id', [':id' => $id]);
        return $rows[0] ?? null;
    }

    public static function editNoticeHtml(array $row): string
    {
        $updatedAt = (int) ($row['updated_at'] ?? 0);
        $updatedBy = (int) ($row['updated_by'] ?? 0);
        if ($updatedAt <= 0) {
            return '';
        }

        $editorName = self::editorName($updatedBy);

        return '<p class="sm site-page__meta">'
            . '<i>Редакция от ' . htmlspecialchars(Date::formatDate($updatedAt))
            . ($updatedBy > 0 ? ' — ' . htmlspecialchars($editorName) : '')
            . '</i></p>';
    }

    public static function editorName(int $userId): string
    {
        return SiteNews::editorName($userId);
    }
}