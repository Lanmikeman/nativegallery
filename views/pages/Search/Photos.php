<?php

use App\Services\{DB, Auth};
use App\Services\Search\PhotoSearch;

$params = [
    'id' => $_GET['id'] ?? '',
    'gid' => $_GET['gid'] ?? '',
    'nid' => $_GET['nid'] ?? '',
    'etype' => $_GET['etype'] ?? '',
    'cid' => $_GET['cid'] ?? '',
    'place' => trim($_GET['place'] ?? ''),
    'route' => trim($_GET['route'] ?? ''),
    'q' => trim($_GET['q'] ?? ''),
    'camera' => trim($_GET['camera'] ?? ''),
    'date_shot_from' => $_GET['date_shot_from'] ?? '',
    'date_shot_to' => $_GET['date_shot_to'] ?? '',
    'date_pub_from' => $_GET['date_pub_from'] ?? '',
    'date_pub_to' => $_GET['date_pub_to'] ?? '',
    'sort' => $_GET['sort'] ?? '',
    'st' => $_GET['st'] ?? 0,
];

$hasCriteria = PhotoSearch::hasCriteria($params);
$photos = $hasCriteria ? PhotoSearch::fetch($params) : [];
$total = $hasCriteria ? PhotoSearch::count($params) : 0;
$offset = max(0, (int) $params['st']);
$perPage = PhotoSearch::PER_PAGE;

$geodb = DB::query('SELECT * FROM geodb ORDER BY title ASC');
$entities = DB::query('SELECT * FROM entities ORDER BY title ASC');
$galleries = DB::query('SELECT * FROM galleries ORDER BY title ASC');

$searchNavActive = 'photos';
$queryBase = $_GET;
unset($queryBase['st']);
$buildUrl = function (int $st = 0) use ($queryBase) {
    $q = $queryBase;
    if ($st > 0) {
        $q['st'] = $st;
    } else {
        unset($q['st']);
    }
    $qs = http_build_query($q);
    return '/search' . ($qs ? '?' . $qs : '');
};
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'); ?>
    <link rel="stylesheet" href="/static/css/jquery-ui-1.8.20.custom.css">
    <script src="/static/js/jquery-ui.js"></script>
    <script src="/static/js/selector.js"></script>
</head>

