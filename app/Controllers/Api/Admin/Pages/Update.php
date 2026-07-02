<?php

namespace App\Controllers\Api\Admin\Pages;

use App\Services\{Auth, DB, Json};

class Update
{
    public function __construct()
    {
        $pageId = (int) (explode('/', strtok($_SERVER['REQUEST_URI'], '?'))[4] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($pageId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Страница не найдена']);
            return;
        }

        if ($title === '' || $body === '') {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Заголовок и текст обязательны']);
            return;
        }

        $rows = DB::query('SELECT id FROM pages WHERE id = :id', [':id' => $pageId]);
        if (empty($rows)) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Страница не найдена']);
            return;
        }

        $editorId = Auth::userid();
        if ($editorId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Нет доступа']);
            return;
        }

        DB::query(
            'UPDATE pages SET title = :title, body = :body, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id',
            [
                ':title' => $title,
                ':body' => $body,
                ':updated_at' => time(),
                ':updated_by' => $editorId,
                ':id' => $pageId,
            ]
        );

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'id' => $pageId,
        ]);
    }
}