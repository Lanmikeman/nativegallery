<?php

use App\Services\AdminAccess;

if (!AdminAccess::isOwner()) {
    echo '<div class="alert alert-danger">Раздел доступен только владельцу сервера.</div>';
    return;
}

require $_SERVER['DOCUMENT_ROOT'] . '/views/components/AdminConfigInputs.php';

$debugEnabled = !empty(NGALLERY['root']['debug']);
$hasOverlay = is_readable($_SERVER['DOCUMENT_ROOT'] . '/storage/server-settings.json');
?>
<h1><b>Сервер</b></h1>
<p class="text-muted">
    Параметры из <code>storage/server-settings.json</code> перекрывают <code>ngallery.yaml</code>.
    Базовый YAML на диске не изменяется.
</p>

<div id="server-settings-alert"></div>

<div class="p20" style="max-width:760px; margin-bottom:24px">
    <h4>Debug (Tracy)</h4>
    <p class="text-muted">Включайте только для диагностики. На production обычно выключено.</p>
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="debug-toggle" <?= $debugEnabled ? 'checked' : '' ?>>
        <label class="form-check-label" for="debug-toggle">
            <?= $debugEnabled ? 'Включён' : 'Выключен' ?>
        </label>
    </div>
    <?php if ($hasOverlay) { ?>
        <p class="form-text mt-2">Активен overlay: <code>storage/server-settings.json</code></p>
    <?php } ?>
</div>

<div class="v-header__tabs">
    <div class="v-tabs">
        <div class="v-tabs__scroll">
            <div class="v-tabs__content">
                <a href="#" onclick="changeTab('server-config')" id="server-config" class="v-tab v-tab-b v-tab--active">
                    <span class="v-tab__label">Конфиг сервера</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div id="server-config__block" class="active__block">
    <div class="alert alert-warning" role="alert">
        Изменяйте настройки только если понимаете последствия. Ошибки в БД или storage могут вывести сайт из строя.
    </div>
    <div class="p20w" style="display:block">
        <form id="server-config-form">
            <fieldset class="mb-3 p-2 border"><legend>root</legend>
                <?php renderConfigInputs(NGALLERY['root'], 'root'); ?>
            </fieldset>
            <button type="submit" class="btn btn-primary" id="server-config-submit">Сохранить конфиг</button>
        </form>
    </div>
</div>

<script>
(function () {
    const alertBox = document.getElementById('server-settings-alert');
    const debugToggle = document.getElementById('debug-toggle');

    debugToggle.addEventListener('change', function () {
        const enabled = debugToggle.checked;
        const data = new FormData();
        data.append('debug', enabled ? '1' : '0');

        fetch('/api/admin/settings/debug', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload.errorcode === 0) {
                    alertBox.innerHTML = '<div class="alert alert-success">' + (payload.message || 'Сохранено') + '</div>';
                    debugToggle.nextElementSibling.textContent = enabled ? 'Включён' : 'Выключен';
                    if (typeof Notify !== 'undefined') {
                        Notify.noty('success', payload.message || 'Сохранено');
                    }
                    setTimeout(function () { window.location.reload(); }, 700);
                    return;
                }
                debugToggle.checked = !enabled;
                const message = payload.message || 'Ошибка';
                alertBox.innerHTML = '<div class="alert alert-danger">' + message + '</div>';
                if (typeof Notify !== 'undefined') {
                    Notify.noty('error', message);
                }
            })
            .catch(function () {
                debugToggle.checked = !enabled;
                alertBox.innerHTML = '<div class="alert alert-danger">Ошибка сети</div>';
            });
    });

    document.getElementById('server-config-form').addEventListener('submit', function (event) {
        event.preventDefault();
        const submitBtn = document.getElementById('server-config-submit');
        submitBtn.disabled = true;

        fetch('/api/admin/settings/server', {
            method: 'POST',
            body: new FormData(this),
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload.errorcode === 0) {
                    alertBox.innerHTML = '<div class="alert alert-success">' + (payload.message || 'Сохранено') + '</div>';
                    if (typeof Notify !== 'undefined') {
                        Notify.noty('success', payload.message || 'Сохранено');
                    }
                    setTimeout(function () { window.location.reload(); }, 700);
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