(function ($, window) {
    'use strict';

    var library = { tracks: [], streams: [], playlists: [] };

    function apiPost(url, data, isMultipart) {
        var opts = {
            url: url,
            type: 'POST',
            dataType: 'json',
            data: data || {}
        };
        if (isMultipart) {
            opts.processData = false;
            opts.contentType = false;
        }
        return $.ajax(opts);
    }

    function notifyOk(msg) {
        if (typeof Notify !== 'undefined') Notify.noty('success', msg);
    }

    function notifyErr(msg) {
        if (typeof Notify !== 'undefined') Notify.noty('danger', msg);
    }

    function parseM3u(text) {
        var lines = (text || '').split(/\r?\n/);
        var entries = [];
        var pendingTitle = '';
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            if (!line || line === '#EXTM3U') continue;
            if (line.indexOf('#EXTINF:') === 0) {
                var comma = line.lastIndexOf(',');
                pendingTitle = comma >= 0 ? line.substring(comma + 1).trim() : '';
                continue;
            }
            if (line.charAt(0) === '#') continue;
            var url = line;
            if (!/^https?:\/\//i.test(url)) continue;
            entries.push({
                url: url,
                title: pendingTitle || url.split('/').pop() || url
            });
            pendingTitle = '';
        }
        return entries;
    }

    function trackRow(item, kind) {
        var artist = item.artist ? '<span class="ng-music-list__artist"> — ' + escapeHtml(item.artist) + '</span>' : '';
        var badge = kind === 'stream' ? ' <small>(поток)</small>' : (item.source_type === 'url' ? ' <small>(ссылка)</small>' : '');
        return '<li data-kind="' + kind + '" data-id="' + item.id + '">' +
            '<button type="button" class="ng-music-bar__btn" data-play-item title="Воспроизвести"><i class="fas fa-play"></i></button>' +
            '<span class="ng-music-list__label"><b>' + escapeHtml(item.title) + '</b>' + artist + badge + '</span>' +
            '<span class="ng-music-list__actions">' +
            '<button type="button" class="ng-music-bar__btn" data-queue-item title="В очередь"><i class="fas fa-plus"></i></button>' +
            '<button type="button" class="ng-music-bar__btn" data-delete-item title="Удалить"><i class="fas fa-trash"></i></button>' +
            '</span></li>';
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderLibrary() {
        var $tracks = $('#ng-music-tracks-list').empty();
        var $streams = $('#ng-music-streams-list').empty();
        var $playlists = $('#ng-music-playlists-list').empty();

        if (!library.tracks.length) {
            $tracks.html('<li class="ng-music-empty">Нет загруженных треков</li>');
        } else {
            library.tracks.forEach(function (t) { $tracks.append(trackRow(t, 'track')); });
        }

        if (!library.streams.length) {
            $streams.html('<li class="ng-music-empty">Нет потоков</li>');
        } else {
            library.streams.forEach(function (s) { $streams.append(trackRow(s, 'stream')); });
        }

        if (!library.playlists.length) {
            $playlists.html('<li class="ng-music-empty">Нет плейлистов</li>');
        } else {
            library.playlists.forEach(function (pl) {
                var itemsHtml = '';
                if (pl.items && pl.items.length) {
                    itemsHtml = '<ul class="ng-music-list ng-music-sublist">';
                    pl.items.forEach(function (it) {
                        itemsHtml += '<li data-playlist-id="' + pl.id + '" data-item-row="' + (it.playlist_item_id || it.id) + '">' +
                            '<button type="button" class="ng-music-bar__btn" data-play-pl-item><i class="fas fa-play"></i></button> ' +
                            escapeHtml(it.title) +
                            '</li>';
                    });
                    itemsHtml += '</ul>';
                }
                $playlists.append(
                    '<li class="ng-music-playlist-block" data-playlist-id="' + pl.id + '">' +
                    '<div style="display:flex;align-items:center;gap:8px">' +
                    '<b>' + escapeHtml(pl.title) + '</b>' +
                    '<button type="button" class="ng-music-bar__btn" data-play-playlist title="Играть всё"><i class="fas fa-play-circle"></i></button>' +
                    '<button type="button" class="ng-music-bar__btn" data-delete-playlist title="Удалить"><i class="fas fa-trash"></i></button>' +
                    '</div>' + itemsHtml + '</li>'
                );
            });
        }
    }

    function loadLibrary() {
        return $.getJSON('/api/audio/library').then(function (res) {
            if (res.error) {
                notifyErr(res.message || 'Ошибка загрузки библиотеки');
                return;
            }
            if (!res.tables_exist) {
                $('#ng-music-migration-hint').show();
            }
            library = res.library || library;
            renderLibrary();
        });
    }

    function itemFromLi($li) {
        var kind = $li.data('kind');
        var id = parseInt($li.data('id'), 10);
        var list = kind === 'stream' ? library.streams : library.tracks;
        for (var i = 0; i < list.length; i++) {
            if (list[i].id === id) return list[i];
        }
        return null;
    }

    $(function () {
        $('.ng-music-tab').on('click', function () {
            var tab = $(this).data('tab');
            $('.ng-music-tab').removeClass('active');
            $(this).addClass('active');
            $('.ng-music-panel').removeClass('active');
            $('#ng-music-panel-' + tab).addClass('active');
        });

        $('#ng-music-upload-form').on('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(this);
            apiPost('/api/audio/upload', fd, true).done(function (res) {
                if (res.error) { notifyErr(res.message); return; }
                notifyOk('Трек загружен');
                this.reset();
                loadLibrary();
            }.bind(this)).fail(function () { notifyErr('Ошибка загрузки'); });
        });

        $('#ng-music-url-form').on('submit', function (e) {
            e.preventDefault();
            apiPost('/api/audio/url', $(this).serialize()).done(function (res) {
                if (res.error) { notifyErr(res.message); return; }
                notifyOk('Ссылка добавлена');
                $('#ng-music-url-form')[0].reset();
                loadLibrary();
            }).fail(function () { notifyErr('Ошибка'); });
        });

        $('#ng-music-stream-form').on('submit', function (e) {
            e.preventDefault();
            apiPost('/api/audio/stream', $(this).serialize()).done(function (res) {
                if (res.error) { notifyErr(res.message); return; }
                notifyOk('Поток добавлен');
                $('#ng-music-stream-form')[0].reset();
                loadLibrary();
            }).fail(function () { notifyErr('Ошибка'); });
        });

        $('#ng-music-m3u-import').on('click', function () {
            var text = $('#ng-music-m3u-text').val();
            var entries = parseM3u(text);
            if (!entries.length) {
                notifyErr('В M3U не найдено http(s) ссылок');
                return;
            }
            var chain = $.Deferred().resolve();
            entries.forEach(function (ent) {
                chain = chain.then(function () {
                    return apiPost('/api/audio/url', { url: ent.url, title: ent.title });
                });
            });
            chain.done(function () {
                notifyOk('Импортировано записей: ' + entries.length);
                $('#ng-music-m3u-text').val('');
                loadLibrary();
            }).fail(function () { notifyErr('Ошибка импорта M3U'); });
        });

        $('#ng-music-m3u-play').on('click', function () {
            var entries = parseM3u($('#ng-music-m3u-text').val());
            if (!entries.length) {
                notifyErr('Нет ссылок для воспроизведения');
                return;
            }
            var queue = entries.map(function (e) {
                return { type: 'url', title: e.title, artist: '', src: e.url };
            });
            window.NgMusicPlayer.playAll(queue, 0);
            notifyOk('Воспроизведение M3U (' + queue.length + ' треков)');
        });

        $('#ng-music-create-playlist').on('click', function () {
            var title = $('#ng-music-new-playlist-title').val() || 'Новый плейлист';
            apiPost('/api/audio/playlist', { action: 'create', title: title }).done(function (res) {
                if (res.error) { notifyErr(res.message); return; }
                notifyOk('Плейлист создан');
                $('#ng-music-new-playlist-title').val('');
                loadLibrary();
            });
        });

        $(document).on('click', '[data-play-item]', function () {
            var item = itemFromLi($(this).closest('li'));
            if (item) window.NgMusicPlayer.play(item);
        });

        $(document).on('click', '[data-queue-item]', function () {
            var item = itemFromLi($(this).closest('li'));
            if (item) window.NgMusicPlayer.addToQueue(item);
        });

        $(document).on('click', '[data-delete-item]', function () {
            var $li = $(this).closest('li');
            var kind = $li.data('kind');
            var id = $li.data('id');
            if (!confirm('Удалить?')) return;
            apiPost('/api/audio/delete', { kind: kind, id: id }).done(function (res) {
                if (res.error) { notifyErr(res.message); return; }
                loadLibrary();
            });
        });

        $(document).on('click', '[data-play-playlist]', function () {
            var plId = parseInt($(this).closest('[data-playlist-id]').data('playlist-id'), 10);
            var pl = null;
            library.playlists.forEach(function (p) { if (p.id === plId) pl = p; });
            if (pl && pl.items && pl.items.length) {
                window.NgMusicPlayer.playAll(pl.items, 0);
            }
        });

        $(document).on('click', '[data-delete-playlist]', function () {
            var plId = parseInt($(this).closest('[data-playlist-id]').data('playlist-id'), 10);
            if (!confirm('Удалить плейлист?')) return;
            apiPost('/api/audio/delete', { kind: 'playlist', id: plId }).done(function (res) {
                if (res.error) { notifyErr(res.message); return; }
                loadLibrary();
            });
        });

        $(document).on('click', '[data-play-pl-item]', function () {
            var plId = parseInt($(this).closest('li').data('playlist-id'), 10);
            var pl = null;
            library.playlists.forEach(function (p) { if (p.id === plId) pl = p; });
            if (!pl) return;
            var rowId = parseInt($(this).closest('li').data('item-row'), 10);
            var idx = 0;
            for (var i = 0; i < pl.items.length; i++) {
                var it = pl.items[i];
                if ((it.playlist_item_id || it.id) === rowId) {
                    window.NgMusicPlayer.playAll(pl.items, i);
                    return;
                }
            }
        });

        loadLibrary();
    });
})(jQuery, window);