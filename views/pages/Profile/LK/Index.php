<?php

use App\Services\{Auth, DB};
use App\Models\User;

$user = new User(Auth::userid());
$userId = Auth::userid();

$publishedCount = (int) DB::query(
    'SELECT COUNT(*) AS cnt FROM photos WHERE user_id = :uid AND moderated = 1',
    [':uid' => $userId]
)[0]['cnt'];

$siteQueueCount = (int) DB::query(
    'SELECT COUNT(*) AS cnt FROM photos WHERE moderated = 0'
)[0]['cnt'];

$userQueueCount = (int) DB::query(
    'SELECT COUNT(*) AS cnt FROM photos WHERE user_id = :uid AND moderated = 0',
    [':uid' => $userId]
)[0]['cnt'];

$uploadIndex = (int) $user->i('uploadindex');
$strokeWidth = min(240, max(0, 4 * $uploadIndex));

$lkActive = 'info';

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'; ?>
</head>

<body>
    <div id="backgr"></div>
    <table class="tmain">
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'; ?>
        <tr>
            <td class="main">
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LkShellOpen.php'; ?>

                <h1>Общая информация</h1>
                <h4>Здравствуйте, <a href="/author/<?= $userId ?>/"><?= htmlspecialchars($user->i('username')) ?></a>!</h4>

                <p>Количество ваших фотографий на сайте: <b><?= $publishedCount ?></b></p>
                <p>Ваши фотографии в очереди на публикацию: <b><?= $userQueueCount ?></b></p>
                <p>Всего фотографий в очереди на публикацию: <b><?= $siteQueueCount ?></b></p>

                <h4>Индекс загрузки</h4>
                <p>
                    Текущее значение
                    <a href="/page/1" class="und">индекса загрузки</a>:
                    <b><?= $uploadIndex ?></b>
                </p>
                <div class="p20" style="float:left; padding:15px 15px 20px; width:240px">
                    <div style="background-color:#fff; width:240px; height:16px"></div>
                    <div style="background-color:#599fe7; width:<?= $strokeWidth ?>px; height:14px; position:relative; top:-15px; margin-bottom:-19px"></div>
                    <img src="/static/img/scale1.png" alt="" style="position:relative; top:-12px; margin-left:-5px; margin-bottom:-22px">
                </div>
                <br clear="all">
                <p><a href="/lk/pday.php" class="und">История изменения индекса загрузки</a></p>
                <?php if ($uploadIndex <= 10) { ?>
                    <p class="sm">При индексе загрузки 10 и ниже действует суточное ограничение на число предлагаемых фотографий (равное индексу, округление вниз).</p>
                <?php } ?>
                <?php if ($uploadIndex >= 55) { ?>
                    <p class="sm">Индекс 55 и выше даёт право на прямую публикацию (постмодерация).</p>
                <?php } ?>

                <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LkShellClose.php'; ?>
            </td>
        </tr>
        <tr>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'; ?>
        </tr>
    </table>
</body>

</html>