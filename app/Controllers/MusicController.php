<?php

namespace App\Controllers;

use App\Core\Page;
use App\Services\AudioLibrary;

class MusicController
{
    public static function index()
    {
        if (!AudioLibrary::isEnabled()) {
            Page::set('Errors/404');
            return;
        }

        Page::set('Music');
    }
}