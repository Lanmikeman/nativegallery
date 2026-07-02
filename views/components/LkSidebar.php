<?php

use App\Services\Auth;

$lkActive = $lkActive ?? 'info';
$userId = Auth::userid();

$items = [
    'info' => ['href' => '/lk/', 'label' => 'Общая информация'],
    'upload' => ['href' => '/lk/upload.php', 'label' => 'Предложить медиа', 'bold' => true],
    'history' => ['href' => '/lk/history.php', 'label' => 'Журнал'],
    'konkurs' => ['href' => '/lk/konkurs.php', 'label' => 'Конкурс'],
    'dbedit' => ['href' => '/vehicle/edit', 'label' => 'Правка БД', 'bold' => true],
    'ticket' => ['href' => '/lk/ticket.php', 'label' => 'Мои заявки'],
    'profile' => ['href' => '/lk/profile.php', 'label' => 'Настройки профиля'],
    'photos' => ['href' => '/search?id=' . $userId, 'label' => 'Мои фотографии'],
    'fav' => ['href' => '/fav', 'label' => 'Избранные снимки'],
];

?>
<div class="lk-menu">
    <?php foreach ($items as $key => $item) {
        $isActive = $key === $lkActive;
        $label = htmlspecialchars($item['label']);
        $labelHtml = ($isActive || !empty($item['bold'])) ? '<b>' . $label . '</b>' : $label;
        ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="lk-menu-item<?= $isActive ? ' lk-menu-item--active' : '' ?>">
            <span class="lk-dir" aria-hidden="true"></span>
            <span class="lk-menu-label sm"><?= $labelHtml ?></span>
        </a>
    <?php } ?>
</div>