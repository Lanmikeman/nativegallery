<?php

use App\Services\{Auth, AudioLibrary};

$uploadAllowed = AudioLibrary::uploadAllowed();
$streamsAllowed = AudioLibrary::streamsAllowed();
$tablesExist = AudioLibrary::tablesExist();

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
                <div class="ng-music-page">
                    <h1>Музыка</h1>
                    <p>Личная библиотека: загрузка файлов, HTTP-потоки и плейлисты M3U. Компактный плеер — в верхней панели рядом с вашим ником; при переходе по меню музыка не прерывается.</p>

                    <div id="ng-music-migration-hint" class="alert alert-warning" style="display:<?= $tablesExist ? 'none' : 'block' ?>;">
                        Для работы раздела примените миграцию <code>sqlcore/sql_0010.sql</code>.
                    </div>

                    <div class="ng-music-tabs">
                        <button type="button" class="ng-music-tab active" data-tab="library">Библиотека</button>
                        <?php if ($uploadAllowed) { ?>
                            <button type="button" class="ng-music-tab" data-tab="upload">Загрузка</button>
                        <?php } ?>
                        <?php if ($streamsAllowed) { ?>
                            <button type="button" class="ng-music-tab" data-tab="streams">Потоки и ссылки</button>
                            <button type="button" class="ng-music-tab" data-tab="m3u">M3U</button>
                        <?php } ?>
                        <button type="button" class="ng-music-tab" data-tab="playlists">Плейлисты</button>
                    </div>

                    <div id="ng-music-panel-library" class="ng-music-panel active">
                        <h3>Радио сайта</h3>
                        <ul id="ng-music-global-streams-list" class="ng-music-list"></ul>
                        <h3>Треки</h3>
                        <ul id="ng-music-tracks-list" class="ng-music-list"></ul>
                        <h3>Мои потоки</h3>
                        <ul id="ng-music-streams-list" class="ng-music-list"></ul>
                    </div>

                    <?php if ($uploadAllowed) { ?>
                    <div id="ng-music-panel-upload" class="ng-music-panel">
                        <form id="ng-music-upload-form" class="ng-music-form" enctype="multipart/form-data">
                            <label>Аудиофайл (MP3, OGG, WAV, FLAC…)</label>
                            <input type="file" name="audio" accept="audio/*" required>
                            <label>Название</label>
                            <input type="text" name="title" placeholder="Необязательно">
                            <label>Исполнитель</label>
                            <input type="text" name="artist" placeholder="Необязательно">
                            <button type="submit" class="btn btn-primary">Загрузить</button>
                        </form>
                    </div>
                    <?php } ?>

                    <?php if ($streamsAllowed) { ?>
                    <div id="ng-music-panel-streams" class="ng-music-panel">
                        <form id="ng-music-url-form" class="ng-music-form">
                            <h3>Прямая ссылка на файл</h3>
                            <label>URL</label>
                            <input type="url" name="url" required placeholder="https://example.com/track.mp3">
                            <label>Название</label>
                            <input type="text" name="title" placeholder="Необязательно">
                            <label>Исполнитель</label>
                            <input type="text" name="artist" placeholder="Необязательно">
                            <button type="submit" class="btn btn-primary">Добавить в библиотеку</button>
                        </form>
                        <form id="ng-music-stream-form" class="ng-music-form">
                            <h3>HTTP-поток (радио)</h3>
                            <label>URL потока</label>
                            <input type="url" name="url" required placeholder="https://stream.example/live">
                            <label>Название станции</label>
                            <input type="text" name="title" placeholder="Необязательно">
                            <button type="submit" class="btn btn-primary">Сохранить поток</button>
                        </form>
                    </div>

                    <div id="ng-music-panel-m3u" class="ng-music-panel">
                        <p>Вставьте текст плейлиста M3U или содержимое файла .m3u. Парсинг выполняется в браузере; внешние URL загружаются только при импорте в библиотеку (без SSRF на сервере).</p>
                        <div class="ng-music-form">
                            <label>Содержимое M3U</label>
                            <textarea id="ng-music-m3u-text" placeholder="#EXTM3U&#10;#EXTINF:-1,Artist - Title&#10;https://example.com/track.mp3"></textarea>
                            <button type="button" id="ng-music-m3u-play" class="btn btn-secondary">Воспроизвести без сохранения</button>
                            <button type="button" id="ng-music-m3u-import" class="btn btn-primary">Импортировать в библиотеку</button>
                        </div>
                    </div>
                    <?php } ?>

                    <div id="ng-music-panel-playlists" class="ng-music-panel">
                        <div class="ng-music-form" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                            <div style="flex:1;min-width:200px">
                                <label>Новый плейлист</label>
                                <input type="text" id="ng-music-new-playlist-title" placeholder="Название">
                            </div>
                            <button type="button" id="ng-music-create-playlist" class="btn btn-primary">Создать</button>
                        </div>
                        <ul id="ng-music-playlists-list" class="ng-music-list"></ul>
                    </div>
                </div>
            </td>
        </tr>
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'; ?>
    </table>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/views/components/AssetHelper.php'; ?>
    <script src="<?= ng_asset('/static/js/music-page.js') ?>"></script>
</body>

</html>