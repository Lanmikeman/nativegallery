<?php

namespace App\Controllers\Api\Admin\Settings;

use App\Services\{Json, TaskScheduler};

class TaskManager
{
    public function __construct()
    {
        $taskId = trim((string) ($_GET['id'] ?? ''));
        $action = (int) ($_GET['type'] ?? -1);

        if ($taskId === '' || ($action !== 0 && $action !== 1)) {
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Некорректные параметры',
            ]);
            return;
        }

        $task = new TaskScheduler();
        $matched = false;

        foreach (NGALLERY_TASKS as $t) {
            if (($t['id'] ?? '') !== $taskId || ($t['type'] ?? '') !== 'cron') {
                continue;
            }

            $matched = true;
            $handler = (string) ($t['handler'] ?? '');
            $command = $task->cronJobCommand($handler);

            $result = $action === 1
                ? $task->addTask($taskId, $command, '*/5 * * * *', $handler)
                : $task->removeTask($taskId, $command, $handler);

            echo Json::return([
                'errorcode' => $result['ok'] ? 0 : 1,
                'error' => $result['ok'] ? 0 : 1,
                'message' => $result['message'],
            ]);
            return;
        }

        if (!$matched) {
            echo Json::return([
                'errorcode' => 1,
                'error' => 1,
                'message' => 'Задача не найдена',
            ]);
        }
    }
}