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

<script>
function parsePageAttr(raw) {
    if (raw == null || raw === '') return '';
    try { return JSON.parse(raw); } catch (e) { return String(raw); }
}

function loadPages() {
    $.ajax({
        url: '/api/admin/loadpages',
        success: function (response) { $('#pages-list').html(response); }
    });
}

function createPage() {
    $.ajax({
        type: 'POST',
        url: '/api/admin/pages/create',
        data: {
            id: $('#page-create-id').val(),
            title: $('#page-create-title').val(),
            body: $('#page-create-body').val()
        },
        success: function (response) {
            var jsonData = typeof response === 'string' ? JSON.parse(response) : response;
            if (parseInt(jsonData.errorcode, 10) !== 0) {
                Notify.noty('danger', jsonData.message || 'Не удалось создать');
                return;
            }
            Notify.noty('success', 'Страница создана');
            bootstrap.Modal.getInstance(document.getElementById('createPageModal')).hide();
            $('#page-create-id, #page-create-title, #page-create-body').val('');
            loadPages();
        },
        error: function () { Notify.noty('danger', 'Ошибка создания'); }
    });
}

function showEditPageModal(pageId, title, body) {
    $('#edit-page-id').val(pageId);
    $('#edit-page-url').text('/page/' + pageId);
    $('#edit-page-title').val(title || '');
    $('#edit-page-body').val(body || '');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editPageModal')).show();
}

function openEditPage(pageId, title, body) {
    if (title !== undefined) {
        showEditPageModal(pageId, title, body);
        return;
    }
    $.ajax({
        type: 'GET',
        url: '/api/admin/pages/' + pageId,
        dataType: 'json',
        success: function (jsonData) {
            if (parseInt(jsonData.errorcode, 10) !== 0 || !jsonData.page) {
                Notify.noty('danger', jsonData.message || 'Не удалось загрузить');
                return;
            }
            showEditPageModal(pageId, jsonData.page.title, jsonData.page.body);
        },
        error: function () { Notify.noty('danger', 'Ошибка загрузки'); }
    });
}

$(document).on('click', '.edit-page-btn', function (e) {
    e.preventDefault();
    openEditPage(
        $(this).data('id'),
        parsePageAttr($(this).attr('data-page-title')),
        parsePageAttr($(this).attr('data-page-body'))
    );
});

function updatePage() {
    var pageId = $('#edit-page-id').val();
    $.ajax({
        type: 'POST',
        url: '/api/admin/pages/' + pageId + '/edit',
        data: {
            title: $('#edit-page-title').val(),
            body: $('#edit-page-body').val()
        },
        success: function (response) {
            var jsonData = typeof response === 'string' ? JSON.parse(response) : response;
            if (parseInt(jsonData.errorcode, 10) !== 0) {
                Notify.noty('danger', jsonData.message || 'Не удалось сохранить');
                return;
            }
            Notify.noty('success', 'Страница обновлена');
            bootstrap.Modal.getInstance(document.getElementById('editPageModal')).hide();
            loadPages();
        },
        error: function () { Notify.noty('danger', 'Ошибка сохранения'); }
    });
}

function deletePage(pageId) {
    if (!confirm('Удалить страницу #' + pageId + '?')) return;
    $.ajax({
        type: 'POST',
        url: '/api/admin/pages/' + pageId + '/delete',
        success: function (response) {
            var jsonData = typeof response === 'string' ? JSON.parse(response) : response;
            if (parseInt(jsonData.errorcode, 10) !== 0) {
                Notify.noty('danger', jsonData.message || 'Не удалось удалить');
                return;
            }
            Notify.noty('success', 'Страница удалена');
            loadPages();
        },
        error: function () { Notify.noty('danger', 'Ошибка удаления'); }
    });
}
</script>