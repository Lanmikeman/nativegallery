<?php

use App\Services\Auth;

$user = new \App\Models\User(Auth::userid());

if (!isset($_GET['type']) || $_GET['type'] != 'Photo') {
    if ($user->i('admin') === 2) {
        header('Location: ?type=Photo');
    }
}

$cacheBust = NGALLERY['root']['cloudflare-caching'] === true ? '?' . time() : '';

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'; ?>
    <title>Админ-панель — <?= htmlspecialchars(NGALLERY['root']['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="/static/css/tabs.css<?= $cacheBust ?>">
    <link rel="stylesheet" href="/static/css/admin.css<?= $cacheBust ?>">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.5/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="/static/js/changeTab.js<?= $cacheBust ?>" defer></script>
</head>

<body class="admin-page">
    <div id="backgr"></div>
    <table class="tmain">
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'; ?>
        <tr>
            <td class="main">
                <div class="admin-layout">
                    <?php \App\Controllers\AdminController::loadMenu(); ?>
                    <div class="admin-panel">
                        <?php \App\Controllers\AdminController::loadContent(); ?>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'; ?>
        </tr>
    </table>
</body>

</html>