<?php

namespace App\Controllers\Api\Admin\Contests;

use App\Services\{Auth, ContestClosure};

class Cancel
{
    public function __construct()
    {
        $contestId = (int) ($_POST['id'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if ($contestId <= 0) {
            echo json_encode(['errorcode' => 1, 'error' => 'Укажите конкурс']);
            return;
        }
        if ($reason === '') {
            echo json_encode(['errorcode' => 1, 'error' => 'Укажите причину отмены']);
            return;
        }

        try {
            echo json_encode(ContestClosure::cancel($contestId, Auth::userid(), $reason));
        } catch (\Throwable $e) {
            echo json_encode(['errorcode' => 1, 'error' => $e->getMessage()]);
        }
    }
}