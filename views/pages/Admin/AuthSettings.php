<?php

use App\Services\{AdminAccess, GalleryConfig};

if (!AdminAccess::isFullAdmin()) {
    echo '<div class="alert alert-danger">Раздел доступен только администраторам.</div>';
    return;
}

$registrationPublic = !empty(NGALLERY['root']['registration']['access']['public']);
$openvkEnabled = !empty(NGALLERY['root']['openvk']['enabled']);
$openvkAutoRegister = !empty(NGALLERY['root']['openvk']['auto_register']);
$providers = GalleryConfig::listProvidersForAdmin();
?>
<h1><b>Авторизация</b></h1>
<p class="text-muted">Регистрация и вход через OpenVK. Все изменения инстансов (включая из <code>ngallery.yaml</code>) сохраняются в <code>storage/auth-settings.json</code> и перекрывают базовый конфиг.</p>

<div id="auth-settings-alert"></div>

<form id="auth-settings-form" class="p20" style="max-width:760px">
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

        <div class="d-flex justify-content-between align-items-center mb-2">
            <b>Инстансы OpenVK</b>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#providerModal" onclick="openProviderModal()">Добавить инстанс</button>
        </div>

        <?php if ($providers === []) { ?>
            <div class="alert alert-warning mb-0">Нет ни одного инстанса. Добавьте свой узел OpenVK или настройте провайдеры в <code>ngallery.yaml</code>.</div>
        <?php } else { ?>
            <table class="table table-sm align-middle mb-2">
                <thead>
                    <tr>
                        <th>Вкл.</th>
                        <th>Название</th>
                        <th>Домен</th>
                        <th>Источник</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($providers as $row) {
                        $fieldId = 'provider_' . preg_replace('/[^a-z0-9_]/i', '_', $row['id']);
                        $sourceLabel = $row['source'] === 'custom' ? 'Админка' : 'yaml';
                        ?>
                        <tr>
                            <td>
                                <input class="form-check-input provider-toggle" type="checkbox" role="switch"
                                       id="<?= htmlspecialchars($fieldId) ?>"
                                       data-provider-id="<?= htmlspecialchars($row['id']) ?>"
                                       <?= $row['enabled'] ? 'checked' : '' ?>>
                            </td>
                            <td><?= htmlspecialchars($row['label']) ?></td>
                            <td><code><?= htmlspecialchars($row['domain']) ?></code></td>
                            <td><span class="badge text-bg-<?= $row['source'] === 'custom' ? 'primary' : 'secondary' ?>"><?= $sourceLabel ?></span></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick='editProvider(<?= json_encode($row, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Изменить</button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteProviderModal('<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['label'], ENT_QUOTES) ?>')">Удалить</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="form-text">Инстансы из yaml можно менять и удалять здесь — правки пишутся в overlay, исходный <code>ngallery.yaml</code> не трогается. При удалении можно перенести привязки пользователей на другой инстанс.</div>
        <?php } ?>
    </fieldset>

    <button type="submit" class="btn btn-primary" id="auth-settings-submit">Сохранить переключатели</button>
</form>

<div class="modal fade" id="providerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="providerModalTitle"><b>Добавить инстанс OpenVK</b></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="provider-modal-alert"></div>
                <input type="hidden" id="provider_edit_id" value="">
                <div class="mb-2" id="provider_id_wrap">
                    <label class="form-label" for="provider_id">ID (латиница, необязательно)</label>
                    <input class="form-control" type="text" id="provider_id" placeholder="my_ovk">
                    <div class="form-text">Если пусто — сгенерируется из домена.</div>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="provider_label">Название кнопки</label>
                    <input class="form-control" type="text" id="provider_label" required placeholder="Мой OpenVK">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="provider_domain">Домен инстанса</label>
                    <input class="form-control" type="url" id="provider_domain" required placeholder="https://vepurovk.xyz">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="provider_api_domain">API-домен</label>
                    <input class="form-control" type="url" id="provider_api_domain" placeholder="https://api.openvk.org">
                    <div class="form-text">Для openvk.org — <code>https://api.openvk.org</code>. Для остальных обычно совпадает с доменом.</div>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="provider_accent">Цвет кнопки</label>
                    <input class="form-control" type="text" id="provider_accent" value="#5181b8" placeholder="#5181b8">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="provider_icon">Иконка (URL)</label>
                    <input class="form-control" type="url" id="provider_icon" placeholder="https://…/favicon.ico">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="provider_enabled" checked>
                    <label class="form-check-label" for="provider_enabled">Включён</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="provider-save-btn" onclick="saveProvider()">Сохранить инстанс</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProviderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><b>Удалить инстанс</b></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Удалить инстанс <b id="delete_provider_label"></b> (<code id="delete_provider_id"></code>) из активной конфигурации?</p>
                <div class="mb-2">
                    <label class="form-label" for="delete_replace_with">Перенести привязки пользователей на</label>
                    <select class="form-select" id="delete_replace_with">
                        <option value="">— не переносить —</option>
                    </select>
                    <div class="form-text">Если пользователи входили через этот инстанс, их OpenVK-привязки можно переключить на другой узел.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="delete-provider-confirm" onclick="confirmDeleteProvider()">Удалить</button>
            </div>
        </div>
    </div>
