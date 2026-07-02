<?php

use App\Services\DB;

?>
<h1><b>Информационные страницы</b></h1>
<p class="text-muted">Публичный адрес: <code>/page/ID</code> (как на transphoto.org). HTML в теле страницы разрешён.</p>
<a data-bs-toggle="modal" data-bs-target="#createPageModal" href="#" class="btn btn-primary mb-3">Создать страницу</a>

<div class="modal fade" id="createPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"><b>Создать страницу</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="page-create-id" class="form-label">ID (необязательно)</label>
                    <input type="number" class="form-control" id="page-create-id" min="1" placeholder="Авто, если пусто">
                    <div class="form-text">Например, <code>1</code> для ссылки <code>/page/1</code> из личного кабинета.</div>
                </div>
                <div class="mb-3">
                    <label for="page-create-title" class="form-label">Заголовок</label>
                    <input type="text" class="form-control" id="page-create-title" required>
                </div>
                <div class="mb-3">
                    <label for="page-create-body" class="form-label">Содержание (HTML)</label>
                    <textarea class="form-control" id="page-create-body" rows="12"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <a type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</a>
                <a href="#" onclick="createPage(); return false;" class="btn btn-primary">Создать</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"><b>Редактировать страницу</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-page-id" value="">
                <div class="mb-3">
                    <label class="form-label">Публичный URL</label>
                    <div><code id="edit-page-url"></code></div>
                </div>
                <div class="mb-3">
                    <label for="edit-page-title" class="form-label">Заголовок</label>
                    <input type="text" class="form-control" id="edit-page-title" required>
                </div>
                <div class="mb-3">
                    <label for="edit-page-body" class="form-label">Содержание (HTML)</label>
                    <textarea class="form-control" id="edit-page-body" rows="14"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <a type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</a>
                <a href="#" onclick="updatePage(); return false;" class="btn btn-primary">Сохранить</a>
            </div>
        </div>
    </div>
</div>

<div id="pages-list">
    <?php
    $pages = DB::query('SELECT id FROM pages ORDER BY id');
    if ($pages === []) {
        echo '<div class="alert alert-secondary">Страниц пока нет. Создайте первую или примените миграцию <code>sql_0009.sql</code>.</div>';
    }
    foreach ($pages as $row) {
        (new \App\Models\Admin\Page((int) $row['id']))->view();
    }
    ?>
</div>