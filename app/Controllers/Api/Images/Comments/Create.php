<?php

namespace App\Controllers\Api\Images\Comments;

use App\Services\{Auth, GenerateRandomStr, DB, Json};
use App\Services\Upload as Upload;

class Create
{
    static $filesrc = '';

    private static function bodyIsEmpty(string $postbody): bool
    {
        $decoded = html_entity_decode($postbody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', '', strip_tags($decoded));

        return $text === '';
    }

    private static function hasUploadedFile(): bool
    {
        if (!isset($_FILES['filebody']) || !is_array($_FILES['filebody'])) {
            return false;
        }

        $file = $_FILES['filebody'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmpName = (string) ($file['tmp_name'] ?? '');

        return $error === UPLOAD_ERR_OK
            && $tmpName !== ''
            && is_uploaded_file($tmpName);
    }

    private static function create($content, $id, $postbody)
    {
        DB::query(
            'INSERT INTO photos_comments VALUES (\'0\', :userid, :postid, :postbody, :time, :content)',
            [
                ':postid' => $id,
                ':postbody' => $postbody,
                ':userid' => Auth::userid(),
                ':time' => time(),
                ':content' => $content,
            ]
        );
    }

    public function __construct()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $postbody = (string) ($_POST['wtext'] ?? '');
        $photoRows = DB::query('SELECT id FROM photos WHERE id=:id', [':id' => $id]);

        if ($id <= 0 || empty($photoRows) || $id !== (int) $photoRows[0]['id']) {
            die(json_encode([
                'errorcode' => '3',
                'error' => 1,
            ]));
        }

        $type = 'none';
        self::$filesrc = '';
        $hasFile = self::hasUploadedFile();

        if ($hasFile) {
            $tmpName = (string) $_FILES['filebody']['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            $filename = GenerateRandomStr::gen_uuid();

            if (preg_match('/^image\//', $mime)) {
                $info = getimagesize($tmpName);

                if ($info['mime'] == 'image/jpeg') {
                    $image = imagecreatefromjpeg($tmpName);
                } elseif ($info['mime'] == 'image/gif') {
                    $image = imagecreatefromgif($tmpName);
                } elseif ($info['mime'] == 'image/png') {
                    $image = imagecreatefrompng($tmpName);
                } else {
                    die(json_encode(['errorcode' => '1', 'error' => 1]));
                }

                $type = 'img';
                $destination = '/cdn/temp/' . $filename . '.jpg';
                imagejpeg($image, $_SERVER['DOCUMENT_ROOT'] . $destination, 60);
            } elseif (preg_match('/^video\//', $mime)) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $ffmpeg = \FFMpeg\FFMpeg::create([
                        'ffmpeg.binaries'  => $_SERVER['DOCUMENT_ROOT'] . '/app/Controllers/Exec/ffmpeg.exe',
                        'ffprobe.binaries' => $_SERVER['DOCUMENT_ROOT'] . '/app/Controllers/Exec/ffprobe.exe',
                        'timeout'          => 3600,
                        'ffmpeg.threads'   => 12,
                    ]);
                } else {
                    $ffmpeg = \FFMpeg\FFMpeg::create();
                }
                $video = $ffmpeg->open($tmpName);
                $video->save(new \FFMpeg\Format\Video\X264(), $_SERVER['DOCUMENT_ROOT'] . '/cdn/temp/' . $filename . '.mp4');
                $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1))->save($_SERVER['DOCUMENT_ROOT'] . '/cdn/temp/VIDPRV_' . $filename . '.jpg');
                $type = 'video';
                $destination = '/cdn/temp/' . $filename . '.mp4';
            } else {
                die('Неизвестный тип файла');
            }

            $upload = new Upload($_SERVER['DOCUMENT_ROOT'] . $destination, 'cdn/' . $type . '/');
            self::$filesrc = $upload->getSrc();
        }

        if (!$hasFile && self::bodyIsEmpty($postbody)) {
            die(json_encode([
                'errorcode' => '1',
                'error' => 1,
            ]));
        }

        if (!$hasFile && strlen($postbody) >= 4096) {
            die(json_encode([
                'errorcode' => '2',
                'error' => 1,
            ]));
        }

        $smileys_dir = $_SERVER['DOCUMENT_ROOT'] . '/static/img/smileys/1';
        $allowedCodes = [];

        if (is_dir($smileys_dir)) {
            $files = scandir($smileys_dir);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['gif', 'png', 'jpg'])) {
                    $allowedCodes[] = ':' . pathinfo($file, PATHINFO_FILENAME) . ':';
                }
            }
        }

        $postbody = ltrim($postbody);
        $postbody = preg_replace_callback('/:\w+:/', function ($matches) use ($allowedCodes) {
            return in_array($matches[0], $allowedCodes, true) ? $matches[0] : '';
        }, $postbody);

        $content = Json::return([
            'type' => 'none',
            'by' => 'user',
            'filetype' => $type,
            'src' => self::$filesrc,
        ]);

        self::create($content, $id, $postbody);

        die(json_encode([
            'errorcode' => '0',
            'error' => 0,
        ]));
    }
}