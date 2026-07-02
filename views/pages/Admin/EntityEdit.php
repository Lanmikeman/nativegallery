<?php

use App\Services\DB;
use App\Models\Vehicle;

$id = (int) ($_GET['id'] ?? 0);
$isModel = isset($_GET['mod']) && (int) $_GET['mod'] === 1;

if ($id <= 0) {
    header('Location: /admin?type=' . ($isModel ? 'Models' : 'Entities'));
    exit;
}

if ($isModel) {
    $rows = DB::query('SELECT * FROM entities_data WHERE id = :id', [':id' => $id]);
    if (empty($rows)) {
        header('Location: /admin?type=Models');
        exit;
    }
    $record = $rows[0];
    $entity = new Vehicle((int) $record['entityid']);
    $fieldDefs = json_decode((string) $entity->i('sampledata'), true) ?: [];
    $fieldValues = json_decode((string) $record['content'], true) ?: [];

    if (isset($_POST['save_model'])) {
        $filteredInputs = [];
        foreach ($_POST as $key => $value) {
            if (str_starts_with($key, 'modelinput_')) {
                $filteredInputs[$key] = $value;
            }
        }
        ksort($filteredInputs);
        $result = [];
        $counter = 1;
        foreach ($filteredInputs as $value) {
            $result[$counter] = ['value' => $value];
            $counter++;
        }

        DB::query(
            'UPDATE entities_data SET title = :title, comment = :comment, content = :content WHERE id = :id',
            [
                ':title' => trim((string) ($_POST['title'] ?? '')),
                ':comment' => trim((string) ($_POST['comment'] ?? '')),
                ':content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ':id' => $id,
            ]
        );
        header('Location: /admin?type=Models');
        exit;
    }
    ?>
    <h1><b>Редактирование модели</b></h1>
    <p class="sm"><a href="/admin?type=Models">← К списку моделей</a> · <a href="/vehicle/<?= $id ?>/" target="_blank">Страница на сайте</a></p>
    <form method="post" action="/admin?type=EntityEdit&amp;id=<?= $id ?>&amp;mod=1" style="max-width:640px">
        <div class="mb-3">
            <label class="form-label">Сущность</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($entity->i('title')) ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Название</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($record['title']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Описание</label>
            <input type="text" name="comment" class="form-control" value="<?= htmlspecialchars($record['comment']) ?>">
        </div>
        <?php
        $num = 1;
        foreach ($fieldDefs as $field) {
            $value = $fieldValues[$num]['value'] ?? '';
            $required = ($field['important'] ?? '') === '1' ? 'required' : '';
            echo '<div class="mb-3">';
            echo '<label class="form-label">' . htmlspecialchars($field['name'] ?? ('Поле ' . $num)) . '</label>';
            echo '<input type="text" name="modelinput_' . $num . '" class="form-control" value="' . htmlspecialchars((string) $value) . '" ' . $required . '>';
            echo '</div>';
            $num++;
        }
        ?>
        <button type="submit" name="save_model" value="1" class="btn btn-primary">Сохранить</button>
        <a href="/admin?type=Models" class="btn btn-secondary">Отмена</a>
    </form>
    <?php
    return;
}

$rows = DB::query('SELECT * FROM entities WHERE id = :id', [':id' => $id]);
if (empty($rows)) {
    header('Location: /admin?type=Entities');
    exit;
}
$record = $rows[0];

if (isset($_POST['save_entity'])) {
    DB::query(
        'UPDATE entities SET title = :title, color = :color WHERE id = :id',
        [
            ':title' => trim((string) ($_POST['title'] ?? '')),
            ':color' => trim((string) ($_POST['color'] ?? '#563d7c')),
            ':id' => $id,
        ]
    );
    header('Location: /admin?type=Entities');
    exit;
}
?>

<h1><b>Редактирование вида сущности</b></h1>
<p class="sm"><a href="/admin?type=Entities">← К списку сущностей</a></p>
<form method="post" action="/admin?type=EntityEdit&amp;id=<?= $id ?>" style="max-width:480px">
    <div class="mb-3">
        <label class="form-label">Название</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($record['title']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Цвет</label>
        <input type="color" name="color" class="form-control form-control-color" value="<?= htmlspecialchars($record['color'] ?: '#563d7c') ?>">
    </div>
    <p class="sm text-muted">Шаблон полей модели (sampledata) пока редактируется только при создании сущности.</p>
    <button type="submit" name="save_entity" value="1" class="btn btn-primary">Сохранить</button>
    <a href="/admin?type=Entities" class="btn btn-secondary">Отмена</a>
</form>