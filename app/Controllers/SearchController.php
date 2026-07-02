<?php

namespace App\Controllers;

use App\Core\Page;

class SearchController
{
    public static function photos()
    {
        Page::set('Search/Photos');
    }

    public static function vehicles()
    {
        Page::set('Search/Vehicles');
    }

    public static function comments()
    {
        Page::set('Search/Comments');
    }

    public static function authors()
    {
        Page::set('Search/Authors');
    }
}