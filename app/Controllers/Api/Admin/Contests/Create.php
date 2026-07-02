<?php

namespace App\Controllers\Api\Admin\Contests;

use App\Services\{DB, Date};
use RuntimeException;

class Create
{
    public function __construct()
    {
        $startNow = ($_POST['startContestNow'] ?? '') === '1';

        $openprdate = Date::parseDateTimeLocal($_POST['openpretendsdate'] ?? '');
        $closeprdate = Date::parseDateTimeLocal($_POST['closepretendsdate'] ?? '');
        $opendate = Date::parseDateTimeLocal($_POST['opendate'] ?? '');
        $closedate = Date::parseDateTimeLocal($_POST['closedate'] ?? '');

        if ($startNow && $closeprdate) {
            $opendate = $closeprdate;
        }

        if (!$openprdate || !$closeprdate) {
            $this->fail('Укажите даты начала и конца отбора претендентов');
            return;
        }

        if (!$opendate) {
            $this->fail('Укажите дату начала голосования или включите «Провести сразу после отбора претендентов»');
            return;
        }

        if (!$closedate) {
            if ($startNow) {
                $duration = max(3600, $closeprdate - $openprdate);
                $closedate = $opendate + $duration;
            } else {
                $this->fail('Укажите дату конца проведения конкурса');
                return;
            }
        }

        if ($closeprdate <= $openprdate) {
            $this->fail('Дата конца отбора претендентов должна быть позже начала');
            return;
        }

        if ($closedate <= $opendate) {
            $this->fail('Дата конца конкурса должна быть позже начала голосования');
            return;
        }

        $themeId = (int) ($_POST['themeid'] ?? 0);
        if ($themeId <= 0) {
            $this->fail('Выберите тематику конкурса');
            return;
        }

        $now = time();
        $status = $openprdate <= $now ? 1 : 0;

        try {
            DB::query(
                'INSERT INTO contests (themeid, openpretendsdate, closepretendsdate, opendate, closedate, status)
                 VALUES (:themeid, :openprdate, :closeprdate, :opendate, :closedate, :status)',
                [
                    ':themeid' => $themeId,
                    ':openprdate' => $openprdate,
                    ':closeprdate' => $closeprdate,
                    ':opendate' => $opendate,
                    ':closedate' => $closedate,
                    ':status' => $status,
                ]
            );
        } catch (RuntimeException $e) {
            $this->fail('Не удалось сохранить конкурс в базу данных');
            return;
        }

        echo json_encode([
            'errorcode' => 0,
            'error' => 0,
            'message' => 'Конкурс создан',
        ]);
    }

    private function fail(string $message): void
    {
        echo json_encode([
            'errorcode' => 1,
            'error' => $message,
            'message' => $message,
        ]);
    }
}