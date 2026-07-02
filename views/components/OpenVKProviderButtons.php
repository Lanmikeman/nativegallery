<?php

use App\Services\OpenVKAuth;

/** @var string $ovkMode login|link */
/** @var string $ovkReturn */
$ovkMode = $ovkMode ?? 'login';
$ovkReturn = $ovkReturn ?? '/';

if (!OpenVKAuth::isEnabled()) {
    return;
}

$providers = OpenVKAuth::providers();
if ($providers === []) {
    return;
}
?>
<div class="ovk-providers" style="margin-top:18px">
    <div class="ovk-providers-title sm" style="margin-bottom:10px; color:#666">
        <?= $ovkMode === 'link' ? 'Привязать профиль OpenVK' : 'Войти через OpenVK' ?>
    </div>
    <div class="ovk-providers-list" style="display:flex; flex-direction:column; gap:10px; max-width:320px; margin:0 auto">
        <?php foreach ($providers as $provider) {
            $accent = htmlspecialchars((string) $provider['accent']);
            $icon = htmlspecialchars((string) $provider['icon']);
            $label = htmlspecialchars((string) $provider['label']);
            $href = '/auth/openvk/start?provider=' . rawurlencode((string) $provider['id'])
                . '&mode=' . rawurlencode($ovkMode)
                . '&return=' . rawurlencode($ovkReturn);
            ?>
            <a href="<?= $href ?>" class="ovk-provider-btn"
               style="--ovk-accent: <?= $accent ?>; display:flex; align-items:center; gap:12px; padding:11px 14px; border:2px solid <?= $accent ?>; border-radius:8px; text-decoration:none; color:#222; background:linear-gradient(90deg, <?= $accent ?>18 0%, #fff 65%); transition:box-shadow .15s ease, transform .15s ease;">
                <img src="<?= $icon ?>" alt="" width="28" height="28" style="border-radius:6px; flex:0 0 28px; object-fit:cover; background:#fff"
                     onerror="this.onerror=null;this.src='/static/img/avatar.png';">
                <span style="font-weight:600; line-height:1.2"><?= $label ?></span>
            </a>
        <?php } ?>
    </div>
</div>
<style>
    .ovk-provider-btn:hover {
        box-shadow: 0 4px 14px color-mix(in srgb, var(--ovk-accent) 35%, transparent);
        transform: translateY(-1px);
    }
</style>