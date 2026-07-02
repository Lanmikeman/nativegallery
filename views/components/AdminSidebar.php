<?php

use App\Core\Page;
use App\Services\{AdminAccess, DB};

$currentType = trim((string) ($_GET['type'] ?? ''));
if ($currentType === '' || !Page::exists('Admin/' . $currentType)) {
    $currentType = 'General';
}

$nonr = '';
$nonrE = '';
$nonreviewedImgs = (int) DB::query('SELECT COUNT(*) AS cnt FROM photos WHERE moderated = 0')[0]['cnt'];
if ($nonreviewedImgs > 0) {
    $nonr = '<span class="badge text-bg-danger admin-nav__badge">' . $nonreviewedImgs . '</span>';
}
$nonreviewedEntities = (int) DB::query('SELECT COUNT(*) AS cnt FROM entities_requests WHERE status = 0')[0]['cnt'];
if ($nonreviewedEntities > 0) {
    $nonrE = '<span class="badge text-bg-danger admin-nav__badge">' . $nonreviewedEntities . '</span>';
}

$navClass = static function (string $type) use ($currentType): string {
    return $type === $currentType ? ' admin-nav__link--active' : '';
};

$items = [
    ['type' => 'General', 'href' => '/admin', 'icon' => 'fa-users-cog', 'label' => 'Пользователи'],
    ['type' => 'Photo', 'href' => '/admin?type=Photo', 'icon' => 'fa-camera', 'label' => 'Фотографии', 'badge' => $nonr],
    ['type' => 'Galleries', 'href' => '/admin?type=Galleries', 'icon' => 'fa-images', 'label' => 'Галереи'],
    ['type' => 'News', 'href' => '/admin?type=News', 'icon' => 'fa-bullhorn', 'label' => 'Новости сайта'],
    ['type' => 'Chronology', 'href' => '/admin?type=Chronology', 'icon' => 'fa-clock', 'label' => 'Хронология'],
    ['type' => 'Links', 'href' => '/admin?type=Links', 'icon' => 'fa-link', 'label' => 'Ссылки'],
    ['type' => 'Contests', 'href' => '/admin?type=Contests', 'icon' => 'fa-trophy', 'label' => 'Фотоконкурсы'],
    ['type' => 'Entities', 'href' => '/admin?type=Entities', 'icon' => 'fa-cubes', 'label' => 'Сущности'],
    ['type' => 'Models', 'href' => '/admin?type=Models', 'icon' => 'fa-database', 'label' => 'База моделей', 'badge' => $nonrE],
    ['type' => 'GeoDB', 'href' => '/admin?type=GeoDB', 'icon' => 'fa-globe', 'label' => 'GeoDB'],
    ['type' => 'Pages', 'href' => '/admin?type=Pages', 'icon' => 'fa-file-alt', 'label' => 'Страницы'],
    ['type' => 'AuthSettings', 'href' => '/admin?type=AuthSettings', 'icon' => 'fa-key', 'label' => 'Авторизация'],
    ['type' => 'Settings', 'href' => '/admin?type=Settings', 'icon' => 'fa-cog', 'label' => 'Настройки'],
];

?>
<nav class="admin-nav">
    <div class="admin-nav__head">
        <h2 class="admin-nav__title">Админ-панель</h2>
        <a href="/" class="admin-nav__back"><i class="fas fa-arrow-left"></i> На сайт</a>
    </div>
    <div class="admin-nav__items">
        <?php foreach ($items as $item) { ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="admin-nav__link<?= $navClass($item['type']) ?>">
                <i class="fas <?= htmlspecialchars($item['icon']) ?>"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
                <?= $item['badge'] ?? '' ?>
            </a>
        <?php } ?>
        <?php if (AdminAccess::isOwner()) { ?>
            <a href="/admin?type=ServerSettings" class="admin-nav__link<?= $navClass('ServerSettings') ?>">
                <i class="fas fa-server"></i>
                <span>Сервер</span>
                <span class="badge text-bg-danger admin-nav__badge">OWNER</span>
            </a>
        <?php } ?>
    </div>
</nav>