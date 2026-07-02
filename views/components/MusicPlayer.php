<?php

use App\Services\Auth;

if (Auth::userid() <= 0) {
    return;
}
?>
<div id="ng-music-bar" class="ng-music-nav" aria-label="Музыкальный плеер">
    <div class="ng-music-nav__controls">
        <button type="button" class="ng-music-nav__btn" data-action="prev" title="Предыдущий"><i class="fas fa-step-backward"></i></button>
        <button type="button" class="ng-music-nav__btn ng-music-nav__btn--play" data-action="play" title="Воспроизведение"><i class="fas fa-play"></i></button>
        <button type="button" class="ng-music-nav__btn" data-action="next" title="Следующий"><i class="fas fa-step-forward"></i></button>
    </div>
    <div class="ng-music-nav__info">
        <a href="/music" class="ng-music-nav__title" id="ng-music-title-link" title="Открыть библиотеку">Музыка</a>
        <span class="ng-music-nav__artist" id="ng-music-artist"></span>
    </div>
    <button type="button" class="ng-music-nav__btn ng-music-nav__btn--vol" data-action="mute" title="Громкость"><i class="fas fa-volume-up"></i></button>
    <input type="range" class="ng-music-nav__vol" min="0" max="100" value="80" data-action="volume" aria-label="Громкость">
</div>