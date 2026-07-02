<?php

namespace App\Controllers\Api\Admin\Pages;

use App\Services\{Auth, DB, Json};

class Create
{
    public function __construct()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($title === '' || $body === '') {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Заголовок и текст обязательны']);
            return;
        }

        $editorId = Auth::userid();
        if ($editorId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Нет доступа']);
            return;
        }

        $now = time();

        if ($id > 0) {
            $existing = DB::query('SELECT id FROM pages WHERE id = :id', [':id' => $id]);
            if (!empty($existing)) {
                echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Страница с таким ID уже существует']);
                return;
            }

            DB::query(
                'INSERT INTO pages (id, title, body, created_by, created_at, updated_at, updated_by)
                 VALUES (:id, :title, :body, :created_by, :created_at, :updated_at, :updated_by)',
                [
                    ':id' => $id,
                    ':title' => $title,
                    ':body' => $body,
                    ':created_by' => $editorId,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                    ':updated_by' => $editorId,
                ]
            );
        } else {
            DB::query(
                'INSERT INTO pages (title, body, created_by, created_at, updated_at, updated_by)
                 VALUES (:title, :body, :created_by, :created_at, :updated_at, :updated_by)',
                [
                    ':title' => $title,
                    ':body' => $body,
                    ':created_by' => $editorId,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                    ':updated_by' => $editorId,
                ]
            );
            $id = (int) DB::lastInsertId();
        }

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'id' => $id,
        ]);
    }
}