<?php

/** @var string $contestNavActive voting|results|rating|waiting */

$contestNavActive = $contestNavActive ?? 'voting';

$items = [
    'voting' => ['label' => 'Голосование', 'href' => '/voting'],
    'results' => ['label' => 'Победители', 'href' => '/voting/results'],
    'rating' => ['label' => 'Рейтинг', 'href' => '/voting/rating'],
    'waiting' => ['label' => 'Претенденты', 'href' => '/voting/waiting'],
];

$parts = [];
foreach ($items as $key => $item) {
    if ($key === $contestNavActive) {
        $parts[] = '<b>' . $item['label'] . '</b>';
    } else {
        $parts[] = '<a href="' . $item['href'] . '">' . $item['label'] . '</a>';
    }
}

echo '<p class="narrow" style="font-size:19px">' . implode(' &nbsp;&middot;&nbsp; ', $parts) . '</p>';