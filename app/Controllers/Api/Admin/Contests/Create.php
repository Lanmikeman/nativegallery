<?php

namespace App\Controllers\Api\Admin\Contests;



use App\Services\{Auth, Router, GenerateRandomStr, DB, Json, EXIF, Date};
use App\Models\{User, Vote, Photo};


class Create
{
    public function __construct()
    {
        $openprdate = Date::parseDateTimeLocal($_POST['openpretendsdate'] ?? '');
        $closeprdate = Date::parseDateTimeLocal($_POST['closepretendsdate'] ?? '');
        $opendate = Date::parseDateTimeLocal($_POST['opendate'] ?? '');
        $closedate = Date::parseDateTimeLocal($_POST['closedate'] ?? '');

        if ($_POST['startContestNow'] === "1") {
            $opendate = $closeprdate;
        }

        if (!$openprdate || !$closeprdate || !$opendate || !$closedate) {
            echo json_encode(['errorcode' => 1, 'error' => 'Укажите все даты конкурса']);
            return;
        }

        if ($closeprdate <= $openprdate || $closedate <= $opendate) {
            echo json_encode(['errorcode' => 1, 'error' => 'Даты конкурса указаны в неверном порядке']);
            return;
        }

        $now = time();
        $status = $openprdate <= $now ? 1 : 0;

        DB::query(
            'INSERT INTO contests VALUES (\'0\', :themeid, :openprdate, :closeprdate, :opendate, :closedate, :status)',
            [
                ':themeid' => $_POST['themeid'],
                ':openprdate' => $openprdate,
                ':closeprdate' => $closeprdate,
                ':opendate' => $opendate,
                ':closedate' => $closedate,
                ':status' => $status,
            ]
        );
        echo json_encode(
            array(
                'errorcode' => 0,
                'error' => 0
            )
        );
    }
}