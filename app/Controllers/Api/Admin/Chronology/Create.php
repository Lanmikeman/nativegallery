<?php

namespace App\Controllers\Api\Admin\Chronology;

use App\Services\DB;

class Create
{
    public function __construct()
    {
        $time = strtotime($_POST['date'] ?? 'today');
        if ($time === false) {
            $time = time();
        }

        DB::query(
            'INSERT INTO chronology (city, geodb_id, transit_type, time, body, main) VALUES (:city, :geodb_id, :transit_type, :time, :body, :main)',
            [
                ':city' => $_POST['city'] ?? '',
                ':geodb_id' => (int) ($_POST['geodb_id'] ?? 0),
                ':transit_type' => (int) ($_POST['transit_type'] ?? 0),
                ':time' => $time,
                ':body' => $_POST['body'] ?? '',
                ':main' => !empty($_POST['main']) ? 1 : 0,
            ]
        );

        echo json_encode(['errorcode' => 0, 'error' => 0]);
    }
}