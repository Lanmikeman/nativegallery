<?php

namespace App\Controllers\Api\Admin\Links;

use App\Services\DB;

class Delete
{
    public function __construct()
    {
        $id = (int) ($_GET['id'] ?? 0);
        DB::query('DELETE FROM site_links WHERE id = :id', [':id' => $id]);
        echo json_encode(['errorcode' => 0, 'error' => 0]);
    }
}