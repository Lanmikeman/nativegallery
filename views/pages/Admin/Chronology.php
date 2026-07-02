<?php

use App\Services\{Auth, DB, Date, ChronologyQuery};
use App\Models\User;
?>

<h1><b>Хронология</b></h1>
<a data-bs-toggle="modal" data-bs-target="#createChronologyModal" href="#" class="btn btn-primary">Добавить запись</a>

<div class="modal fade" id="createChronologyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"><b>Добавить запись в хронологию</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Город (текст)</label>
                    <input type="text" class="form-control" id="chrono-city" placeholder="Москва">
                </div>
                <div class="mb-3">
                    <label class="form-label">Город из GeoDB</label>
                    <select class="form-select" id="chrono-geodb">
                        <option value="0">— не выбран —</option>
                        <?php
                        $geodb = DB::query('SELECT * FROM geodb ORDER BY title ASC');
                        foreach ($geodb as $g) {
                            echo '<option value="' . $g['id'] . '">' . htmlspecialchars($g['title']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Вид транспорта</label>
                    <select class="form-select" id="chrono-transit">
                        <?php foreach (ChronologyQuery::TRANSIT_TYPES as $id => $label) {
                            echo '<option value="' . $id . '">' . htmlspecialchars($label) . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Дата</label>
                    <input type="date" class="form-control" id="chrono-date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Содержание (HTML допускается)</label>
                    <textarea class="form-control" id="chrono-body" rows="6"></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="chrono-main" checked>
                    <label class="form-check-label" for="chrono-main">Главная новость (показывать в «только главные»)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <a href="#" onclick="createChronology(); return false;" data-bs-dismiss="modal" class="btn btn-primary">Создать</a>
            </div>
        </div>
    </div>
</div>

<div id="chronology-list" style="margin-top:15px">
    <?php
    $items = DB::query('SELECT * FROM chronology ORDER BY time DESC LIMIT 50');
    foreach ($items as $item) {
        $city = ChronologyQuery::cityLabel((int) $item['geodb_id'], $item['city']);
        echo '<div class="card mb-3" id="chrono' . $item['id'] . '"><div class="card-body">';
        echo '<h5>' . htmlspecialchars($city) . ', ' . Date::chronologyDate((int) $item['time']) . '</h5>';
        echo '<div class="break-links">' . $item['body'] . '</div>';
        echo '<a class="btn btn-danger mt-3" href="#" onclick="deleteChronology(' . $item['id'] . '); return false;">Удалить</a>';
        echo '</div></div>';
    }
    ?>
</div>