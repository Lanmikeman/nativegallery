<?php

use App\Services\{DB, SiteNews};
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
                <h1>Новости сайта</h1>

                <?php
                $news = DB::query('SELECT * FROM news ORDER BY time DESC');
                if (empty($news)) {
                    echo '<p class="sm"><i>Новостей пока нет. Администратор может добавить их в разделе <a href="/admin?type=News">Админ → Новости сайта</a>.</i></p>';
                } else {
                    foreach ($news as $n) {
                        echo SiteNews::renderItemHtml($n, 'chronology');
                    }
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