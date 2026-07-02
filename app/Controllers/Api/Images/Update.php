<?php

namespace App\Controllers\Api\Images;

use App\Services\{Auth, DB, Json, EXIF, GenerateRandomStr};
use App\Services\Upload as UploadPhoto;
use App\Models\User;

class Update
{
    public function __construct()
    {
        $photoId = (int) ($_POST['id'] ?? 0);
        if ($photoId <= 0) {
            echo json_encode(['errorcode' => 1, 'error' => 'Фотография не найдена']);
            return;
        }

        $rows = DB::query('SELECT * FROM photos WHERE id = :id', [':id' => $photoId]);
        if (empty($rows)) {
            echo json_encode(['errorcode' => 1, 'error' => 'Фотография не найдена']);
            return;
        }

        $photo = $rows[0];
        $user = new User(Auth::userid());
        if ((int) $photo['user_id'] !== Auth::userid() && (int) $user->i('admin') <= 0) {
            echo json_encode(['errorcode' => 1, 'error' => 'Нет доступа']);
            return;
        }

        $day = (int) ($_POST['day'] ?? 0);
        $month = (int) ($_POST['month'] ?? 0);
        $year = (int) ($_POST['year'] ?? 0);
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || $year < 1850) {
            echo json_encode(['errorcode' => 1, 'error' => 'Укажите корректную дату съёмки']);
            return;
        }

        $place = trim((string) ($_POST['place'] ?? ''));
        $descr = trim((string) ($_POST['descr'] ?? ''));
        $galleryId = (int) ($_POST['gallery'] ?? 0);

        $content = json_decode($photo['content'], true);
        if (!is_array($content)) {
            $content = [];
        }

        $content['copyright'] = (string) ($_POST['license'] ?? ($content['copyright'] ?? '1'));
        $content['comment'] = $descr;
        $content['comments'] = ((int) ($_POST['disablecomments'] ?? 0) === 1) ? 'disabled' : 'allowed';
        $content['rating'] = ((int) ($_POST['disablerating'] ?? 0) === 1) ? 'disabled' : 'allowed';
        $content['showtop'] = ((int) ($_POST['disableshowtop'] ?? 0) === 1) ? 'disabled' : 'allowed';

        $entitydataId = (int) ($_POST['nid'] ?? 0);
        if ($entitydataId > 0) {
            $entityRows = DB::query('SELECT id FROM entities_data WHERE id = :id', [':id' => $entitydataId]);
            if (empty($entityRows)) {
                echo json_encode(['errorcode' => 1, 'error' => 'Модель сущности не найдена']);
                return;
            }
            $content['entityroute'] = trim((string) ($_POST['entity_route'] ?? ''));
            $content['entitycomment'] = trim((string) ($_POST['entity_notes'] ?? ''));
        } else {
            $entitydataId = 0;
            unset($content['entityroute'], $content['entitycomment']);
        }

        if (isset($_POST['nomap'])) {
            $content['lat'] = null;
            $content['lng'] = null;
        } else {
            $lat = $_POST['lat'] ?? null;
            $lng = $_POST['lng'] ?? null;
            if ($lat !== '' && $lng !== '') {
                $content['lat'] = $lat;
                $content['lng'] = $lng;
            }
        }

        $exif = $photo['exif'];
        if ((int) ($_POST['disableexif'] ?? 0) === 1) {
            $exif = Json::return(['type' => 'disabled']);
        }

