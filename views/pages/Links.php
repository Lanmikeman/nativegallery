<?php

use App\Services\DB;
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
                <h1>Ссылки</h1>
                <ul>
                    <?php
                    $links = DB::query('SELECT * FROM site_links ORDER BY sort ASC, id ASC');
                    if (empty($links)) {
                        echo '<li class="sm"><i>Ссылки пока не добавлены. Администратор может добавить их в <a href="/admin?type=Links">Админ → Ссылки</a>.</i></li>';
                    } else {
                        foreach ($links as $link) {
                            echo '<li><a href="' . htmlspecialchars($link['url']) . '" target="_blank" rel="noopener">' . htmlspecialchars($link['title']) . '</a></li>';
                        }
                    }
                    ?>
                </ul>
            </td>
        </tr>
        <tr>
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
        </tr>
    </table>
</body>

</html>