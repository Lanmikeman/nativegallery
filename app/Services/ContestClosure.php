<?php

namespace App\Services;

use App\Models\User;

class ContestClosure
{
    public const STATUS_FINISHED = 3;
    public const STATUS_CANCELLED = 4;

    public static function forceFinish(int $contestId, int $adminId, string $reason, bool $processWinners = true): array
    {
        $contest = self::getActiveContest($contestId);
        if ($processWinners) {
            self::processVotes($contest);
        }
        self::releasePhotos((int) $contest['id']);
        self::markClosed($contest, $adminId, $reason, $processWinners ? 'forced_end' : 'forced_end_no_winners');
        return ['errorcode' => 0, 'error' => 0];
    }

    public static function cancel(int $contestId, int $adminId, string $reason): array
    {
        $contest = self::getActiveContest($contestId);
        self::releasePhotos((int) $contest['id']);
        DB::query(
            'UPDATE contests SET status = :status, closure_meta = :meta WHERE id = :id',
            [
                ':status' => self::STATUS_CANCELLED,
                ':meta' => self::buildMeta('cancelled', $adminId, $reason),
                ':id' => $contest['id'],
            ]
        );
        return ['errorcode' => 0, 'error' => 0];
    }

    public static function finishNormally(array $contest): void
    {
        self::processVotes($contest);
        self::releasePhotos((int) $contest['id']);
        self::markClosed($contest, 0, '', 'normal');
    }

    public static function processVotes(array $contest): void
    {
        $votes = DB::query(
            'SELECT photo_id, COUNT(*) AS vote_count
             FROM contests_rates WHERE contest_id = :id
             GROUP BY photo_id ORDER BY vote_count DESC LIMIT 10',
            [':id' => $contest['id']]
        );

        $place = 1;
        foreach ($votes as $vote) {
            self::updatePhotoContent($vote, $contest, $place);
            $place++;
        }
    }

    public static function releasePhotos(int $contestId): void
    {
        DB::query(
            'UPDATE photos SET contest_id = 0, on_contest = 0 WHERE contest_id = :id',
            [':id' => $contestId]
        );
    }

    public static function decodeMeta(?string $json): ?array
    {
        if (!$json) {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public static function closureLabel(?array $meta): string
    {
        if (!$meta) {
            return '';
        }
        return match ($meta['type'] ?? '') {
            'forced_end' => 'Завершён принудительно',
            'forced_end_no_winners' => 'Завершён принудительно (без победителей)',
            'cancelled' => 'Отменён',
            default => '',
        };
    }

    private static function getActiveContest(int $contestId): array
    {
        $rows = DB::query('SELECT * FROM contests WHERE id = :id', [':id' => $contestId]);
        if (empty($rows)) {
            throw new \RuntimeException('Конкурс не найден');
        }
        $contest = $rows[0];
        $status = (int) $contest['status'];
        if ($status === self::STATUS_FINISHED || $status === self::STATUS_CANCELLED) {
            throw new \RuntimeException('Конкурс уже завершён или отменён');
        }
        return $contest;
    }

    private static function markClosed(array $contest, int $adminId, string $reason, string $type): void
    {
        DB::query(
            'UPDATE contests SET status = :status, closure_meta = :meta WHERE id = :id',
            [
                ':status' => self::STATUS_FINISHED,
                ':meta' => self::buildMeta($type, $adminId, $reason),
                ':id' => $contest['id'],
            ]
        );
    }

    private static function buildMeta(string $type, int $adminId, string $reason): string
    {
        $meta = [
            'type' => $type,
            'reason' => trim($reason),
            'at' => time(),
        ];
        if ($adminId > 0) {
            $meta['admin_id'] = $adminId;
            $user = new User($adminId);
            $meta['admin_username'] = $user->i('username');
        }
        return json_encode($meta, JSON_UNESCAPED_UNICODE);
    }

    private static function updatePhotoContent(array $vote, array $contest, int $place): void
    {
        $photo = DB::query('SELECT * FROM photos WHERE id = :id', [':id' => $vote['photo_id']])[0];
        $photoData = json_decode($photo['content'], true);
        if (!is_array($photoData)) {
            $photoData = [];
        }
        if (!isset($photoData['contests']) || !is_array($photoData['contests'])) {
            $photoData['contests'] = [];
        }

        $theme = DB::query('SELECT title FROM contests_themes WHERE id = :id', [':id' => $contest['themeid']])[0]['title'];
        $photoData['contests'][] = [
            'id' => $contest['id'],
            'contesttheme' => $theme,
            'votenum' => $vote['vote_count'],
            'place' => $place,
        ];

        DB::query(
            'INSERT INTO contests_winners VALUES (\'0\', :photo_id, :place, :contest_id, :date)',
            [
                ':photo_id' => $vote['photo_id'],
                ':place' => $place,
                ':contest_id' => $contest['id'],
                ':date' => time(),
            ]
        );

        DB::query(
            'UPDATE photos SET content = :content, on_contest = 0, contest_id = 0 WHERE id = :id',
            [
                ':id' => $vote['photo_id'],
                ':content' => json_encode($photoData, JSON_UNESCAPED_UNICODE),
            ]
        );
    }
}