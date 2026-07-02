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

    public static function trustedStreamHosts(): array
    {
        return ['radio.fetbuk.ru'];
    }

    public static function canProxyUrl(int $userId, string $url): bool
    {
        if ($userId <= 0 || !self::isValidStreamUrl($url) || !self::streamsAllowed()) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (in_array($host, self::trustedStreamHosts(), true)) {
            return true;
        }

        if (!self::tablesExist()) {
            return false;
        }

        $owned = DB::query(
            'SELECT id FROM audio_streams WHERE user_id = :uid AND url = :url LIMIT 1',
            [':uid' => $userId, ':url' => $url]
        );
        if (!empty($owned)) {
            return true;
        }

        $owned = DB::query(
            'SELECT id FROM audio_tracks WHERE user_id = :uid AND src = :url AND source_type = :st LIMIT 1',
            [':uid' => $userId, ':url' => $url, ':st' => 'url']
        );

        return !empty($owned);
    }

    public static function fetchIcyMetadata(string $url, int $timeoutSec = 8): array
    {
        $result = [
            'title' => '',
            'artist' => '',
            'station' => '',
        ];

        if (!function_exists('curl_init')) {
            return $result;
        }

        $metaInterval = 0;
        $audioRemaining = 0;
        $phase = 'audio';
        $metaRemaining = 0;
        $done = false;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_HTTPHEADER => [
                'Icy-MetaData: 1',
                'User-Agent: NativeGallery/1.0',
            ],
            CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$metaInterval, &$audioRemaining, &$result): int {
                if (stripos($header, 'icy-metaint:') === 0) {
                    $metaInterval = (int) trim(substr($header, 12));
                    $audioRemaining = $metaInterval;
                }
                if (stripos($header, 'icy-name:') === 0) {
                    $result['station'] = trim(substr($header, 9));
                }
                return strlen($header);
            },
            CURLOPT_WRITEFUNCTION => static function ($ch, string $data) use (
                &$metaInterval,
                &$audioRemaining,
                &$phase,
                &$metaRemaining,
                &$done,
                &$result
            ): int {
                if ($done) {
                    return 0;
                }

                $input = $data;
                while ($input !== '') {
                    if ($metaInterval <= 0) {
                        $done = true;
                        return 0;
                    }

                    if ($phase === 'audio') {
                        if ($audioRemaining <= 0) {
                            $audioRemaining = $metaInterval;
                        }
                        $take = min($audioRemaining, strlen($input));
                        $input = substr($input, $take);
                        $audioRemaining -= $take;
                        if ($audioRemaining === 0) {
                            $phase = 'meta_len';
                        }
                        continue;
                    }

                    if ($phase === 'meta_len') {
                        $metaRemaining = ord($input[0]) * 16;
                        $input = substr($input, 1);
                        $phase = $metaRemaining > 0 ? 'meta_skip' : 'audio';
                        if ($phase === 'audio') {
                            $audioRemaining = $metaInterval;
                        }
                        continue;
                    }

                    $need = $metaRemaining;
                    $have = strlen($input);
                    if ($have < $need) {
                        $block = $input;
                        $input = '';
                    } else {
                        $block = substr($input, 0, $need);
                        $input = substr($input, $need);
                    }
                    $metaRemaining -= strlen($block);
                    if ($metaRemaining === 0) {
                        $parsed = self::parseIcyMetaBlock($block);
                        if ($parsed['title'] !== '') {
                            $result['title'] = $parsed['title'];
                        }
                        if ($parsed['artist'] !== '') {
                            $result['artist'] = $parsed['artist'];
                        }
                        $done = true;
                        return 0;
                    }
                }

                return strlen($data);
            },
        ]);

        curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function parseIcyMetaBlock(string $block): array
    {
        $title = '';
        $artist = '';

        if (!preg_match("/StreamTitle='([^']*)'/i", $block, $m)) {
            return ['title' => '', 'artist' => ''];
        }

        $streamTitle = trim($m[1]);
        if ($streamTitle === '') {
            return ['title' => '', 'artist' => ''];
        }

        if (preg_match('/^(.+?)\s*[-–—]\s*(.+)$/u', $streamTitle, $parts)) {
            $artist = trim($parts[1]);
            $title = trim($parts[2]);
        } else {
            $title = $streamTitle;
        }

        return ['title' => $title, 'artist' => $artist];
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