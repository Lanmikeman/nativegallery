<?php

use App\Services\{AudioLibrary, DB};

$hasTable = AudioLibrary::globalStreamsTableExist();
?>
<h1><b>Радиостанции сайта</b></h1>
<p class="text-muted">Станции из этого списка видны всем пользователям в разделе «Музыка» в дополнение к их личным потокам.</p>

<?php if (!$hasTable) { ?>
    <div class="alert alert-warning">Примените миграцию <code>sqlcore/sql_0011.sql</code>.</div>
<?php } else { ?>
    <a data-bs-toggle="modal" data-bs-target="#createRadioModal" href="#" class="btn btn-primary">Добавить станцию</a>

    <div class="modal fade" id="createRadioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5"><b>Добавить радиостанцию</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название станции</label>
                        <input type="text" class="form-control" id="radio-title" placeholder="Fetbuk Radio 320">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL потока</label>
                        <input type="url" class="form-control" id="radio-url" placeholder="https://radio.fetbuk.ru/ptrc320">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Порядок сортировки</label>
                        <input type="number" class="form-control" id="radio-sort" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <a href="#" onclick="createGlobalRadio(); return false;" data-bs-dismiss="modal" class="btn btn-primary">Создать</a>
                </div>
            </div>
        </div>
    </div>

    <table class="table" style="margin-top:15px" id="radio-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>URL</th>
                <th>Порядок</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stations = DB::query('SELECT * FROM audio_global_streams ORDER BY sort_order ASC, id ASC');
            foreach ($stations as $station) {
                echo '<tr id="radio' . (int) $station['id'] . '">';
                echo '<td>' . (int) $station['id'] . '</td>';
                echo '<td>' . htmlspecialchars($station['title']) . '</td>';
                echo '<td><a href="' . htmlspecialchars($station['url']) . '" target="_blank" rel="noopener">' . htmlspecialchars($station['url']) . '</a></td>';
                echo '<td>' . (int) $station['sort_order'] . '</td>';
                echo '<td><a class="btn btn-sm btn-danger" href="#" onclick="deleteGlobalRadio(' . (int) $station['id'] . '); return false;">Удалить</a></td>';
                echo '</tr>';
            }
            if (empty($stations)) {
                echo '<tr><td colspan="5" class="text-muted">Пока нет общих радиостанций</td></tr>';
            }
            ?>
        </tbody>
    </table>
<?php } ?>