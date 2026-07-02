<?php

use App\Services\DB;

if (isset($_POST['create_gallery'])) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $opened = isset($_POST['opened']) ? 1 : 0;
    if ($title !== '') {
        DB::query(
            'INSERT INTO galleries (title, opened) VALUES (:title, :opened)',
            [':title' => $title, ':opened' => $opened]
        );
    }
    header('Location: /admin?type=Galleries');
    exit;
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId > 0) {
        DB::query('DELETE FROM galleries WHERE id = :id', [':id' => $deleteId]);
    }
    header('Location: /admin?type=Galleries');
    exit;
}

if (isset($_POST['toggle_opened'])) {
    $galleryId = (int) ($_POST['gallery_id'] ?? 0);
    $opened = (int) ($_POST['opened'] ?? 0) === 1 ? 1 : 0;
    if ($galleryId > 0) {
        DB::query('UPDATE galleries SET opened = :opened WHERE id = :id', [
            ':opened' => $opened,
            ':id' => $galleryId,
        ]);
    }
    header('Location: /admin?type=Galleries');
    exit;
}
?>

<h1><b>Галереи</b></h1>
<p class="sm text-muted">Открытые галереи отображаются на <a href="/misc" target="_blank">/misc</a> и доступны при загрузке фото.</p>

<a data-bs-toggle="modal" data-bs-target="#createGalleryModal" href="#" class="btn btn-primary">Создать галерею</a>

<div class="modal fade" id="createGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/admin?type=Galleries">
                <div class="modal-header">
                    <h1 class="modal-title fs-5"><b>Новая галерея</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="opened" value="1" id="galleryOpened" checked>
                        <label class="form-check-label" for="galleryOpened">Открытая (видна на сайте)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="create_gallery" value="1" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<table class="table" style="margin-top:15px">
    <thead>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Открыта</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $galleries = DB::query('SELECT * FROM galleries ORDER BY title ASC');
        foreach ($galleries as $gallery) {
            $isOpen = (int) $gallery['opened'] === 1;
            echo '<tr>';
            echo '<td>' . (int) $gallery['id'] . '</td>';
            echo '<td>' . htmlspecialchars($gallery['title']) . '</td>';
            echo '<td>';
            echo '<form method="post" action="/admin?type=Galleries" style="display:inline">';
            echo '<input type="hidden" name="gallery_id" value="' . (int) $gallery['id'] . '">';
            echo '<input type="hidden" name="opened" value="' . ($isOpen ? '0' : '1') . '">';
            echo '<button type="submit" name="toggle_opened" value="1" class="btn btn-sm ' . ($isOpen ? 'btn-success' : 'btn-outline-secondary') . '">';
            echo $isOpen ? 'Да' : 'Нет';
            echo '</button></form>';
            echo '</td>';
            echo '<td><a class="btn btn-sm btn-danger" href="/admin?type=Galleries&delete=' . (int) $gallery['id'] . '" onclick="return confirm(\'Удалить галерею?\')">Удалить</a></td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>