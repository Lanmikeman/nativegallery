<?php

namespace App\Controllers\Api\Admin\News;



use App\Services\{Auth, Router, GenerateRandomStr, DB, Json, EXIF};
use App\Models\{User, Vote, Photo};


class Create
{
    public function __construct()
    {
        DB::query(
            'INSERT INTO news (body, time, edited_at, edited_by) VALUES (:body, :time, 0, 0)',
            [':body' => $_POST['body'], ':time' => time()]
        );
        echo json_encode(
            array(
                'errorcode' => 0,
                'error' => 0
            )
        );
    }
}
