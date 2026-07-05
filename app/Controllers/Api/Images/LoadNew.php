<?php

namespace App\Controllers\Api\Images;

use App\Services\{DB, Date, Image};

class LoadNew
{
    private const LIMIT = 30;

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $firstpid = (int) ($_GET['firstpid'] ?? 0);
            if ($firstpid <= 0) {
                echo json_encode([]);
                return;
            }

            $photos = DB::query(
                'SELECT * FROM photos
                 WHERE moderated = 1 AND id > :id
                 ORDER BY id ASC
                 LIMIT ' . self::LIMIT,
                [':id' => $firstpid]
            );

            $response = [];
            foreach ($photos as $photo) {
                $response[] = $this->formatPhotoData($photo);
            }

            echo json_encode($response);
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function formatPhotoData(array $photo): array
    {
        $id = (int) $photo['id'];
        $comments = (int) (DB::query(
            'SELECT COUNT(*) AS cnt FROM photos_comments WHERE photo_id = :pid',
            [':pid' => $id]
        )[0]['cnt'] ?? 0);

        return [
            'id' => $id,
            'pid' => $id,
            'place' => '<span style="word-spacing:-1px"><b>' . htmlspecialchars((string) $photo['place']) . '</b></span>',
            'date' => $this->formatDate((int) $photo['posted_at']),
            'photourl_small' => $this->compressUrl((string) $photo['photourl']),
            'photourl_extrasmall' => Image::generateBlurredPlaceholder((string) $photo['photourl']),
            'ccnt' => $comments,
        ];
    }

    private function formatDate(int $timestamp): string
    {
        if ($timestamp === 943909200 || Date::zmdate($timestamp) === '30 ноября 1999 в 00:00') {
            return 'дата не указана';
        }

        return Date::zmdate($timestamp);
    }

    private function compressUrl(string $url): string
    {
        return '/api/photo/compress?url=' . rawurlencode($url);
    }
}