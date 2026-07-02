<?php

use App\Services\OpenVKAuth;

$returnUrl = (string) ($_SESSION['ovk_return'] ?? '/');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'); ?>
</head>
<body>
    <div id="backgr"></div>
    <table class="tmain">
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'); ?>
        <tr>
            <td class="main">
                <center style="padding:40px 0">
                    <h1>Завершение входа через OpenVK</h1>
                    <p id="ovk-status" class="sm">Получаем токен…</p>
                </center>
            </td>
        </tr>
        <tr>
            <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
        </tr>
    </table>
    <script>
        (function () {
            var hash = new URLSearchParams(window.location.hash.replace(/^#/, ''));
            var token = hash.get('access_token') || '';
            var userId = hash.get('user_id') || '';
            var status = document.getElementById('ovk-status');

            if (!token) {
                status.textContent = 'Токен не найден в адресе. Попробуйте войти снова.';
                status.style.color = '#c00';
                return;
            }

            fetch('/api/auth/openvk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    access_token: token,
                    user_id: userId ? parseInt(userId, 10) : null
                })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (parseInt(data.errorcode, 10) === 0) {
                        window.location.href = data.redirect || <?= json_encode($returnUrl) ?>;
                        return;
                    }
                    status.textContent = data.message || 'Не удалось выполнить вход.';
                    status.style.color = '#c00';
                })
                .catch(function () {
                    status.textContent = 'Ошибка сети при отправке токена.';
                    status.style.color = '#c00';
                });
        })();
    </script>
</body>
</html>