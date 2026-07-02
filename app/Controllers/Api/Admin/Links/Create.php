<?php

namespace App\Controllers\Api\Admin\Links;

use App\Services\DB;

class Create
{
    public function __construct()
    {
        DB::query(
            'INSERT INTO site_links (title, url, sort) VALUES (:title, :url, :sort)',
            [
                ':title' => $_POST['title'] ?? '',
                ':url' => $_POST['url'] ?? '',
                ':sort' => (int) ($_POST['sort'] ?? 0),
            ]
        );

        echo json_encode(['errorcode' => 0, 'error' => 0]);
    }
}