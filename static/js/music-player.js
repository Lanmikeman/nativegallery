(function (window) {
    'use strict';

    var STORAGE_KEY = 'ng_music_player_v2';
    var PLAYBACK_KEY = 'ng_music_playback_v1';
    var queue = [];
    var index = -1;
    var volume = 0.8;
    var barEl = null;
    var audio = null;
    var loadGeneration = 0;
    var playRequestId = 0;

    function savePlaybackState() {
        if (!audio) return;
        try {
            sessionStorage.setItem(PLAYBACK_KEY, JSON.stringify({
                src: audio.src,
                time: audio.currentTime,
                paused: audio.paused
            }));
        } catch (e) { /* ignore */ }
    }

    function isLiveStream(track) {
        if (!track) return false;
        return track.type === 'stream' || track.source_type === 'stream';
    }

    function isPlaying(a) {
        return a && !a.paused && !a.ended;
    }

    function requestPlay(a, opts) {
        opts = opts || {};
        var gen = loadGeneration;
        var reqId = ++playRequestId;
        var showError = opts.showError !== false;
        var seekTime = typeof opts.seekTime === 'number' ? opts.seekTime : null;

        function attemptPlay() {
            if (gen !== loadGeneration || reqId !== playRequestId) return;
            if (seekTime !== null && !isLiveStream(currentTrack())) {
                try { a.currentTime = seekTime; } catch (e) { /* ignore */ }
            }
            var p = a.play();
            if (!p || typeof p.then !== 'function') {
                updateBar();
                return;
            }
            p.then(function () {
                if (gen !== loadGeneration) return;
                updateBar();
            }).catch(function (err) {
                if (gen !== loadGeneration || reqId !== playRequestId) return;
                if (err && err.name === 'AbortError') return;
                setTimeout(function () {
                    if (gen !== loadGeneration || reqId !== playRequestId) return;
                    if (isPlaying(a)) return;
                    console.error('NgMusicPlayer play failed:', err);
                    if (showError && typeof Notify !== 'undefined') {
                        Notify.noty('danger', 'Не удалось начать воспроизведение');
                    }
                    updateBar();
                }, 900);
            });
        }

        function onReady() {
            a.removeEventListener('canplay', onReady);
            a.removeEventListener('error', onLoadError);
            attemptPlay();
        }

        function onLoadError() {
            a.removeEventListener('canplay', onReady);
            a.removeEventListener('error', onLoadError);
            if (gen !== loadGeneration) return;
            setTimeout(function () {
                if (gen !== loadGeneration) return;
                if (isPlaying(a)) return;
                if (showError && typeof Notify !== 'undefined') {
                    Notify.noty('danger', 'Не удалось воспроизвести трек');
                }
            }, 900);
        }

        if (a.readyState >= 2) {
            attemptPlay();
        } else {
            a.addEventListener('canplay', onReady);
            a.addEventListener('error', onLoadError);
            a.load();
        }
    }

    function setAudioSrc(a, resolved) {
        loadGeneration += 1;
        playRequestId += 1;
        a.pause();
        a.src = resolved;
    }

    function reconnectStream() {
        var track = currentTrack();
        if (!track || !isLiveStream(track)) return;
        var a = getAudio();
        var resolved = resolveAudioSrc(track.src);
        if (!resolved) return;
        setAudioSrc(a, resolved);
        requestPlay(a);
        updateBar();
    }

    function getAudio() {
        if (!window.__ngMusicAudio) {
            audio = new Audio();
            audio.preload = 'metadata';
            window.__ngMusicAudio = audio;
            audio.addEventListener('ended', function () {
                if (audio.paused) return;
                var track = currentTrack();
                if (isLiveStream(track)) {
                    reconnectStream();
                    return;
                }
                NgMusicPlayer.next();
            });
            audio.addEventListener('play', function () {
                savePlaybackState();
                updateBar();
            });
            audio.addEventListener('pause', function () {
                savePlaybackState();
                updateBar();
            });
            audio.addEventListener('error', function () {
                var gen = loadGeneration;
                setTimeout(function () {
                    if (gen !== loadGeneration) return;
                    if (isPlaying(audio)) return;
                    if (typeof Notify !== 'undefined') {
                        Notify.noty('danger', 'Не удалось воспроизвести трек');
                    }
                }, 900);
            });
            var lastSave = 0;
            audio.addEventListener('timeupdate', function () {
                var now = Date.now();
                if (now - lastSave < 2000) return;
                lastSave = now;
                savePlaybackState();
            });
        } else {
            audio = window.__ngMusicAudio;
        }
        audio.volume = volume;
        return audio;
    }

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
            src: item.src,
            source_type: item.source_type || ''
        };
    }

    function currentTrack() {
        if (index < 0 || index >= queue.length) return null;
        return queue[index];
    }

    function updateBar() {
        if (!barEl) {
            barEl = document.getElementById('ng-music-bar');
        }
        if (!barEl) return;

        var track = currentTrack();
        var titleEl = barEl.querySelector('.ng-music-nav__title') || barEl.querySelector('#ng-music-title-link');
        var artistEl = barEl.querySelector('.ng-music-nav__artist') || barEl.querySelector('#ng-music-artist');
        var playBtn = barEl.querySelector('[data-action="play"]');
        var a = window.__ngMusicAudio;
        var playing = a && !a.paused && track;

        barEl.classList.toggle('ng-music-idle', !track);
        barEl.classList.toggle('ng-music-playing', !!playing);

        if (titleEl) {
            titleEl.textContent = track ? track.title : 'Музыка';
            titleEl.setAttribute('href', '/music');
            titleEl.setAttribute('title', track ? track.title + ' — открыть библиотеку' : 'Открыть библиотеку');
        }

        if (artistEl) {
            if (track) {
                artistEl.textContent = track.artist || (track.type === 'stream' ? 'Поток' : '');
            } else {
                artistEl.textContent = '';
            }
        }

        if (playBtn) {
            playBtn.innerHTML = playing
                ? '<i class="fas fa-pause"></i>'
                : '<i class="fas fa-play"></i>';
        }

        var volInput = barEl.querySelector('[data-action="volume"]');
        if (volInput) volInput.value = Math.round(volume * 100);

        var muteBtn = barEl.querySelector('[data-action="mute"]');
        if (muteBtn) {
            var icon = muteBtn.querySelector('i');
            if (icon) {
                icon.className = volume === 0 ? 'fas fa-volume-mute' : (volume < 0.45 ? 'fas fa-volume-down' : 'fas fa-volume-up');
            }
        }
    }

    function resolveAudioSrc(src) {
        if (!src) return '';
        if (/^https?:\/\//i.test(src)) return src;
        if (src.charAt(0) === '/') return src;
        return '/' + src;
    }

    function playAt(i) {
        if (i < 0 || i >= queue.length) return;
        index = i;
        var track = queue[index];
        var a = getAudio();
        var resolved = resolveAudioSrc(track.src);
        if (!resolved) return;
        var seekTime = null;
        try {
            var pb = JSON.parse(sessionStorage.getItem(PLAYBACK_KEY) || 'null');
            if (pb && pb.src && resolved.indexOf(pb.src) >= 0 && !isLiveStream(track) && pb.time > 1 && !pb.paused) {
                seekTime = pb.time;
            }
        } catch (e) { /* ignore */ }

        setAudioSrc(a, resolved);
        requestPlay(a, { seekTime: seekTime });
        saveState();
        updateBar();
        window.dispatchEvent(new CustomEvent('ngmusic:change', { detail: { track: track, index: index } }));
    }

    function bindBarEvents() {
        if (!barEl || barEl.__ngMusicBound) return;
        barEl.__ngMusicBound = true;

        barEl.addEventListener('click', function (e) {
            e.stopPropagation();
            var btn = e.target.closest('[data-action]');
            if (!btn || btn.getAttribute('data-action') === 'volume') return;
            e.preventDefault();
            var action = btn.getAttribute('data-action');
            if (action === 'play') NgMusicPlayer.toggle();
            if (action === 'prev') NgMusicPlayer.prev();
            if (action === 'next') NgMusicPlayer.next();
            if (action === 'mute') NgMusicPlayer.toggleMute();
        });

        var volInput = barEl.querySelector('[data-action="volume"]');
        if (volInput) {
            volInput.addEventListener('input', function (e) {
                e.stopPropagation();
                volume = parseInt(volInput.value, 10) / 100;
                if (audio) audio.volume = volume;
                saveState();
                updateBar();
            });
            volInput.addEventListener('click', function (e) { e.stopPropagation(); });
        }
    }

    var NgMusicPlayer = {
        init: function () {
            loadState();
            barEl = document.getElementById('ng-music-bar');
            if (!barEl) return;

            getAudio();
            bindBarEvents();
            updateBar();

            if (!window.__ngMusicPlayerReady) {
                window.__ngMusicPlayerReady = true;
                var track = currentTrack();
                var a = getAudio();
                if (track && a.src && a.src.indexOf(track.src) >= 0 && !a.paused) {
                    updateBar();
                }
            }
        },

        setQueue: function (items, startIndex) {
            queue = (items || []).map(normalizeTrack).filter(Boolean);
            index = typeof startIndex === 'number' ? startIndex : 0;
            if (queue.length === 0) {
                index = -1;
                if (audio) {
                    audio.pause();
                }
            } else {
                playAt(Math.min(Math.max(index, 0), queue.length - 1));
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
            if (!currentTrack()) {
                if (window.ngSpaNavigate) window.ngSpaNavigate('/music');
                else window.location.href = '/music';
                return;
            }
            var a = getAudio();
            if (a.paused) {
                if (!a.src) playAt(index);
                else requestPlay(a);
            } else {
                a.pause();
            }
            updateBar();
        },

        toggleMute: function () {
            volume = volume > 0 ? 0 : 0.8;
            if (audio) audio.volume = volume;
            saveState();
            updateBar();
        },

        prev: function () {
            if (queue.length === 0) return;
            var a = getAudio();
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
            if (nextIdx >= queue.length) {
                if (isLiveStream(currentTrack())) {
                    reconnectStream();
                    return;
                }
                nextIdx = 0;
            }
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
                audio.removeAttribute('src');
            }
            saveState();
            updateBar();
        }
    };

    window.NgMusicPlayer = NgMusicPlayer;

    function boot() {
        NgMusicPlayer.init();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.addEventListener('ng:navigate', function () {
        barEl = null;
        NgMusicPlayer.init();
    });
})(window);