<?php

namespace App\Controllers\Api;

use App\Controllers\Api\Images\LoadNew;

class LegacyApi
{
    public function __construct()
    {
        $action = (string) ($_GET['action'] ?? '');

        if ($action === 'get-pub-photos') {
            new LoadNew();
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Unknown action']);
    }
}