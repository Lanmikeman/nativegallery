<?php

use App\Services\{AdminAccess, OpenVKAuth};

if (!AdminAccess::isFullAdmin()) {
    echo '<div class="alert alert-danger">Раздел доступен только администраторам.</div>';
    return;
}

$registrationPublic = !empty(NGALLERY['root']['registration']['access']['public']);
$openvkEnabled = !empty(NGALLERY['root']['openvk']['enabled']);
$openvkAutoRegister = !empty(NGALLERY['root']['openvk']['auto_register']);
$providers = NGALLERY['root']['openvk']['providers'] ?? [];
if (!is_array($providers)) {
    $providers = [];
}
?>
<h1><b>Авторизация</b></h1>
<p class="text-muted">Включение и отключение регистрации и входа через внешние сервисы. Изменения записываются в <code>ngallery.yaml</code>.</p>

<div id="auth-settings-alert"></div>

<form id="auth-settings-form" class="p20" style="max-width:640px">
    <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2 fs-6"><b>Регистрация</b></legend>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="registration_public" name="registration_public" value="1" <?= $registrationPublic ? 'checked' : '' ?>>
            <label class="form-check-label" for="registration_public">Открытая регистрация на сайте</label>
        </div>
        <div class="form-text">При отключении скрывается форма на <code>/register</code> и API регистрации.</div>
    </fieldset>

    <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2 fs-6"><b>OpenVK</b></legend>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="openvk_enabled" name="openvk_enabled" value="1" <?= $openvkEnabled ? 'checked' : '' ?>>
            <label class="form-check-label" for="openvk_enabled">Вход и привязка через OpenVK</label>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="openvk_auto_register" name="openvk_auto_register" value="1" <?= $openvkAutoRegister ? 'checked' : '' ?>>
            <label class="form-check-label" for="openvk_auto_register">Автоматически создавать локальный аккаунт при первом входе</label>
        </div>

        <?php if ($providers === []) { ?>
            <div class="alert alert-warning mb-0">Провайдеры OpenVK не настроены в <code>ngallery.yaml</code>.</div>
        <?php } else { ?>
            <div class="mb-2"><b>Инстансы OpenVK</b></div>
            <?php foreach ($providers as $providerId => $provider) {
                if (!is_array($provider)) {
                    continue;
                }
                $label = (string) ($provider['label'] ?? $providerId);
                $domain = (string) ($provider['domain'] ?? '');
                $providerEnabled = !array_key_exists('enabled', $provider) || !empty($provider['enabled']);
                $fieldId = 'provider_' . preg_replace('/[^a-z0-9_]/i', '_', (string) $providerId);
                ?>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input provider-toggle" type="checkbox" role="switch"
                           id="<?= htmlspecialchars($fieldId) ?>"
                           name="providers[<?= htmlspecialchars((string) $providerId) ?>]"
                           value="1"
                           data-provider-id="<?= htmlspecialchars((string) $providerId) ?>"
                           <?= $providerEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= htmlspecialchars($fieldId) ?>">
                        <?= htmlspecialchars($label) ?>
                        <?php if ($domain !== '') { ?>
                            <span class="text-muted">(<?= htmlspecialchars($domain) ?>)</span>
                        <?php } ?>
                    </label>
                </div>
            <?php } ?>
            <div class="form-text">Можно отключить отдельный инстанс, например <b>vepurovk.xyz</b>, не выключая OpenVK целиком.</div>
        <?php } ?>
    </fieldset>

    <button type="submit" class="btn btn-primary" id="auth-settings-submit">Сохранить настройки</button>
</form>

<script>
(function () {
    const form = document.getElementById('auth-settings-form');
    const alertBox = document.getElementById('auth-settings-alert');
    const submitBtn = document.getElementById('auth-settings-submit');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitBtn.disabled = true;

        const data = new FormData();
        data.append('registration_public', document.getElementById('registration_public').checked ? '1' : '0');
        data.append('openvk_enabled', document.getElementById('openvk_enabled').checked ? '1' : '0');
        data.append('openvk_auto_register', document.getElementById('openvk_auto_register').checked ? '1' : '0');

        document.querySelectorAll('.provider-toggle').forEach(function (input) {
            data.append('providers[' + input.dataset.providerId + ']', input.checked ? '1' : '0');
        });

        fetch('/api/admin/settings/auth', {
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