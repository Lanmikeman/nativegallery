<?php

namespace App\Controllers\Api\Admin\Models;



use App\Services\{Auth, Router, GenerateRandomStr, DB, Json, EXIF};
use App\Models\{User, Vote, Photo};


class RequestHandler
{
    public function __construct()
    {
        
        $id = explode('/', $_SERVER['REQUEST_URI'])[5];
        $type = explode('/', $_SERVER['REQUEST_URI'])[6];
        $modelrequest = DB::query('SELECT * FROM entities_requests WHERE id=:id', array(':id' => $id))[0];
        if ($modelrequest) {
            if ($type === 'accept') {
                DB::query(
                    'INSERT INTO entities_data (title, createdate, entityid, comment, content) VALUES (:title, :createdate, :entityid, NULL, :content)',
                    array(':title' => $modelrequest['title'], ':createdate' => time(), ':entityid' => $modelrequest['entityid'], ':content' => $modelrequest['data'])
                );
                $newEntityId = (int) DB::lastInsertId();
                $photoId = (int) ($modelrequest['photo_id'] ?? 0);
                if ($photoId > 0 && $newEntityId > 0) {
                    DB::query(
                        'UPDATE photos SET entitydata_id = :entitydata_id WHERE id = :id',
                        array(':entitydata_id' => $newEntityId, ':id' => $photoId)
                    );
                }
                DB::query('UPDATE entities_requests SET status=1 WHERE id=:id', array(':id' => $id));
            } else if ($type === 'decline') {
                DB::query('UPDATE entities_requests SET status=2 WHERE id=:id', array(':id' => $id));
            }
        }
        echo json_encode(
            array(
                'errorcode' => '0',
                'error' => 0,
            )
        );
    }
}
