<?php

namespace App\Controllers\Api\Admin\Contests;

use App\Services\{Auth, ContestClosure};

class ForceClose
{
    public function __construct()
    {
        $contestId = (int) ($_POST['id'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $processWinners = ($_POST['process_winners'] ?? '1') === '1';

        if ($contestId <= 0) {
            echo json_encode(['errorcode' => 1, 'error' => 'Укажите конкурс']);
            return;
        }
        if ($reason === '') {
            echo json_encode(['errorcode' => 1, 'error' => 'Укажите причину принудительного завершения']);
            return;
        }

        try {
            echo json_encode(ContestClosure::forceFinish($contestId, Auth::userid(), $reason, $processWinners));
        } catch (\Throwable $e) {
            echo json_encode(['errorcode' => 1, 'error' => $e->getMessage()]);
        }
    }
}