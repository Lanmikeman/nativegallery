<?php

use App\Services\{AdminAccess, AudioLibrary};

if (!AdminAccess::isFullAdmin()) {
    echo '<div class="alert alert-danger">Раздел доступен только администраторам.</div>';
    return;
}

$audioEnabled = AudioLibrary::isEnabled();
?>
<h1><b>Музыка</b></h1>
<p class="text-muted">Включение и отключение раздела <code>/music</code>, мини-плеера в шапке и пользовательских API аудио. Изменения сохраняются в <code>storage/auth-settings.json</code> и перекрывают <code>ngallery.yaml</code>.</p>

<div id="music-settings-alert"></div>

<form id="music-settings-form" class="p20" style="max-width:760px">
    <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2 fs-6"><b>Раздел на сайте</b></legend>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="audio_enabled" name="audio_enabled" value="1" <?= $audioEnabled ? 'checked' : '' ?>>
            <label class="form-check-label" for="audio_enabled">Музыка доступна пользователям</label>
        </div>
        <div class="form-text">При отключении скрываются пункт меню «Музыка», мини-плеер и страница <code>/music</code>. Управление радиостанциями в админке остаётся доступным.</div>
    </fieldset>

    <button type="submit" class="btn btn-primary" id="music-settings-submit">Сохранить</button>
</form>

<script>
(function () {
    const form = document.getElementById('music-settings-form');
    const alertBox = document.getElementById('music-settings-alert');
    const submitBtn = document.getElementById('music-settings-submit');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitBtn.disabled = true;

        const data = new FormData();
        data.append('audio_enabled', document.getElementById('audio_enabled').checked ? '1' : '0');

        fetch('/api/admin/settings/music', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload.errorcode === 0) {
                    alertBox.innerHTML = '<div class="alert alert-success">' + (payload.message || 'Сохранено') + '</div>';
                    if (typeof Notify !== 'undefined') {
                        Notify.noty('success', payload.message || 'Сохранено');
                    }
                    return;
                }
                const message = payload.message || 'Не удалось сохранить';
                alertBox.innerHTML = '<div class="alert alert-danger">' + message + '</div>';
                if (typeof Notify !== 'undefined') {
                    Notify.noty('error', message);
                }
            })
            .catch(function () {
                alertBox.innerHTML = '<div class="alert alert-danger">Ошибка сети</div>';
            })
            .finally(function () {
                submitBtn.disabled = false;
            });
    });
})();
</script>