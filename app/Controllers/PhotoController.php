<?php
namespace App\Controllers;

use \App\Services\{Router, Auth, DB, Json};
use \App\Controllers\ExceptionRegister;
use \App\Core\Page;

class PhotoController
{
    public static function random()
    {
        $rows = DB::query('SELECT id FROM photos WHERE moderated = 1 ORDER BY RAND() LIMIT 1');
        if ($rows === []) {
            ExceptionRegister::notfound();
            return;
        }

        header('Location: /photo/' . (int) $rows[0]['id'] . '/');
        exit;
    }

    public static function i()
    {
       Page::set('Photo');
    }
    public static function photoext()
    {
       Page::set('PhotoExt');
       
    }


}