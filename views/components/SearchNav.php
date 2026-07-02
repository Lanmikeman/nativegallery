<?php
$current = $searchNavActive ?? 'photos';
$tabs = [
    'photos' => ['label' => 'Поиск фотографий', 'url' => '/search'],
    'vehicles' => ['label' => 'Поиск ТС', 'url' => '/vsearch'],
    'comments' => ['label' => 'Поиск комментариев', 'url' => '/csearch'],
    'authors' => ['label' => 'Поиск авторов', 'url' => '/authors'],
];
?>
<p class="sm" style="margin-bottom:20px">
    <?php foreach ($tabs as $key => $tab) {
        if ($key === $current) {
            echo '<b>' . $tab['label'] . '</b>';
        } else {
            echo '<a href="' . $tab['url'] . '">' . $tab['label'] . '</a>';
        }
        if ($key !== 'authors') {
            echo ' &nbsp;·&nbsp; ';
        }
    } ?>
</p>