<?php

use App\Services\{AdminAccess, DB};
use App\Models\User;

if (!AdminAccess::isFullAdmin()) {
    echo '<div class="alert alert-danger">Раздел доступен только администраторам.</div>';
    return;
}
?>
<h1><b>Пользователи</b></h1>
<p><a href="/admin?type=AuthSettings" class="btn btn-sm btn-outline-primary">Настройки авторизации</a></p>

<table class="table" style="margin-top: 15px;">
  <thead>
    <tr>
      <th scope="col">ID</th>
      <th scope="col"></th>
      <th scope="col">Никнейм</th>
      <th scope="col">Почта</th>
      <th scope="col">Роль</th>
      <th scope="col">Прямая загрузка</th>
      <th scope="col">Статус</th>
      <th scope="col">Профиль</th>
      <th scope="col"></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $users = DB::query('SELECT * FROM users ORDER BY id ASC');
    foreach ($users as $u) {
        $user = new User($u['id']);
        $prem = $user->content('premoderation') === 'true' ? 'Да' : 'Нет';
        [$roleLabel, $roleClass] = AdminAccess::roleLabel((int) $u['admin']);
        $statusLabel = (int) $u['status'] === 1 ? 'Заблокирован' : 'Активен';
        $statusClass = (int) $u['status'] === 1 ? 'danger' : 'success';
        echo '<tr>
      <th>' . (int) $u['id'] . '</th>
      <td><img src="' . htmlspecialchars((string) $u['photourl']) . '" width="35" alt=""></td>
      <td>' . htmlspecialchars((string) $u['username']) . '</td>
      <td>' . htmlspecialchars((string) $u['email']) . '</td>
      <td><span class="badge text-bg-' . $roleClass . '">' . htmlspecialchars($roleLabel) . '</span></td>
      <td>' . $prem . '</td>
      <td><span class="badge text-bg-' . $statusClass . '">' . $statusLabel . '</span></td>
      <td><a href="/author/' . (int) $u['id'] . '">/author/' . (int) $u['id'] . '</a></td>
      <td><a class="btn btn-sm btn-primary" href="/admin?type=UserEdit&user_id=' . (int) $u['id'] . '">Редактировать</a></td>
    </tr>';
    }
    ?>
  </tbody>
</table>