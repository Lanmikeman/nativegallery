<?php

namespace App\Services;

use donatj\UserAgent\UserAgentParser;

class AuthSession
{
    public static function establish(int $userId): string
    {
        $token = GenerateRandomStr::gen_uuid();
        $servicekey = GenerateRandomStr::gen_uuid();

        $ip = Router::ip() ?? '0.0.0.0';
        $parser = new UserAgentParser();
        $ua = $parser();
        $device = $ua->platform();
        $os = $ua->platform();

        $loc = '';
        $url = 'http://ip-api.com/json/' . $ip;
        $response = @file_get_contents($url);
        if ($response !== false) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['country'])) {
                $loc = ($data['country'] ?? '') . ', ' . ($data['city'] ?? '');
            }
        }

        $encryptionKey = NGALLERY['root']['encryptionkey'] ?? '';
        $iv = openssl_random_pseudo_bytes(16);
        $encryptedIp = openssl_encrypt($ip, 'AES-256-CBC', $encryptionKey, 0, $iv);
        $encryptedLoc = openssl_encrypt($loc, 'AES-256-CBC', $encryptionKey, 0, $iv);

        DB::query(
            'INSERT INTO login_tokens (id, token, iv, user_id, device_name, os, ip, location, last_activity, created_at)
             VALUES (\'0\', :token, :iv, :user_id, :device, :os, :ip, :loc, :la, :crd)',
            [
                ':token' => $token,
                ':user_id' => $userId,
                ':device' => $device,
                ':os' => $os,
                ':ip' => $encryptedIp,
                ':loc' => $encryptedLoc,
                ':la' => time(),
                ':crd' => time(),
                ':iv' => $iv,
            ]
        );

        $ttl = time() + 50 * 50 * 54 * 72;
        self::setAuthCookie('NGALLERYSESS', $token, $ttl);
        self::setAuthCookie('NGALLERYSERVICE', $servicekey, $ttl);
        self::setAuthCookie('NGALLERYSESS_', '1', $ttl);
        self::setAuthCookie('NGALLERYID', (string) $userId, $ttl);

        return $token;
    }

    private static function setAuthCookie(string $name, string $value, int $expires): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

        setcookie($name, $value, [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}