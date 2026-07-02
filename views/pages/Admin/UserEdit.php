<?php

use App\Models\User;
use App\Services\{AdminAccess, Auth, Date, DB};

if (!AdminAccess::isFullAdmin()) {
    echo '<div class="alert alert-danger">Раздел доступен только администраторам.</div>';
    return;
}

$userId = (int) ($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    echo '<div class="alert alert-danger">Пользователь не найден.</div>';
    return;
}

if (DB::query('SELECT id FROM users WHERE id = :id', [':id' => $userId]) === []) {
    echo '<div class="alert alert-danger">Пользователь не найден.</div>';
    return;
}

$user = new User($userId);

[$roleLabel] = AdminAccess::roleLabel((int) $user->i('admin'));
$premoderation = $user->content('premoderation');
if ($premoderation !== 'true' && $premoderation !== 'false') {
    $premoderation = 'false';
}
?>
<p><a href="/admin">&larr; К списку пользователей</a></p>

<h1><b>Редактирование пользователя</b></h1>

<div id="user-edit-alert"></div>

<form id="user-edit-form" style="display:inline-block; min-width:520px; max-width:640px;">
    <p><img src="<?= htmlspecialchars((string) $user->i('photourl')) ?>" width="50" alt=""> <b><?= htmlspecialchars((string) $user->i('username')) ?></b></p>
    <p>Текущая роль: <span class="badge text-bg-secondary"><?= htmlspecialchars($roleLabel) ?></span></p>
    <p>Был в сети: <b><?= Date::zmdate($user->i('online')) ?><?php if (time() - 300 <= (int) $user->i('online')) { ?> <i>(online)</i><?php } ?></b></p>
    <p>Профиль: <a href="/author/<?= $userId ?>">/author/<?= $userId ?></a></p>

    <div class="p20" style="text-align:left; margin-bottom:15px">
        <h4 class="mt-3"><b>Права и ограничения</b></h4>

        <div style="margin-bottom:3px; margin-top:5px">Прямая загрузка (без модерации)</div>
        <select class="form-select" name="premoderation" style="width:100%">
            <option value="true" <?= $premoderation === 'true' ? 'selected' : '' ?>>Да</option>
            <option value="false" <?= $premoderation === 'false' ? 'selected' : '' ?>>Нет</option>
        </select>

        <div style="margin-bottom:3px; margin-top:12px">Статус аккаунта</div>
        <select class="form-select" name="status" style="width:100%">
            <option value="0" <?= (int) $user->i('status') === 0 ? 'selected' : '' ?>>Без ограничений</option>
            <option value="1" <?= (int) $user->i('status') === 1 ? 'selected' : '' ?>>Заблокирован</option>
        </select>

        <div style="margin-bottom:3px; margin-top:12px">Уровень доступа</div>
        <select class="form-select" name="admin" style="width:100%" <?= $userId === Auth::userid() ? 'disabled' : '' ?>>
            <option value="0" <?= (int) $user->i('admin') === 0 ? 'selected' : '' ?>>Пользователь</option>
            <option value="1" <?= (int) $user->i('admin') === 1 ? 'selected' : '' ?>>Администратор</option>
            <option value="2" <?= (int) $user->i('admin') === 2 ? 'selected' : '' ?>>Фотомодератор</option>
            <option value="3" <?= (int) $user->i('admin') === 3 ? 'selected' : '' ?>>Модератор</option>
        </select>
        <?php if ($userId === Auth::userid()) { ?>
            <div class="form-text">Свою роль администратора изменить нельзя.</div>
            <input type="hidden" name="admin" value="<?= (int) $user->i('admin') ?>">
        <?php } ?>
    </div>

    <div class="cmt-submit">
        <button type="submit" class="btn btn-primary" id="user-edit-submit">Сохранить</button>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById('user-edit-form');
    const alertBox = document.getElementById('user-edit-alert');
    const submitBtn = document.getElementById('user-edit-submit');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitBtn.disabled = true;

        const data = new FormData(form);
        fetch('/api/admin/users/<?= $userId ?>/edit', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload.errorcode === 0) {
                    alertBox.innerHTML = '<div class="alert alert-success">Данные успешно обновлены</div>';
                    if (typeof Notify !== 'undefined') {
                        Notify.noty('success', 'Сохранено');
                    }
                    setTimeout(function () { window.location.reload(); }, 600);
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