        $photourl = $photo['photourl'];
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $replaced = $this->replaceMedia($photo, $content, $exif);
            if ($replaced === null) {
                return;
            }
            [$photourl, $exif, $content] = $replaced;
        }

        $moderated = (int) $photo['moderated'];
        $endmoderation = (int) $photo['endmoderation'];
        if ($moderated === 2) {
            $moderated = 0;
            $endmoderation = -1;
            unset($content['declineReason'], $content['declineComment'], $content['iRate'], $content['kRate']);
        }

        DB::query(
            'UPDATE photos SET postbody = :postbody, photourl = :photourl, posted_at = :posted_at, exif = :exif,
             moderated = :moderated, place = :place, endmoderation = :endmoderation, gallery_id = :gallery_id,
             entitydata_id = :entitydata_id, content = :content
             WHERE id = :id',
            [
                ':postbody' => $descr,
                ':photourl' => $photourl,
                ':posted_at' => mktime(0, 0, 0, $month, $day, $year),
                ':exif' => is_string($exif) ? $exif : json_encode($exif, JSON_UNESCAPED_UNICODE),
                ':moderated' => $moderated,
                ':place' => $place,
                ':endmoderation' => $endmoderation,
                ':gallery_id' => $galleryId,
                ':entitydata_id' => $entitydataId,
                ':content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                ':id' => $photoId,
            ]
        );

        echo json_encode([
            'id' => $photoId,
            'errorcode' => 0,
            'error' => 0,
        ]);
    }

    private function replaceMedia(array $photo, array $content, $exif): ?array
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        if ($type === 'image/gif' && NGALLERY['root']['photo']['upload']['allowgif'] === false) {
            echo json_encode(['errorcode' => 'FILE_NOTSUPPORTED', 'error' => 1]);
            return null;
        }

        $upload = null;
        $photourl = $photo['photourl'];

        if (explode('/', $type)[0] === 'video') {
            $newname = GenerateRandomStr::init(64);
            $ffmpegPath = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                ? $_SERVER['DOCUMENT_ROOT'] . '/app/Controllers/Exec/ffmpeg.exe'
                : 'ffmpeg';
            $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/cdn/temp/';
            $mp4File = $tempDir . $newname . '.mp4';

            exec("$ffmpegPath -i {$_FILES['image']['tmp_name']} -c:v libx264 -crf 18 -fpsmax 60 -preset fast -c:a aac -ac 2 -codec:v copy -codec:a copy $mp4File");

            $thumbnailFile = $tempDir . $newname . '.jpg';
            exec("$ffmpegPath -i {$_FILES['image']['tmp_name']} -ss 00:00:00 -frames:v 1 -q:v 2 $thumbnailFile");

            $background = imagecreatefromjpeg($thumbnailFile);
            $overlay = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . '/static/img/playic.png');
            $destX = (imagesx($background) - imagesx($overlay)) / 2;
            $destY = (imagesy($background) - imagesy($overlay)) / 2;
            imagecopy($background, $overlay, $destX, $destY, 0, 0, imagesx($overlay), imagesy($overlay));
            $outputImagePath = $_SERVER['DOCUMENT_ROOT'] . '/cdn/temp/VIDPRV_' . $newname . '.jpg';
            imagejpeg($background, $outputImagePath, 90);
            imagedestroy($background);
            imagedestroy($overlay);

            $upload = new UploadPhoto($outputImagePath, 'cdn/img/');
            $content['type'] = 'video';
            $content['videourl'] = (new UploadPhoto($mp4File, 'cdn/video/'))->getSrc();
            $photourl = $upload->getSrc();
            $exif = Json::return(['type' => 'none']);
        } elseif (explode('/', $type)[0] === 'image') {
            $exifReader = new EXIF($_FILES['image']['tmp_name']);
            $exif = $exifReader->getData();
            if ($exif === null) {
                $exif = Json::return(['type' => 'none']);
            }
            $upload = new UploadPhoto($_FILES['image'], 'cdn/img/');
            $content['type'] = 'image';
            unset($content['videourl']);
            $photourl = $upload->getSrc();
        } else {
            echo json_encode(['errorcode' => 'FILE_NOTSUPPORTED', 'error' => 1]);
            return null;
        }

        if (!$upload || $upload->getType() === null) {
            echo json_encode(['errorcode' => 'FILE_NOTSELECTED', 'error' => 1]);
            return null;
        }

        return [$photourl, $exif, $content];
    }
}