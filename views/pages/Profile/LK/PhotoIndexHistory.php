<?php

use App\Services\{Auth, DB, Date};

$lkActive = 'info';

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'; ?>
</head>

<body>
    <div id="backgr"></div>
    <table class="tmain">
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'; ?>
        <tr>
            <td class="main">
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LkShellOpen.php'; ?>
                <h1>История изменения индекса загрузки</h1>
                <p class="sm"><a href="/lk/" class="und">← Общая информация</a></p>

                <?php
                $indexhistory = DB::query(
                    'SELECT * FROM uploadindex_history WHERE user_id = :uid ORDER BY id DESC',
                    [':uid' => Auth::userid()]
                );
                ?>

                <?php if ($indexhistory === []) { ?>
                    <div class="p20" style="padding:10px 12px">Записей пока нет.</div>
                <?php } else { ?>
                    <div class="p20w" style="display:block">
                        <table>
                            <tr>
                                <th>Дата и время</th>
                                <th class="r">Индекс</th>
                                <th class="r">Изменение</th>
                                <th>Действие</th>
                                <th>Фотография</th>
                            </tr>
                            <?php foreach ($indexhistory as $ih) {
                                $type = (int) ($ih['type'] ?? 0);
                                $class = $type === 1 ? 's12' : 's15';
                                $typeLabel = $type === 1 ? 'Публикация' : 'Отклонение';
                                $oldIndex = (int) ($ih['oldindex'] ?? 0);
                                $newIndex = (int) ($ih['newindex'] ?? 0);
                                $delta = $newIndex - $oldIndex;
                                $deltaLabel = ($delta > 0 ? '+' : '') . $delta;
                                $photoId = (int) ($ih['photo_id'] ?? 0);
                                ?>
                                <tr class="<?= $class ?>">
                                    <td class="ds"><?= Date::formatDate((int) ($ih['date'] ?? 0)) ?></td>
                                    <td class="r"><b><?= $newIndex ?></b></td>
                                    <td class="r"><?= $deltaLabel ?></td>
                                    <td class="ds"><?= $typeLabel ?></td>
                                    <td class="d">
                                        <?php if ($photoId > 0) { ?>
                                            <a href="/photo/<?= $photoId ?>/" target="_blank">/photo/<?= $photoId ?>/</a>
                                        <?php } else { ?>
                                            —
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                <?php } ?>

                <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/LkShellClose.php'; ?>
            </td>
        </tr>
        <tr>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'; ?>
        </tr>
    </table>
</body>

</html>