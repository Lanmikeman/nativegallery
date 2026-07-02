<?php

namespace App\Controllers;

use \App\Services\{Router, Auth, DB, Json};
use \App\Controllers\ExceptionRegister;
use \App\Core\Page;

class AdminController
{
    public static function resolvePage(): string
    {
        $type = trim((string) ($_GET['type'] ?? ''));
        if ($type === '' || !Page::exists('Admin/' . $type)) {
            return 'General';
        }

        return $type;
    }

    public static function loadMenu()
    {
        Page::component('AdminSidebar');
    }

    public static function index()
    {
        Page::set('Admin/Index');
    }

    public static function loadContent()
    {
        Page::set('Admin/' . self::resolvePage());
    }
}