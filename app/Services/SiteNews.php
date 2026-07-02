<?php

namespace App\Services;

use App\Models\User;

class SiteNews
{
    public static function editNoticeHtml(array $row): string
    {
        $editedAt = (int) ($row['edited_at'] ?? 0);
        $editedBy = (int) ($row['edited_by'] ?? 0);
        if ($editedAt <= 0 || $editedBy <= 0) {
            return '';
        }

        $editorName = self::editorName($editedBy);

        return '<p class="sm site-news-edited" style="margin-top:8px; color:#888">'
            . '<i>Отредактировано ' . htmlspecialchars(Date::zmdate($editedAt))
            . ' — ' . htmlspecialchars($editorName) . '</i></p>';
    }

    public static function editorName(int $userId): string
    {
        if ($userId <= 0) {
            return 'неизвестно';
        }

        $rows = DB::query('SELECT username FROM users WHERE id = :id', [':id' => $userId]);
        if (!empty($rows[0]['username'])) {
            return (string) $rows[0]['username'];
        }

        try {
            $user = new User($userId);
            $name = (string) $user->i('username');
            return $name !== '' ? $name : 'удалённый пользователь';
        } catch (\Throwable) {
            return 'удалённый пользователь';
        }
    }

    public static function renderItemHtml(array $row, string $dateFormat = 'zmdate'): string
    {
        $time = (int) ($row['time'] ?? 0);
        $date = $dateFormat === 'chronology'
            ? Date::chronologyDate($time)
            : Date::zmdate($time);

        $html = '<div class="p20 site-news-item" style="margin-bottom:10px">';
        $html .= '<h4>' . htmlspecialchars($date) . '</h4>';
        $html .= '<div class="break-links">' . ($row['body'] ?? '') . '</div>';
        $html .= self::editNoticeHtml($row);
        $html .= '</div>';

        return $html;
    }
}