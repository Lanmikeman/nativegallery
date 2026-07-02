<?php

use App\Services\DB;
?>

<h1><b>Ссылки</b></h1>
<a data-bs-toggle="modal" data-bs-target="#createLinkModal" href="#" class="btn btn-primary">Добавить ссылку</a>

<div class="modal fade" id="createLinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"><b>Добавить ссылку</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input type="text" class="form-control" id="link-title">
                </div>
                <div class="mb-3">
                    <label class="form-label">URL</label>
                    <input type="url" class="form-control" id="link-url" placeholder="https://">
                </div>
                <div class="mb-3">
                    <label class="form-label">Порядок сортировки</label>
                    <input type="number" class="form-control" id="link-sort" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <a href="#" onclick="createSiteLink(); return false;" data-bs-dismiss="modal" class="btn btn-primary">Создать</a>
            </div>
        </div>
    </div>
</div>

<table class="table" style="margin-top:15px" id="links-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>URL</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $links = DB::query('SELECT * FROM site_links ORDER BY sort ASC, id ASC');
        foreach ($links as $link) {
            echo '<tr id="link' . $link['id'] . '">';
            echo '<td>' . $link['id'] . '</td>';
            echo '<td>' . htmlspecialchars($link['title']) . '</td>';
            echo '<td><a href="' . htmlspecialchars($link['url']) . '" target="_blank">' . htmlspecialchars($link['url']) . '</a></td>';
            echo '<td><a class="btn btn-sm btn-danger" href="#" onclick="deleteSiteLink(' . $link['id'] . '); return false;">Удалить</a></td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>