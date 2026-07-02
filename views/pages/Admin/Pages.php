<?php

use App\Services\DB;

?>
<h1><b>Информационные страницы</b></h1>
<p class="admin-pages-hint text-muted">Публичный адрес: <code>/page/ID</code> (как на transphoto.org). HTML в теле страницы разрешён.</p>

<div class="admin-pages-toolbar">
    <a data-bs-toggle="modal" data-bs-target="#createPageModal" href="#" class="btn btn-primary">Создать страницу</a>
</div>

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
                    <textarea class="form-control admin-pages-editor" id="page-create-body" rows="14" spellcheck="false"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" onclick="createPage(); return false;">Создать</button>
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
                    <div><code id="edit-page-url"></code> <a href="#" id="edit-page-open" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-2">Открыть на сайте</a></div>
                </div>
                <div class="mb-3">
                    <label for="edit-page-title" class="form-label">Заголовок</label>
                    <input type="text" class="form-control" id="edit-page-title" required>
                </div>
                <div class="mb-3">
                    <label for="edit-page-body" class="form-label">Содержание (HTML)</label>
                    <textarea class="form-control admin-pages-editor" id="edit-page-body" rows="16" spellcheck="false"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" onclick="updatePage(); return false;">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive admin-pages-table-wrap">
    <table class="table table-striped admin-pages-table">
        <thead>
            <tr>
                <th class="admin-pages-table__id">ID</th>
                <th>Заголовок</th>
                <th>Адрес</th>
                <th>Изменения</th>
                <th class="admin-pages-table__actions">Действия</th>
            </tr>
        </thead>
        <tbody id="pages-list">
            <?php
            $pages = DB::query('SELECT id FROM pages ORDER BY id');
            if ($pages === []) {
                echo '<tr><td colspan="5" class="admin-pages-table__empty">Страниц пока нет. Создайте первую или примените миграцию <code>sql_0009.sql</code>.</td></tr>';
            } else {
                foreach ($pages as $row) {
                    (new \App\Models\Admin\Page((int) $row['id']))->viewRow();
                }
            }
            ?>
        </tbody>
    </table>
</div>