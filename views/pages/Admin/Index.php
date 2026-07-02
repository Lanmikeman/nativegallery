<?php

use App\Controllers\AdminController;
use App\Services\{AdminNav, Auth};

$user = new \App\Models\User(Auth::userid());
$adminPageType = AdminController::resolvePage();
$adminPageLabel = AdminNav::label($adminPageType);

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
            <td class="main admin-main">
                <div class="admin-layout">
                    <?php AdminController::loadMenu(); ?>
                    <div class="admin-panel">
                        <div class="admin-panel__crumb">
                            <span class="admin-panel__crumb-root">Админ-панель</span>
                            <span class="admin-panel__crumb-sep">/</span>
                            <span class="admin-panel__crumb-current"><?= htmlspecialchars($adminPageLabel) ?></span>
                        </div>
                        <?php AdminController::loadContent(); ?>
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