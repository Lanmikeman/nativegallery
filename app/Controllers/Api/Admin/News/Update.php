<?php

namespace App\Controllers\Api\Admin\News;

use App\Services\{Auth, DB, Json};

class Update
{
    public function __construct()
    {
        $postId = (int) (explode('/', strtok($_SERVER['REQUEST_URI'], '?'))[4] ?? 0);
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($postId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Новость не найдена']);
            return;
        }

        if ($body === '') {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Текст новости не может быть пустым']);
            return;
        }

        $rows = DB::query('SELECT id FROM news WHERE id = :id', [':id' => $postId]);
        if (empty($rows)) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Новость не найдена']);
            return;
        }

        $editorId = Auth::userid();
        if ($editorId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Нет доступа']);
            return;
        }

        DB::query(
            'UPDATE news SET body = :body, edited_at = :edited_at, edited_by = :edited_by WHERE id = :id',
            [
                ':body' => $body,
                ':edited_at' => time(),
                ':edited_by' => $editorId,
                ':id' => $postId,
            ]
        );

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'id' => $postId,
        ]);
    }
}