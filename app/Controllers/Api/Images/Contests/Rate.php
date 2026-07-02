<?php

namespace App\Controllers\Api\Images\Contests;



use App\Services\{Auth, Router, GenerateRandomStr, DB, Json, EXIF};
use App\Models\{User, Vote, Photo};


class Rate
{
    public function __construct()
    {
        $count = 3;
        $contestId = (int) ($_GET['kid'] ?? 0);
        $photoId = (int) ($_GET['pid'] ?? 0);
        if ($contestId <= 0 || $photoId <= 0) {
            $this->respond($photoId, 0, 'Некорректные параметры голосования.');
        }

        $uservotes = (int) (DB::query(
            'SELECT COUNT(*) AS cnt FROM contests_rates WHERE user_id=:uid AND contest_id=:cid',
            [':uid' => Auth::userid(), ':cid' => $contestId]
        )[0]['cnt'] ?? 0);
        $countvotes = $count - $uservotes;

        $contestRows = DB::query('SELECT * FROM contests WHERE id=:id', [':id' => $contestId]);
        if (empty($contestRows)) {
            $this->respond($photoId, 0, 'Конкурс не найден.');
        }
        $contest = $contestRows[0];

        $photo = new Photo($photoId);
        if ((int) $contest['status'] !== 2) {
            $this->respond($photoId, 0, 'Голосование сейчас не проводится.');
        }
        if ((int) $photo->i('on_contest') !== 2 || (int) $photo->i('contest_id') !== $contestId) {
            $this->respond($photoId, 0, 'Фото не участвует в этом конкурсе.');
        }
        $existingRate = DB::query(
            'SELECT photo_id FROM contests_rates WHERE photo_id=:pid AND user_id=:uid AND contest_id=:cid',
            [':uid' => Auth::userid(), ':pid' => $photoId, ':cid' => $contestId]
        );
        $status = 0;
        if (!empty($existingRate)) {
            DB::query('DELETE FROM contests_rates WHERE user_id=:uid AND photo_id=:pid AND contest_id=:cid', [':pid' => $photoId, ':uid' => Auth::userid(), ':cid' => $contestId]);
            $status = 0;
            $newval = $countvotes + 1;
        } else {
            $newval = $countvotes - 1;
            if ($newval >= 0) {
                DB::query('INSERT INTO contests_rates VALUES (\'0\', :pid, :uid, :cid)', [':pid' => $photoId, ':uid' => Auth::userid(), ':cid' => $contestId]);
                $status = 1;
            }
        }
        if ($newval < 0) {
            $text = 'Вы можете выбрать максимум 3 фотографии.';
        } else if ($newval === 0) {
            $text = 'Вы выбрали 3 фотографии. Спасибо за голосование!';
        } else {
            $text = "Вы можете выбрать ещё {$newval} фото.";
        }
        $this->respond($photoId, $status, $text);
    }

    private function respond(int $photoId, int $status, string $text): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([[(string) $photoId => $status], $text], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
