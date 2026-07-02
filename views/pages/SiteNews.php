<?php

use App\Services\{DB, Date};
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
                        echo '<div class="p20" style="margin-bottom:10px">';
                        echo '<h4>' . Date::chronologyDate((int) $n['time']) . '</h4>';
                        echo '<div class="break-links">' . $n['body'] . '</div>';
                        echo '</div>';
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