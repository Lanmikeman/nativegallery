<?php

namespace App\Controllers\Api;

use App\Services\{Auth, Router, DB, Json, AuthSession};
use \App\Controllers\ExceptionRegister;
use \App\Core\Page;

class Login
{
    public function __construct()
    {
        $username = $_POST['username'];
        $password = $_POST['password'];
        if (DB::query('SELECT email FROM users WHERE (LOWER(username) LIKE :username1) OR (LOWER(email) LIKE :username2)', array(':username1' => '%'.$username.'%', ':username2' => '%'.$username.'%'))) {
            $email = DB::query('SELECT email FROM users WHERE (LOWER(username) LIKE :username1) OR (LOWER(email) LIKE :username2)', array(':username1' => '%'.$username.'%', ':username2' => '%'.$username.'%'))[0]['email'];
            if (password_verify($password, DB::query('SELECT password FROM users WHERE email=:username', array(':username' => $email))[0]['password'])) {
                $user_id = DB::query('SELECT id FROM users WHERE email=:username', array(':username' => $email))[0]['id'];
                $token = AuthSession::establish((int) $user_id);

                echo Json::return(
                    array(
                        'errorcode' => '0',
                        'error' => 0,
                        'token' => $token
                    )
                );
            } else {
                echo Json::return(
                    array(
                        'errorcode' => '1',
                        'error' => 1
                    )
                );
            }
        } else {
            echo Json::return(
                array(
                    'errorcode' => '1',
                    'error' => 1
                )
            );
        }
    }
}
