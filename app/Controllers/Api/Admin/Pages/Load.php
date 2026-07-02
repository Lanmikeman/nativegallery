<?php

namespace App\Controllers\Api\Admin\Pages;

use App\Services\DB;

class Load
{
    public function __construct()
    {
        $pages = DB::query('SELECT id FROM pages ORDER BY id');
        if ($pages === []) {
            echo '<tr><td colspan="5" class="admin-pages-table__empty">Страниц пока нет. Создайте первую или примените миграцию <code>sql_0009.sql</code>.</td></tr>';
            return;
        }

        foreach ($pages as $row) {
            (new \App\Models\Admin\Page((int) $row['id']))->viewRow();
        }
    }
}