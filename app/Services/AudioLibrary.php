<?php

namespace App\Services;

class AudioLibrary
{
    public static function tablesExist(): bool
    {
        $row = DB::query(
            "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audio_tracks'"
        );
        return !empty($row) && (int) $row[0]['c'] > 0;
    }

    public static function uploadAllowed(): bool
    {
        return (NGALLERY['root']['audio']['upload']['allow'] ?? true) === true;
    }

    public static function streamsAllowed(): bool
    {
        return (NGALLERY['root']['audio']['streams']['allow'] ?? true) === true;
    }

    public static function maxUploadBytes(): int
    {
        return (int) (NGALLERY['root']['audio']['upload']['maxsize'] ?? 52428800);
    }

    public static function allowedMimeTypes(): array
    {
        return [
            'audio/mpeg',
            'audio/mp3',
            'audio/ogg',
            'audio/wav',
            'audio/x-wav',
            'audio/flac',
            'audio/aac',
            'audio/mp4',
            'audio/webm',
            'audio/x-m4a',
        ];
    }

    public static function isValidStreamUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    public static function trackRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'type' => 'track',
            'title' => $row['title'],
            'artist' => $row['artist'],
            'src' => $row['src'],
            'source_type' => $row['source_type'],
            'duration' => (int) $row['duration'],
            'created_at' => (int) $row['created_at'],
        ];
    }

    public static function streamRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'type' => 'stream',
            'title' => $row['title'],
            'artist' => '',
            'src' => $row['url'],
            'source_type' => 'stream',
            'duration' => 0,
            'created_at' => (int) $row['created_at'],
        ];
    }

    public static function libraryForUser(int $userId): array
    {
        if (!self::tablesExist()) {
            return ['tracks' => [], 'streams' => [], 'playlists' => []];
        }

        $tracks = DB::query(
            'SELECT * FROM audio_tracks WHERE user_id = :uid ORDER BY created_at DESC',
            [':uid' => $userId]
        );
        $streams = DB::query(
            'SELECT * FROM audio_streams WHERE user_id = :uid ORDER BY created_at DESC',
            [':uid' => $userId]
        );
        $playlists = DB::query(
            'SELECT * FROM audio_playlists WHERE user_id = :uid ORDER BY updated_at DESC',
            [':uid' => $userId]
        );

        return [
            'tracks' => array_map([self::class, 'trackRow'], $tracks),
            'streams' => array_map([self::class, 'streamRow'], $streams),
            'playlists' => array_map(function ($pl) {
                return [
                    'id' => (int) $pl['id'],
                    'title' => $pl['title'],
                    'created_at' => (int) $pl['created_at'],
                    'updated_at' => (int) $pl['updated_at'],
                    'items' => self::playlistItems((int) $pl['id']),
                ];
            }, $playlists),
        ];
    }

    public static function playlistItems(int $playlistId): array
    {
        $rows = DB::query(
            'SELECT * FROM audio_playlist_items WHERE playlist_id = :pid ORDER BY sort_order ASC, id ASC',
            [':pid' => $playlistId]
        );
        $items = [];
        foreach ($rows as $row) {
            $item = self::resolvePlaylistItem($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public static function resolvePlaylistItem(array $row): ?array
    {
        $type = $row['item_type'];
        if ($type === 'url') {
            $src = trim((string) $row['url']);
            if ($src === '') {
                return null;
            }
            return [
                'id' => (int) $row['id'],
                'type' => 'url',
                'title' => $row['title'] !== '' ? $row['title'] : $src,
                'artist' => '',
                'src' => $src,
                'source_type' => 'url',
            ];
        }
        if ($type === 'track') {
            $track = DB::query('SELECT * FROM audio_tracks WHERE id = :id', [':id' => (int) $row['item_id']]);
            if (empty($track)) {
                return null;
            }
            $t = self::trackRow($track[0]);
            $t['playlist_item_id'] = (int) $row['id'];
            return $t;
        }
        if ($type === 'stream') {
            $stream = DB::query('SELECT * FROM audio_streams WHERE id = :id', [':id' => (int) $row['item_id']]);
            if (empty($stream)) {
                return null;
            }
            $s = self::streamRow($stream[0]);
            $s['playlist_item_id'] = (int) $row['id'];
            return $s;
        }
        return null;
    }

    public static function ownsPlaylist(int $playlistId, int $userId): bool
    {
        $row = DB::query(
            'SELECT id FROM audio_playlists WHERE id = :id AND user_id = :uid',
            [':id' => $playlistId, ':uid' => $userId]
        );
        return !empty($row);
    }

    public static function ownsTrack(int $trackId, int $userId): bool
    {
        $row = DB::query(
            'SELECT id FROM audio_tracks WHERE id = :id AND user_id = :uid',
            [':id' => $trackId, ':uid' => $userId]
        );
        return !empty($row);
    }

    public static function ownsStream(int $streamId, int $userId): bool
    {
        $row = DB::query(
            'SELECT id FROM audio_streams WHERE id = :id AND user_id = :uid',
            [':id' => $streamId, ':uid' => $userId]
        );
        return !empty($row);
    }
}