<?php

use App\Services\DB;
use App\Services\Search\VehicleSearch;

$params = [
    'etype' => $_GET['etype'] ?? '',
    'num' => trim($_GET['num'] ?? ''),
    'q' => trim($_GET['q'] ?? ''),
    'st' => $_GET['st'] ?? 0,
];

$hasCriteria = !empty($params['etype']) || !empty($params['num']) || !empty($params['q']);
$items = $hasCriteria ? VehicleSearch::fetch($params) : [];
$total = $hasCriteria ? VehicleSearch::count($params) : 0;
$offset = max(0, (int) $params['st']);
$perPage = VehicleSearch::PER_PAGE;
$entities = DB::query('SELECT * FROM entities ORDER BY title ASC');

$searchNavActive = 'vehicles';
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
    return '/vsearch' . ($qs ? '?' . $qs : '');
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
                <h1>Поиск транспортных средств</h1>
                <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/SearchNav.php'); ?>

                <form method="get" class="p20" style="margin-bottom:20px">
                    <table>
                        <tr>
                            <td class="sm" style="padding-right:10px">Вид сущности:</td>
                            <td>
                                <select name="etype" class="form-select" style="max-width:300px">
                                    <option value="">Любой</option>
                                    <?php foreach ($entities as $e) {
                                        $sel = (string) $params['etype'] === (string) $e['id'] ? 'selected' : '';
                                        echo '<option value="' . $e['id'] . '" ' . $sel . '>' . htmlspecialchars($e['title']) . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">ID / номер:</td>
                            <td><input type="text" name="num" value="<?= htmlspecialchars($params['num']) ?>" class="form-control" style="max-width:200px"></td>
                        </tr>
                        <tr>
                            <td class="sm" style="padding-right:10px">Текст:</td>
                            <td><input type="text" name="q" value="<?= htmlspecialchars($params['q']) ?>" class="form-control" style="max-width:400px" placeholder="Название или примечание"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><input type="submit" class="btn btn-primary" value="Найти"></td>
                        </tr>
                    </table>
                </form>

                <?php if ($hasCriteria) { ?>
                    <div class="sm" style="margin-bottom:15px">Найдено: <b><?= $total ?></b></div>
                    <?php if (empty($items)) { ?>
                        <p class="sm"><i>Ничего не найдено.</i></p>
                    <?php } else { ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Тип</th>
                                    <th>Примечание</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item) { ?>
                                    <tr>
                                        <td><?= (int) $item['id'] ?></td>
                                        <td><a href="/vehicle/<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></a></td>
                                        <td><?= htmlspecialchars($item['entity_type'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($item['comment'] ?? '') ?></td>
                                        <td><a href="/search?nid=<?= (int) $item['id'] ?>">Фото</a></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php }
                    if ($total > $perPage) {
                        echo '<p class="sm" style="margin-top:15px">';
                        if ($offset > 0) {
                            echo '<a href="' . htmlspecialchars($buildUrl($offset - $perPage)) . '">« назад</a> ';
                        }
                        if ($offset + $perPage < $total) {
                            echo '<a href="' . htmlspecialchars($buildUrl($offset + $perPage)) . '">вперёд »</a>';
                        }
                        echo '</p>';
                    }
                } else { ?>
                    <p class="sm"><i>Укажите вид сущности, ID/номер или текст для поиска.</i></p>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
        </tr>
    </table>
</body>

</html>