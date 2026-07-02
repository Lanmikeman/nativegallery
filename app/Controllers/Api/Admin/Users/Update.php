<?php

namespace App\Controllers\Api\Admin\Users;

use App\Models\User;
use App\Services\{AdminAccess, Auth, DB, Json};

class Update
{
    public function __construct()
    {
        if (!AdminAccess::requireFullAdmin()) {
            return;
        }

        $userId = (int) (explode('/', strtok($_SERVER['REQUEST_URI'], '?'))[4] ?? 0);
        if ($userId <= 0) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Пользователь не найден']);
            return;
        }

        $rows = DB::query('SELECT id, admin, status, content FROM users WHERE id = :id', [':id' => $userId]);
        if ($rows === []) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Пользователь не найден']);
            return;
        }

        $target = $rows[0];
        $admin = isset($_POST['admin']) ? (int) $_POST['admin'] : (int) $target['admin'];
        $status = isset($_POST['status']) ? (int) $_POST['status'] : (int) $target['status'];
        $premoderation = (string) ($_POST['premoderation'] ?? 'false');

        if (!in_array($admin, [0, 1, 2, 3, AdminAccess::ROLE_OWNER], true)) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Некорректный уровень доступа']);
            return;
        }

        if ($admin === AdminAccess::ROLE_OWNER && !AdminAccess::isOwner()) {
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Назначать владельца может только текущий владелец',
            ]);
            return;
        }

        if (!in_array($status, [0, 1], true)) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Некорректный статус аккаунта']);
            return;
        }

        if (!in_array($premoderation, ['true', 'false'], true)) {
            echo Json::return(['errorcode' => 1, 'error' => 1, 'message' => 'Некорректное значение прямой загрузки']);
            return;
        }

        $editorId = Auth::userid();
        $editorAdmin = (int) (new User($editorId))->i('admin');

        if ($userId === $editorId && $admin !== $editorAdmin) {
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Нельзя изменить свою роль',
            ]);
            return;
        }

        if ((int) $target['admin'] === AdminAccess::ROLE_OWNER && $admin !== AdminAccess::ROLE_OWNER) {
            $owners = DB::query('SELECT COUNT(*) AS cnt FROM users WHERE admin = :role', [
                ':role' => AdminAccess::ROLE_OWNER,
            ]);
            if ((int) ($owners[0]['cnt'] ?? 0) <= 1) {
                echo Json::return([
                    'errorcode' => 1,
                    'error' => 1,
                    'message' => 'Нельзя снять последнего владельца сервера',
                ]);
                return;
            }
        }

        if ((int) $target['admin'] === AdminAccess::ROLE_ADMIN && $admin !== AdminAccess::ROLE_ADMIN) {
            $admins = DB::query('SELECT COUNT(*) AS cnt FROM users WHERE admin = :role', [
                ':role' => AdminAccess::ROLE_ADMIN,
            ]);
            if ((int) ($admins[0]['cnt'] ?? 0) <= 1) {
                echo Json::return([
                    'errorcode' => 1,
                    'error' => 1,
                    'message' => 'Нельзя удалить последнего администратора',
                ]);
                return;
            }
        }

        $content = json_decode((string) $target['content'], true);
        if (!is_array($content)) {
            $content = [];
        }
        $content['premoderation'] = $premoderation;

        DB::query(
            'UPDATE users SET admin = :admin, status = :status, content = :content WHERE id = :id',
            [
                ':admin' => $admin,
                ':status' => $status,
                ':content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                ':id' => $userId,
            ]
        );

        echo Json::return([
            'errorcode' => 0,
            'error' => 0,
            'id' => $userId,
        ]);
    }
}