(function (window) {
    'use strict';

    var STORAGE_KEY = 'ng_music_player_v1';
    var audio = null;
    var queue = [];
    var index = -1;
    var volume = 0.8;
    var barEl = null;

    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            var data = JSON.parse(raw);
            if (Array.isArray(data.queue)) queue = data.queue;
            if (typeof data.index === 'number') index = data.index;
            if (typeof data.volume === 'number') volume = data.volume;
        } catch (e) { /* ignore */ }
    }

    function saveState() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                queue: queue,
                index: index,
                volume: volume
            }));
        } catch (e) { /* ignore */ }
    }

    function normalizeTrack(item) {
        if (!item || !item.src) return null;
        return {
            type: item.type || 'track',
            title: item.title || 'Без названия',
            artist: item.artist || '',
            src: item.src
        };
    }

    function currentTrack() {
        if (index < 0 || index >= queue.length) return null;
        return queue[index];
    }

    function updateBar() {
        if (!barEl) return;
        var track = currentTrack();
        var titleEl = barEl.querySelector('.ng-music-bar__title');
        var artistEl = barEl.querySelector('.ng-music-bar__artist');
        var playBtn = barEl.querySelector('[data-action="play"]');

        if (!track) {
            barEl.classList.add('ng-music-hidden');
            document.body.classList.remove('ng-music-bar-visible');
            return;
        }

        barEl.classList.remove('ng-music-hidden');
        document.body.classList.add('ng-music-bar-visible');
        if (titleEl) titleEl.textContent = track.title;
        if (artistEl) artistEl.textContent = track.artist || (track.type === 'stream' ? 'Поток' : '');
        if (playBtn) {
            playBtn.innerHTML = audio && !audio.paused
                ? '<i class="fas fa-pause"></i>'
                : '<i class="fas fa-play"></i>';
        }
        var volInput = barEl.querySelector('[data-action="volume"]');
        if (volInput) volInput.value = Math.round(volume * 100);
    }

    function ensureAudio() {
        if (!audio) {
            audio = new Audio();
            audio.preload = 'metadata';
            audio.addEventListener('ended', function () {
                NgMusicPlayer.next();
            });
            audio.addEventListener('play', updateBar);
            audio.addEventListener('pause', updateBar);
            audio.addEventListener('error', function () {
                if (typeof Notify !== 'undefined') {
                    Notify.noty('danger', 'Не удалось воспроизвести трек');
                }
            });
        }
        audio.volume = volume;
        return audio;
    }

    function playAt(i) {
        if (i < 0 || i >= queue.length) return;
        index = i;
        var track = queue[index];
        var a = ensureAudio();
        a.src = track.src;
        a.play().catch(function () {
            updateBar();
        });
        saveState();
        updateBar();
        window.dispatchEvent(new CustomEvent('ngmusic:change', { detail: { track: track, index: index } }));
    }

    var NgMusicPlayer = {
        init: function (barSelector) {
            loadState();
            barEl = document.querySelector(barSelector || '#ng-music-bar');
            if (!barEl) return;

            barEl.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-action]');
                if (!btn) return;
                var action = btn.getAttribute('data-action');
                if (action === 'play') NgMusicPlayer.toggle();
                if (action === 'prev') NgMusicPlayer.prev();
                if (action === 'next') NgMusicPlayer.next();
            });

            var volInput = barEl.querySelector('[data-action="volume"]');
            if (volInput) {
                volInput.addEventListener('input', function () {
                    volume = parseInt(volInput.value, 10) / 100;
                    if (audio) audio.volume = volume;
                    saveState();
                });
            }

            updateBar();
        },

        setQueue: function (items, startIndex) {
            queue = (items || []).map(normalizeTrack).filter(Boolean);
            index = typeof startIndex === 'number' ? startIndex : 0;
            if (queue.length === 0) {
                index = -1;
                if (audio) {
                    audio.pause();
                    audio.src = '';
                }
            } else {
                playAt(Math.min(index, queue.length - 1));
            }
            saveState();
            updateBar();
        },

        play: function (item) {
            var t = normalizeTrack(item);
            if (!t) return;
            queue = [t];
            index = 0;
            playAt(0);
        },

        addToQueue: function (item) {
            var t = normalizeTrack(item);
            if (!t) return;
            queue.push(t);
            if (index < 0) {
                playAt(0);
            } else {
                saveState();
            }
            updateBar();
        },

        playAll: function (items, startIndex) {
            this.setQueue(items, startIndex || 0);
        },

        toggle: function () {
            if (!currentTrack()) return;
            var a = ensureAudio();
            if (a.paused) {
                if (!a.src) playAt(index);
                else a.play();
            } else {
                a.pause();
            }
            updateBar();
        },

        prev: function () {
            if (queue.length === 0) return;
            var a = ensureAudio();
            if (a.currentTime > 3) {
                a.currentTime = 0;
                return;
            }
            var nextIdx = index - 1;
            if (nextIdx < 0) nextIdx = queue.length - 1;
            playAt(nextIdx);
        },

        next: function () {
            if (queue.length === 0) return;
            var nextIdx = index + 1;
            if (nextIdx >= queue.length) nextIdx = 0;
            playAt(nextIdx);
        },

        getQueue: function () {
            return queue.slice();
        },

        getIndex: function () {
            return index;
        },

        clear: function () {
            queue = [];
            index = -1;
            if (audio) {
                audio.pause();
                audio.src = '';
            }
            saveState();
            updateBar();
        }
    };

    window.NgMusicPlayer = NgMusicPlayer;

    document.addEventListener('DOMContentLoaded', function () {
        NgMusicPlayer.init('#ng-music-bar');
    });
})(window);