<?php

use App\Services\{Date, UpdateQuery};

Date::applySiteTimezone();

$params = UpdateQuery::parseParams($_GET);
$mode = UpdateQuery::mode($params);

$buildUrl = fn(array $overrides = []) => UpdateQuery::buildUrl($params, array_merge(['st' => 0], $overrides));

$photos = [];
$total = 0;
$archiveDays = [];
$archiveTotalDays = 0;
$daySummary = [];

if ($mode === 'recent' || $mode === 'date') {
    $photos = UpdateQuery::fetchPhotos($params);
    $total = UpdateQuery::countPhotos($params);
    $facetCities = UpdateQuery::fetchFacetCities($params);
    $facetAuthors = UpdateQuery::fetchFacetAuthors($params);
    $facetTypes = UpdateQuery::fetchFacetTypes($params);
} else {
    $archiveDays = UpdateQuery::fetchArchiveDays($params['st']);
    $archiveTotalDays = UpdateQuery::countArchiveDays();
    foreach ($archiveDays as $day) {
        $daySummary[$day['upload_date']] = UpdateQuery::fetchDaySummary($day['upload_date']);
    }
}

$perPage = UpdateQuery::PER_PAGE;
$offset = $params['st'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'); ?>
</head>

<body>
    <div id="backgr"></div>
    <table class="tmain">
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'); ?>
        <tr>
            <td class="main">
                <p class="sm" style="margin-bottom:10px">
                    <a href="/update?time=24"<?= ($mode === 'recent' && (int) ($params['time'] ?? 0) === 24) ? ' style="font-weight:bold"' : '' ?>>Новые фотографии (24 ч)</a>
                    · <a href="/update?time=72"<?= ($mode === 'recent' && (int) ($params['time'] ?? 0) === 72) ? ' style="font-weight:bold"' : '' ?>>за 72 часа</a>
                    · <a href="/update"<?= $mode === 'archive' ? ' style="font-weight:bold"' : '' ?>>Архив по датам</a>
                </p>

                <?php if ($mode === 'archive') { ?>
                    <h1>Архив обновлений по датам</h1>
                    <?php
                    if (empty($archiveDays)) {
                        echo '<p class="sm"><i>Нет опубликованных фотографий.</i></p>';
                    }
                    foreach ($archiveDays as $day) {
                        $date = $day['upload_date'];
                        $cnt = (int) $day['cnt'];
                        $ts = strtotime($date . ' 12:00:00');
                        echo '<h4 style="margin-top:20px"><b><a href="/update?date=' . htmlspecialchars($date) . '">'
                            . Date::chronologyDate($ts) . '</a></b> — ' . $cnt . ' '
                            . ($cnt % 10 === 1 && $cnt % 100 !== 11 ? 'фотография' : ($cnt % 10 >= 2 && $cnt % 10 <= 4 && ($cnt % 100 < 10 || $cnt % 100 >= 20) ? 'фотографии' : 'фотографий'))
                            . '</h4>';

                        $summary = $daySummary[$date] ?? [];
                        foreach ($summary as $city) {
                            echo '<p style="margin:4px 0 4px 15px"><b>' . htmlspecialchars($city['title']) . '</b>:</p>';
                            foreach ($city['galleries'] as $gid => $gtitle) {
                                echo '<p style="margin:2px 0 2px 30px">» <i>' . htmlspecialchars($gtitle) . '</i></p>';
                            }
                            foreach ($city['types'] as $type) {
                                $entities = array_unique(array_filter($type['entities']));
                                $entityList = implode(', ', array_slice($entities, 0, 20));
                                if (count($entities) > 20) {
                                    $entityList .= '…';
                                }
                                echo '<p style="margin:2px 0 2px 30px">» <a href="/update?date=' . htmlspecialchars($date) . '&t=' . (int) $type['id'] . '">'
                                    . htmlspecialchars($type['title']) . '</a>';
                                if ($entityList !== '') {
                                    echo ' — ' . htmlspecialchars($entityList);
                                }
                                echo '</p>';
                            }
                        }
                    }

                    $daysPerPage = UpdateQuery::ARCHIVE_DAYS_PER_PAGE;
                    if ($archiveTotalDays > $daysPerPage) {
                        $pages = (int) ceil($archiveTotalDays / $daysPerPage);
                        $current = (int) floor($offset / $daysPerPage) + 1;
                        echo '<p class="sm" style="margin-top:25px">';
                        if ($offset > 0) {
                            echo '<a href="/update?st=' . max(0, $offset - $daysPerPage) . '">« назад</a> ';
                        }
                        for ($p = max(1, $current - 2); $p <= min($pages, $current + 2); $p++) {
                            $st = ($p - 1) * $daysPerPage;
                            if ($p === $current) {
                                echo '<b>' . $p . '</b> ';
                            } else {
                                echo '<a href="/update?st=' . $st . '">' . $p . '</a> ';
                            }
                        }
                        if ($offset + $daysPerPage < $archiveTotalDays) {
                            echo '<a href="/update?st=' . ($offset + $daysPerPage) . '">вперёд »</a>';
                        }
                        echo '</p>';
                    }
                } else { ?>
                    <h1><?= $mode === 'date' ? 'Фотографии за ' . UpdateQuery::periodLabel($params) : 'Новые фотографии за ' . UpdateQuery::periodLabel($params) ?></h1>

                    <?php
                    UpdateQuery::renderFilterLine('Города', $facetCities, $buildUrl, 'cid', $params['cid']);
                    UpdateQuery::renderFilterLine('Авторы', $facetAuthors, $buildUrl, 'aid', $params['aid'] ? $params['aid'] : null);
                    UpdateQuery::renderFilterLine('Виды сущностей', $facetTypes, $buildUrl, 't', $params['t']);
                    ?>

                    <br>

                    <?php if (empty($photos)) { ?>
                        <p class="sm"><i>За выбранный период фотографий не найдено.</i></p>
                    <?php } else {
                        include $_SERVER['DOCUMENT_ROOT'] . '/views/components/UpdatePhotoList.php';
                    } ?>

                    <p class="sm" style="margin-top:15px">
                        Фотографий за период: <b><?= $total ?></b>
                        <?php if ($total > 0) { ?>
                            · Показано: <?= min($perPage, count($photos)) ?> из <?= $total ?>
                        <?php } ?>
                    </p>

                    <?php if ($total > $perPage) {
                        $pages = (int) ceil($total / $perPage);
                        $current = (int) floor($offset / $perPage) + 1;
                        echo '<p class="sm" style="margin-top:10px">';
                        if ($offset > 0) {
                            echo '<a href="' . htmlspecialchars($buildUrl(['st' => $offset - $perPage])) . '">« назад</a> ';
                        }
                        for ($p = max(1, $current - 2); $p <= min($pages, $current + 2); $p++) {
                            $st = ($p - 1) * $perPage;
                            if ($p === $current) {
                                echo '<b>' . $p . '</b> ';
                            } else {
                                echo '<a href="' . htmlspecialchars($buildUrl(['st' => $st])) . '">' . $p . '</a> ';
                            }
                        }
                        if ($offset + $perPage < $total) {
                            echo '<a href="' . htmlspecialchars($buildUrl(['st' => $offset + $perPage])) . '">вперёд »</a>';
                        }
                        echo '</p>';
                    } ?>

                    <?php
                    UpdateQuery::renderFilterLine('Города', $facetCities, $buildUrl, 'cid', $params['cid']);
                    UpdateQuery::renderFilterLine('Авторы', $facetAuthors, $buildUrl, 'aid', $params['aid'] ? $params['aid'] : null);
                    UpdateQuery::renderFilterLine('Виды сущностей', $facetTypes, $buildUrl, 't', $params['t']);
                    ?>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
        </tr>
    </table>
</body>

</html>