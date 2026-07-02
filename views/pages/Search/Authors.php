<?php

use App\Services\Search\AuthorSearch;

$params = [
    'q' => trim($_GET['q'] ?? ''),
    'st' => $_GET['st'] ?? 0,
];

$hasCriteria = !empty($params['q']);
$items = $hasCriteria ? AuthorSearch::fetch($params) : [];
$total = $hasCriteria ? AuthorSearch::count($params) : 0;
$offset = max(0, (int) $params['st']);
$perPage = AuthorSearch::PER_PAGE;

$searchNavActive = 'authors';
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
    return '/authors' . ($qs ? '?' . $qs : '');
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
                <h1>Поиск авторов</h1>
                <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/SearchNav.php'); ?>

                <form method="get" class="p20" style="margin-bottom:20px">
                    <table>
                        <tr>
                            <td class="sm" style="padding-right:10px">Никнейм или email:</td>
                            <td>
                                <input type="text" name="q" value="<?= htmlspecialchars($params['q']) ?>" class="form-control" style="max-width:300px">
                                <input type="submit" class="btn btn-primary" value="Найти" style="margin-left:10px">
                            </td>
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
                                    <th></th>
                                    <th>ID</th>
                                    <th>Никнейм</th>
                                    <th>Фото</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $u) { ?>
                                    <tr>
                                        <td><img src="<?= htmlspecialchars($u['photourl']) ?>" width="35" onerror="this.src='/static/img/avatar.png'"></td>
                                        <td><?= (int) $u['id'] ?></td>
                                        <td><a href="/author/<?= (int) $u['id'] ?>/"><?= htmlspecialchars($u['username']) ?></a></td>
                                        <td><?= (int) $u['photo_count'] ?></td>
                                        <td><a href="/search?id=<?= (int) $u['id'] ?>">Все фото</a></td>
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
                    <p class="sm"><i>Введите никнейм или часть email для поиска.</i></p>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
        </tr>
    </table>
</body>

</html>