<body>
    <div id="backgr"></div>
    <table class="tmain">
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'); ?>
        <tr>
            <td class="main">
                <h1>Поиск фотографий</h1>
                <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/SearchNav.php'); ?>

                <form method="get" id="sf" class="p20" style="margin-bottom:20px">
                    <table>
                        <tr>
                            <td class="sm" style="padding-right:10px; vertical-align:top">Автор (ID):</td>
                            <td><input type="number" name="id" value="<?= htmlspecialchars($params['id']) ?>" class="form-control" style="max-width:150px"></td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Город (GeoDB):</td>
                            <td>
                                <select name="cid" class="form-select" style="max-width:300px">
                                    <option value="">Любой</option>
                                    <?php foreach ($geodb as $g) {
                                        $sel = (string) $params['cid'] === (string) $g['id'] ? 'selected' : '';
                                        echo '<option value="' . $g['id'] . '" ' . $sel . '>' . htmlspecialchars($g['title']) . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Место съёмки:</td>
                            <td><input type="text" name="place" value="<?= htmlspecialchars($params['place']) ?>" class="form-control" style="max-width:400px"></td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Вид сущности:</td>
                            <td>
                                <select name="etype" class="form-select" style="max-width:300px">
                                    <option value="">Любой</option>
                                    <?php foreach ($entities as $e) {
                                        $sel = (string) $params['etype'] === (string) $e['id'] ? 'selected' : '';
                                        echo '<option value="' . $e['id'] . '" ' . $sel . ' style="background-color:' . htmlspecialchars($e['color']) . '">' . htmlspecialchars($e['title']) . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">ID ТС:</td>
                            <td><input type="number" name="nid" value="<?= htmlspecialchars($params['nid']) ?>" class="form-control" style="max-width:150px"></td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Маршрут:</td>
                            <td><input type="text" name="route" value="<?= htmlspecialchars($params['route']) ?>" class="form-control" style="max-width:200px"></td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Галерея:</td>
                            <td>
                                <select name="gid" class="form-select" style="max-width:300px">
                                    <option value="">Любая</option>
                                    <?php foreach ($galleries as $g) {
                                        $sel = (string) $params['gid'] === (string) $g['id'] ? 'selected' : '';
                                        echo '<option value="' . $g['id'] . '" ' . $sel . '>' . htmlspecialchars($g['title']) . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Текст:</td>
                            <td><input type="text" name="q" value="<?= htmlspecialchars($params['q']) ?>" class="form-control" style="max-width:400px" placeholder="Подпись или место"></td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Камера (EXIF):</td>
                            <td><input type="text" name="camera" value="<?= htmlspecialchars($params['camera']) ?>" class="form-control" style="max-width:300px" placeholder="Canon, Nikon, IFD0.Model..."></td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Дата съёмки:</td>
                            <td>
                                <input type="date" name="date_shot_from" value="<?= htmlspecialchars($params['date_shot_from']) ?>" class="form-control" style="max-width:180px; display:inline-block">
                                —
                                <input type="date" name="date_shot_to" value="<?= htmlspecialchars($params['date_shot_to']) ?>" class="form-control" style="max-width:180px; display:inline-block">
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Дата публикации:</td>
                            <td>
                                <input type="date" name="date_pub_from" value="<?= htmlspecialchars($params['date_pub_from']) ?>" class="form-control" style="max-width:180px; display:inline-block">
                                —
                                <input type="date" name="date_pub_to" value="<?= htmlspecialchars($params['date_pub_to']) ?>" class="form-control" style="max-width:180px; display:inline-block">
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Сортировка:</td>
                            <td>
                                <select name="sort" class="form-select" style="max-width:200px">
                                    <option value="" <?= $params['sort'] !== 'shot' ? 'selected' : '' ?>>По дате публикации</option>
                                    <option value="shot" <?= $params['sort'] === 'shot' ? 'selected' : '' ?>>По дате съёмки</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><input type="submit" class="btn btn-primary" value="Найти"></td>
                        </tr>
                    </table>
                </form>

                <?php if ($hasCriteria) { ?>
                    <div class="sm" style="margin-bottom:15px">
                        Найдено: <b><?= $total ?></b>
                        <?php if ($total > 0) { ?> · <a href="#sf">Новый поиск</a><?php } ?>
                    </div>
                    <?php
                    if (empty($photos)) {
                        echo '<p class="sm"><i>Ничего не найдено. Попробуйте изменить критерии.</i></p>';
                    } else {
                        include $_SERVER['DOCUMENT_ROOT'] . '/views/components/PhotoSearchResults.php';
                    }

                    if ($total > $perPage) {
                        $pages = (int) ceil($total / $perPage);
                        $current = (int) floor($offset / $perPage) + 1;
                        echo '<p class="sm" style="margin-top:20px">';
                        if ($offset > 0) {
                            echo '<a href="' . htmlspecialchars($buildUrl($offset - $perPage)) . '">« назад</a> ';
                        }
                        for ($p = max(1, $current - 2); $p <= min($pages, $current + 2); $p++) {
                            $st = ($p - 1) * $perPage;
                            if ($p === $current) {
                                echo '<b>' . $p . '</b> ';
                            } else {
                                echo '<a href="' . htmlspecialchars($buildUrl($st)) . '">' . $p . '</a> ';
                            }
                        }
                        if ($offset + $perPage < $total) {
                            echo '<a href="' . htmlspecialchars($buildUrl($offset + $perPage)) . '">вперёд »</a>';
                        }
                        echo '</p>';
                    }
                } else { ?>
                    <p class="sm"><i>Укажите хотя бы один критерий поиска.</i></p>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
        </tr>
    </table>
</body>

</html>