<?php

use App\Services\Auth;

if (Auth::userid() <= 0) {
    return;
}
?>
<link rel="stylesheet" href="/static/css/music-player.css<?php if (NGALLERY['root']['cloudflare-caching'] === true) { echo '?' . time(); } ?>">
<div id="ng-music-bar" class="ng-music-bar ng-music-hidden" aria-label="Музыкальный плеер">
    <div class="ng-music-bar__controls">
        <button type="button" class="ng-music-bar__btn" data-action="prev" title="Предыдущий"><i class="fas fa-step-backward"></i></button>
        <button type="button" class="ng-music-bar__btn" data-action="play" title="Воспроизведение"><i class="fas fa-play"></i></button>
        <button type="button" class="ng-music-bar__btn" data-action="next" title="Следующий"><i class="fas fa-step-forward"></i></button>
    </div>
    <div class="ng-music-bar__info">
        <div class="ng-music-bar__title">—</div>
        <div class="ng-music-bar__artist"></div>
    </div>
    <div class="ng-music-bar__volume">
        <i class="fas fa-volume-up"></i>
        <input type="range" min="0" max="100" value="80" data-action="volume" aria-label="Громкость">
    </div>
</div>
<script src="/static/js/music-player.js<?php if (NGALLERY['root']['cloudflare-caching'] === true) { echo '?' . time(); } ?>"></script>