<?php

use App\Services\{Auth, OpenVKAuth};
use App\Models\User;

$user = new User(Auth::userid());
$links = OpenVKAuth::linksForUser(Auth::userid());
$providers = OpenVKAuth::providers();
$linkedNotice = !empty($_GET['linked']);
?>
<div class="p20" style="text-align:left; max-width:560px; margin:0 auto">
    <h4>OpenVK</h4>
    <p class="sm">Привяжите профили OpenVK к аккаунту <b><?= htmlspecialchars($user->i('username')) ?></b>, чтобы входить через них без пароля.</p>

    <?php if ($linkedNotice) { ?>
        <p style="color:green; font-weight:bold">✔ Профиль OpenVK успешно привязан.</p>
    <?php } ?>

    <?php foreach ($providers as $provider) {
        $id = (string) $provider['id'];
        $link = $links[$id] ?? null;
        $accent = htmlspecialchars((string) $provider['accent']);
        $icon = htmlspecialchars((string) $provider['icon']);
        $label = htmlspecialchars((string) $provider['label']);
        ?>
        <div class="ovk-link-card" style="border:1px solid #ddd; border-left:4px solid <?= $accent ?>; border-radius:8px; padding:14px; margin:12px 0; display:flex; gap:12px; align-items:center">
            <img src="<?= $icon ?>" alt="" width="36" height="36" style="border-radius:6px; object-fit:cover"
                 onerror="this.onerror=null;this.src='/static/img/avatar.png';">
            <div style="flex:1">
                <div style="font-weight:600"><?= $label ?></div>
                <?php if (is_array($link)) {
                    $profileUrl = OpenVKAuth::profileUrl($link);
                    $profileName = OpenVKAuth::profileDisplayName($link);
                    ?>
                    <div class="sm">
                        Привязан: <b><?= htmlspecialchars($profileName) ?></b>
                        (ID <?= (int) ($link['id'] ?? 0) ?>)
                    </div>
                    <div class="sm" style="margin-top:4px">
                        <a href="<?= htmlspecialchars($profileUrl) ?>" target="_blank" rel="noopener noreferrer" style="color:<?= $accent ?>">
                            <?= htmlspecialchars($profileUrl) ?>
                        </a>
                    </div>
                <?php } else { ?>
                    <div class="sm" style="color:#888">Не привязан</div>
                <?php } ?>
            </div>
            <div>
                <?php if (is_array($link)) { ?>
                    <button type="button" class="mf-button ovk-unlink-btn" data-provider="<?= htmlspecialchars($id) ?>" style="background:#dc3545; border-color:#dc3545">Отвязать</button>
                <?php } else { ?>
                    <a href="/auth/openvk/start?provider=<?= rawurlencode($id) ?>&mode=link&return=<?= rawurlencode('/lk/profile?type=OpenVK') ?>"
                       class="mf-button" style="border-color:<?= $accent ?>; color:<?= $accent ?>">Привязать</a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <p id="ovk-link-status" class="sm" style="margin-top:12px"></p>
</div>
<script>
    $(document).on('click', '.ovk-unlink-btn', function () {
        var provider = $(this).data('provider');
        if (!confirm('Отвязать этот профиль OpenVK?')) return;

        $.post('/api/auth/openvk?action=unlink', { provider: provider }, function (r) {
            r = typeof r === 'string' ? JSON.parse(r) : r;
            if (parseInt(r.errorcode, 10) === 0) {
                window.location.reload();
            } else {
                $('#ovk-link-status').css('color', '#c00').text(r.message || 'Не удалось отвязать.');
            }
        });
    });
</script>