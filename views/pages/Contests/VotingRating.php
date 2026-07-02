<?php

use App\Services\DB;
use App\Models\User;

$rows = DB::query(
    'SELECT cw.photo_id, cw.place, cw.contest_id, p.user_id
     FROM contests_winners cw
     INNER JOIN photos p ON p.id = cw.photo_id
     WHERE cw.place BETWEEN 1 AND 3
     ORDER BY cw.date DESC'
);

$pointsForPlace = static function (int $place): float {
    return match ($place) {
        1 => 1.0,
        2 => 0.5,
        3 => 0.25,
        default => 0.0,
    };
};

$iconForPlace = static function (int $place): string {
    return match ($place) {
        1 => 'vs3',
        2 => 'vs2',
        3 => 'vs1',
        default => 'vs1',
    };
};

$authors = [];
foreach ($rows as $row) {
    $userId = (int) $row['user_id'];
    if (!isset($authors[$userId])) {
        $authors[$userId] = [
            'points' => 0.0,
            'wins' => [],
        ];
    }

    $place = (int) $row['place'];
    $authors[$userId]['points'] += $pointsForPlace($place);
    $authors[$userId]['wins'][] = [
        'photo_id' => (int) $row['photo_id'],
        'place' => $place,
        'contest_id' => (int) $row['contest_id'],
    ];
}

uasort($authors, static function (array $a, array $b): int {
    return $b['points'] <=> $a['points'];
});

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
                <center>
                    <h1>Рейтинг победителей</h1>
                    <?php $contestNavActive = 'rating'; include $_SERVER['DOCUMENT_ROOT'] . '/views/components/ContestNav.php'; ?>

                    <div class="sm" style="margin: 12px 0 20px">
                        <img src="/static/img/vs3.png" alt=""> 1-е место (+1) &nbsp;
                        <img src="/static/img/vs2.png" alt=""> 2-е место (+½) &nbsp;
                        <img src="/static/img/vs1.png" alt=""> 3-е место (+¼)
                    </div>

                    <?php if ($authors === []) { ?>
                        <div class="p20" style="margin: 20px 0">
                            <p>Рейтинг появится после первых завершённых конкурсов.</p>
                        </div>
                    <?php } else {
                        $rank = 1;
                        foreach ($authors as $userId => $data) {
                            $user = new User((int) $userId);
                            $score = rtrim(rtrim(number_format($data['points'], 2, '.', ''), '0'), '.');
                            echo '<div class="p20" style="text-align:left; max-width:900px; margin:0 auto 18px">'
                                . '<div style="margin-bottom:8px"><b>' . $rank . '.</b> '
                                . '<a href="/author/' . (int) $userId . '/"><b>' . htmlspecialchars((string) $user->i('username')) . '</b></a>'
                                . ' <span class="sm">(' . $score . ')</span></div><div>';

                            foreach ($data['wins'] as $win) {
                                $icon = $iconForPlace($win['place']);
                                echo '<a href="/photo/' . $win['photo_id'] . '/" title="Конкурс #' . $win['contest_id'] . '">'
                                    . '<img src="/static/img/' . $icon . '.png" alt="" style="margin:0 4px 4px 0">'
                                    . '</a>';
                            }

                            echo '</div></div>';
                            $rank++;
                        }
                    } ?>

                    <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/ContestNav.php'; ?>
                </center>
            </td>
        </tr>
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'; ?>
    </table>
</body>

</html>