<?php

use App\Services\SitePage;

$uriParts = explode('/', rtrim(strtok($_SERVER['REQUEST_URI'], '?'), '/'));
$pageId = (int) ($uriParts[2] ?? 0);
$page = SitePage::findById($pageId);

if ($page === null) {
    Page::set('Errors/404');
    return;
}

$pageTitle = trim((string) ($page['title'] ?? ''));
if ($pageTitle === '') {
    $pageTitle = 'Страница';
}
$body = (string) ($page['body'] ?? '');

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'; ?>
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(NGALLERY['root']['title']) ?></title>
</head>

<body>
    <div id="backgr"></div>
    <table class="tmain">
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'; ?>
        <tr>
            <td class="main">
                <header class="site-page__head">
                    <h1 class="site-page__title"><?= htmlspecialchars($pageTitle) ?></h1>
                    <?= SitePage::editNoticeHtml($page) ?>
                </header>
                <div class="p20 break-links site-page__body">
                    <?= $body ?>
                </div>
            </td>
        </tr>
        <tr>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'; ?>
        </tr>
    </table>
</body>

</html>