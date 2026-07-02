<?php

use App\Core\Page;
use App\Services\{AdminAccess, AdminNav, DB};

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

$sections = AdminNav::sections($nonr, $nonrE);

?>
<aside class="admin-nav" aria-label="Меню админ-панели">
    <div class="admin-nav__head">
        <h2 class="admin-nav__title">Админ-панель</h2>
        <a href="/" class="admin-nav__back"><i class="fas fa-arrow-left"></i> На сайт</a>
    </div>
    <div class="admin-nav__items">
        <?php foreach ($sections as $sectionTitle => $items) { ?>
            <div class="admin-nav__section">
                <div class="admin-nav__section-title"><?= htmlspecialchars($sectionTitle) ?></div>
                <?php foreach ($items as $item) { ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="admin-nav__link<?= $navClass($item['type']) ?>">
                        <i class="fas <?= htmlspecialchars($item['icon']) ?> admin-nav__icon"></i>
                        <span class="admin-nav__label"><?= htmlspecialchars($item['label']) ?></span>
                        <?= $item['badge'] ?? '' ?>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</aside>