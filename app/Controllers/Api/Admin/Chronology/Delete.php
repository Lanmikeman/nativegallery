<?php

namespace App\Controllers\Api\Admin\Chronology;

use App\Services\DB;

class Delete
{
    public function __construct()
    {
        $id = (int) explode('/', $_SERVER['REQUEST_URI'])[4];
        DB::query('DELETE FROM chronology WHERE id = :id', [':id' => $id]);
        echo json_encode(['errorcode' => 0, 'error' => 0]);
    }
}