</div>

<script>
const AUTH_PROVIDERS = <?= json_encode(array_values($providers), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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

function openProviderModal() {
    document.getElementById('providerModalTitle').innerHTML = '<b>Добавить инстанс OpenVK</b>';
    document.getElementById('provider_edit_id').value = '';
    document.getElementById('provider_id_wrap').style.display = '';
    document.getElementById('provider_id').value = '';
    document.getElementById('provider_label').value = '';
    document.getElementById('provider_domain').value = '';
    document.getElementById('provider_api_domain').value = '';
    document.getElementById('provider_accent').value = '#5181b8';
    document.getElementById('provider_icon').value = '';
    document.getElementById('provider_enabled').checked = true;
    document.getElementById('provider-modal-alert').innerHTML = '';
}

function editProvider(row) {
    document.getElementById('providerModalTitle').innerHTML = '<b>Изменить инстанс</b>';
    document.getElementById('provider_edit_id').value = row.id;
    document.getElementById('provider_id_wrap').style.display = 'none';
    document.getElementById('provider_label').value = row.label || '';
    document.getElementById('provider_domain').value = row.domain || '';
    document.getElementById('provider_api_domain').value = row.api_domain || '';
    document.getElementById('provider_accent').value = row.accent || '#5181b8';
    document.getElementById('provider_icon').value = row.icon || '';
    document.getElementById('provider_enabled').checked = !!row.enabled;
    document.getElementById('provider-modal-alert').innerHTML = '';
    const modal = new bootstrap.Modal(document.getElementById('providerModal'));
    modal.show();
}

function saveProvider() {
    const editId = document.getElementById('provider_edit_id').value;
    const data = new FormData();
    data.append('label', document.getElementById('provider_label').value.trim());
    data.append('domain', document.getElementById('provider_domain').value.trim());
    data.append('api_domain', document.getElementById('provider_api_domain').value.trim());
    data.append('accent', document.getElementById('provider_accent').value.trim());
    data.append('icon', document.getElementById('provider_icon').value.trim());
    data.append('enabled', document.getElementById('provider_enabled').checked ? '1' : '0');

    let url = '/api/admin/settings/auth/providers';
    if (!editId) {
        data.append('provider_id', document.getElementById('provider_id').value.trim());
    } else {
        url = '/api/admin/settings/auth/providers/' + encodeURIComponent(editId);
    }

    const btn = document.getElementById('provider-save-btn');
    btn.disabled = true;

    fetch(url, { method: 'POST', body: data, credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
            if (payload.errorcode === 0) {
                window.location.reload();
                return;
            }
            document.getElementById('provider-modal-alert').innerHTML =
                '<div class="alert alert-danger">' + (payload.message || 'Ошибка') + '</div>';
        })
        .catch(function () {
            document.getElementById('provider-modal-alert').innerHTML =
                '<div class="alert alert-danger">Ошибка сети</div>';
        })
        .finally(function () {
            btn.disabled = false;
        });
}

let deleteProviderId = '';

function openDeleteProviderModal(id, label) {
    deleteProviderId = id;
    document.getElementById('delete_provider_id').textContent = id;
    document.getElementById('delete_provider_label').textContent = label || id;

    const select = document.getElementById('delete_replace_with');
    select.innerHTML = '<option value="">— не переносить —</option>';
    AUTH_PROVIDERS.forEach(function (row) {
        if (row.id === id) {
            return;
        }
        const option = document.createElement('option');
        option.value = row.id;
        option.textContent = row.label + ' (' + row.id + ')';
        select.appendChild(option);
    });

    const modal = new bootstrap.Modal(document.getElementById('deleteProviderModal'));
    modal.show();
}

function confirmDeleteProvider() {
    if (!deleteProviderId) {
        return;
    }

    const data = new FormData();
    const replaceWith = document.getElementById('delete_replace_with').value;
    if (replaceWith) {
        data.append('replace_with', replaceWith);
    }

    const btn = document.getElementById('delete-provider-confirm');
    btn.disabled = true;

    fetch('/api/admin/settings/auth/providers/' + encodeURIComponent(deleteProviderId) + '/delete', {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
    })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
            if (payload.errorcode === 0) {
                window.location.reload();
                return;
            }
            document.getElementById('auth-settings-alert').innerHTML =
                '<div class="alert alert-danger">' + (payload.message || 'Ошибка') + '</div>';
            bootstrap.Modal.getInstance(document.getElementById('deleteProviderModal')).hide();
        })
        .catch(function () {
            document.getElementById('auth-settings-alert').innerHTML =
                '<div class="alert alert-danger">Ошибка сети</div>';
        })
        .finally(function () {
            btn.disabled = false;
        });
}
</script>