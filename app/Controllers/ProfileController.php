<?php
namespace App\Controllers;

use \App\Services\{Router, Auth, DB, Json};
use \App\Controllers\ExceptionRegister;
use \App\Core\Page;

class ProfileController
{

    static $file = 'Index';

    public function __construct()
    {
        if (isset($_GET['type'])) {
            switch (Page::exists('Profile/LK/Profile/' . $_GET['type'])) {
                case true:
                    self::$file = $_GET['type'];
                    break;
                case false:
                    self::$file = 'Index';
                    break;
            }
        } else {
            self::$file = 'Index';
        }
    }
    
    public static function loadContent()
    {
        $type = trim((string) ($_GET['type'] ?? ''));

        if ($type !== '' && Page::exists('Profile/LK/Profile/' . $type)) {
            self::$file = $type;
        } else {
            self::$file = 'Index';
        }

        Page::set('Profile/LK/Profile/' . self::$file);
    }
  
    public static function lk()
    {
       Page::set('Profile/LK/Index');
    }
    public static function i()
    {
       Page::set('Profile/Index');
    }
    public static function photoindexhistory()
    {
       Page::set('Profile/LK/PhotoIndexHistory');
       
    }
    public static function upload()
    {
       Page::set('Profile/UploadPhoto');
    }
    public static function lkhistory()
    {
       Page::set('Profile/LK/History');
    }

    public static function lkticket()
    {
        Page::set('Profile/LK/Ticket');
    }

    public static function lkkonkurs()
    {
        Page::set('Profile/LK/Konkurs');
    }
    public static function lkprofile()
    {
       Page::set('Profile/LK/Profile');
    }
    public static function editimage()
    {
        $photoId = (int) ($_GET['id'] ?? 0);
        if ($photoId <= 0) {
            ExceptionRegister::notfound();
            return;
        }

        $rows = DB::query('SELECT user_id FROM photos WHERE id = :id', [':id' => $photoId]);
        if (empty($rows)) {
            ExceptionRegister::notfound();
            return;
        }

        $user = new \App\Models\User(Auth::userid());
        if ((int) $rows[0]['user_id'] !== Auth::userid() && (int) $user->i('admin') <= 0) {
            ExceptionRegister::notfound();
            return;
        }

        Page::set('Profile/LK/EditImage');
    }

    public static function editphoto()
    {
        self::editimage();
    }


}