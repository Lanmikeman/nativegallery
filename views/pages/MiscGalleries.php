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
                <h1>Разные фотогалереи</h1>
                <ul>
                    <?php
                    $galleries = DB::query('SELECT * FROM galleries WHERE opened = 1 ORDER BY title ASC');
                    if (empty($galleries)) {
                        echo '<li class="sm"><i>Галереи пока не созданы. Администратор может добавить их в <a href="/admin?type=Galleries">Админ → Галереи</a>.</i></li>';
                    } else {
                        foreach ($galleries as $g) {
                            echo '<li><a href="/article/' . $g['id'] . '">' . htmlspecialchars($g['title']) . '</a></li>';
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