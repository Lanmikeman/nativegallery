<?php

namespace App\Controllers;

use App\Core\Page;

class MusicController
{
    public static function index()
    {
        Page::set('Music');
    }
}