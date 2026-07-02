<?php

use App\Services\{DB, Date, ChronologyQuery};

$params = [
    'all' => isset($_GET['all']) ? 1 : 0,
    'geodb_id' => $_GET['cid'] ?? '',
    't' => $_GET['t'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'q' => trim($_GET['q'] ?? ''),
    'st' => $_GET['st'] ?? 0,
];

$items = ChronologyQuery::fetch($params);
$total = ChronologyQuery::count($params);
$offset = max(0, (int) $params['st']);
$perPage = ChronologyQuery::PER_PAGE;
$geodb = DB::query('SELECT * FROM geodb ORDER BY title ASC');

$queryBase = $_GET;
unset($queryBase['st']);
$buildUrl = function (int $st = 0, bool $all = null) use ($queryBase) {
    $q = $queryBase;
    if ($st > 0) {
        $q['st'] = $st;
    } else {
        unset($q['st']);
    }
    if ($all === true) {
        $q['all'] = 1;
    } elseif ($all === false) {
        unset($q['all']);
    }
    $qs = http_build_query($q);
    return '/news' . ($qs ? '?' . $qs : '');
};
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
                <h1>Новости и хронология</h1>

                <p class="sm" style="margin-bottom:15px">
                    Показать:
                    <?php if (empty($params['all'])) { ?>
                        <b>только главные</b> · <a href="<?= htmlspecialchars($buildUrl(0, true)) ?>">все</a>
                    <?php } else { ?>
                        <a href="<?= htmlspecialchars($buildUrl(0, false)) ?>">только главные</a> · <b>все</b>
                    <?php } ?>
                </p>

                <form method="get" class="p20" style="margin-bottom:20px">
                    <?php if (!empty($params['all'])) { ?><input type="hidden" name="all" value="1"><?php } ?>
                    <table>
                        <tr>
                            <td class="sm" style="padding-right:10px">Город:</td>
                            <td>
                                <select name="cid" class="form-select" style="max-width:300px">
                                    <option value="">Все города</option>
                                    <?php foreach ($geodb as $g) {
                                        $sel = (int) $params['geodb_id'] === (int) $g['id'] ? 'selected' : '';
                                        echo '<option value="' . $g['id'] . '" ' . $sel . '>' . htmlspecialchars($g['title']) . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Вид транспорта:</td>
                            <td>
                                <select name="t" class="form-select" style="max-width:300px">
                                    <?php foreach (ChronologyQuery::TRANSIT_TYPES as $id => $label) {
                                        $sel = (string) $params['t'] === (string) $id ? 'selected' : '';
                                        echo '<option value="' . $id . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Дата с:</td>
                            <td>
                                <input type="date" name="date_from" value="<?= htmlspecialchars($params['date_from']) ?>" class="form-control" style="max-width:200px; display:inline-block">
                                по
                                <input type="date" name="date_to" value="<?= htmlspecialchars($params['date_to']) ?>" class="form-control" style="max-width:200px; display:inline-block">
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Текст:</td>
                            <td><input type="text" name="q" value="<?= htmlspecialchars($params['q']) ?>" class="form-control" style="max-width:400px"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><input type="submit" class="btn btn-primary" value="Найти"></td>
                        </tr>
                    </table>
                </form>

                <?php
                if (empty($items)) {
                    echo '<p class="sm"><i>Записей не найдено. Администратор может добавить их в <a href="/admin?type=Chronology">Админ → Хронология</a>.</i></p>';
                } else {
                    foreach ($items as $item) {
                        $city = ChronologyQuery::cityLabel((int) $item['geodb_id'], $item['city']);
                        echo '<div class="p20" style="margin-bottom:10px">';
                        echo '<h4>' . htmlspecialchars($city) . ', ' . Date::chronologyDate((int) $item['time']) . '</h4>';
                        echo '<div class="break-links">' . $item['body'] . '</div>';
                        echo '</div>';
                    }
                }

                if ($total > $perPage) {
                    $pages = (int) ceil($total / $perPage);
                    $current = (int) floor($offset / $perPage) + 1;
                    echo '<p class="sm" style="margin-top:20px">';
                    if ($offset > 0) {
                        echo '<a href="' . htmlspecialchars($buildUrl($offset - $perPage)) . '">« назад</a> ';
                    }
                    for ($p = 1; $p <= min($pages, 10); $p++) {
                        $st = ($p - 1) * $perPage;
                        if ($p === $current) {
                            echo '<b>' . $p . '</b> ';
                        } else {
                            echo '<a href="' . htmlspecialchars($buildUrl($st)) . '">' . $p . '</a> ';
                        }
                    }
                    if ($pages > 10) {
                        echo '··· <a href="' . htmlspecialchars($buildUrl(($pages - 1) * $perPage)) . '">' . $pages . '</a> ';
                    }
                    if ($offset + $perPage < $total) {
                        echo '<a href="' . htmlspecialchars($buildUrl($offset + $perPage)) . '">вперёд »</a>';
                    }
                    echo '</p>';
                }
                ?>
            </td>
        </tr>
        <tr>
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
        </tr>
    </table>
</body>

</html>