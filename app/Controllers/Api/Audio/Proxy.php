<?php

namespace App\Controllers\Api\Audio;

use App\Services\{Auth, AudioLibrary};

class Proxy
{
    private int $metaInterval = 0;
    private int $audioRemaining = 0;
    private int $metaRemaining = 0;
    private string $metaPhase = 'audio';

    public function __construct()
    {
        $userId = Auth::userid();
        if ($userId <= 0) {
            http_response_code(401);
            exit;
        }
        if (!AudioLibrary::isEnabled()) {
            http_response_code(403);
            exit;
        }

        $url = trim((string) ($_GET['url'] ?? ''));
        if (!AudioLibrary::canProxyUrl($userId, $url)) {
            http_response_code(403);
            exit;
        }

        $this->stream($url);
    }

    private function stream(string $url): void
    {
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        while (ob_get_level()) {
            ob_end_flush();
        }

        header('Content-Type: audio/mpeg');
        header('Cache-Control: no-cache, no-store');
        header('X-Accel-Buffering: no');

        $proxy = $this;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTPHEADER => [
                'Icy-MetaData: 1',
                'User-Agent: NativeGallery/1.0',
            ],
            CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use ($proxy): int {
                if (stripos($header, 'icy-metaint:') === 0) {
                    $proxy->metaInterval = (int) trim(substr($header, 12));
                    $proxy->audioRemaining = $proxy->metaInterval;
                }
                return strlen($header);
            },
            CURLOPT_WRITEFUNCTION => static function ($ch, string $data) use ($proxy): int {
                $filtered = $proxy->filterIcy($data);
                if ($filtered !== '') {
                    echo $filtered;
                    flush();
                }
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Audio proxy error for ' . $url . ': ' . curl_error($ch));
        }
        curl_close($ch);
        exit;
    }

    private function filterIcy(string $chunk): string
    {
        if ($this->metaInterval <= 0) {
            return $chunk;
        }

        if ($this->audioRemaining <= 0 && $this->metaPhase === 'audio') {
            $this->audioRemaining = $this->metaInterval;
        }

        $input = $chunk;
        $output = '';

        while ($input !== '') {
            if ($this->metaPhase === 'audio') {
                $take = min($this->audioRemaining, strlen($input));
                $output .= substr($input, 0, $take);
                $input = substr($input, $take);
                $this->audioRemaining -= $take;
                if ($this->audioRemaining === 0) {
                    $this->metaPhase = 'meta_len';
                }
                continue;
            }

            if ($this->metaPhase === 'meta_len') {
                $this->metaRemaining = ord($input[0]) * 16;
                $input = substr($input, 1);
                if ($this->metaRemaining > 0) {
                    $this->metaPhase = 'meta_skip';
                } else {
                    $this->metaPhase = 'audio';
                    $this->audioRemaining = $this->metaInterval;
                }
                continue;
            }

            $skip = min($this->metaRemaining, strlen($input));
            $input = substr($input, $skip);
            $this->metaRemaining -= $skip;
            if ($this->metaRemaining === 0) {
                $this->metaPhase = 'audio';
                $this->audioRemaining = $this->metaInterval;
            }
        }

        return $output;
    }
}