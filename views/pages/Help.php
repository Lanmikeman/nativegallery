<?php

use App\Services\Auth;

$isLoggedIn = Auth::userid() > 0;

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
                <h1>Помощь по сайту</h1>

                <div class="p20" style="padding: 20px">
                    <p>Раздел содержит информацию о работе сайта <?= htmlspecialchars(NGALLERY['root']['title']) ?>.</p>

                    <p><a href="/rules/"><b>Правила сайта</b> (основные)</a></p>

                    <h3>Автору фотографий</h3>
                    <ul>
                        <li><a href="/rules/pub/">Общие требования к фотографиям</a></li>
                        <li><a href="/rules/photo/"><b>Правила подписи фотографий</b></a></li>
                        <li><a href="/rules/video/">Правила видеокаталога</a></li>
                        <?php if ($isLoggedIn) { ?>
                            <li><a href="/lk/upload">Загрузка фотографий</a></li>
                            <li><a href="/lk/">Личный кабинет</a></li>
                        <?php } else { ?>
                            <li><a href="/register">Регистрация</a> и <a href="/login">вход</a> для публикации фото</li>
                        <?php } ?>
                    </ul>

                    <h3>Фотоконкурс</h3>
                    <ul>
                        <li><a href="/voting">Голосование</a></li>
                        <li><a href="/voting/waiting">Претенденты</a></li>
                        <li><a href="/voting/results">Победители</a></li>
                        <?php if ($isLoggedIn) { ?>
                            <li><a href="/voting/sendpretend">Подать фото на конкурс</a></li>
                        <?php } ?>
                    </ul>

                    <h3>Разное</h3>
                    <ul>
                        <li><a href="/tour">Обзор возможностей сайта</a></li>
                        <li><a href="/about">О сервере</a></li>
                        <li><a href="/search">Поиск фотографий</a></li>
                        <li><a href="/links">Каталог ссылок</a></li>
                    </ul>
                </div>
            </td>
        </tr>
        <tr>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'; ?>
        </tr>
    </table>
</body>

</html>