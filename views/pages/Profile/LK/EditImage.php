<?php

use App\Services\{Auth, DB, Date};
use App\Models\{User, Photo};

$photoId = (int) ($_GET['id'] ?? 0);
$photo = new Photo($photoId);
$content = json_decode($photo->i('content'), true) ?: [];

$postedAt = (int) $photo->i('posted_at');
$day = $postedAt > 0 ? (int) date('j', $postedAt) : 1;
$month = $postedAt > 0 ? (int) date('n', $postedAt) : 1;
$year = $postedAt > 0 ? (int) date('Y', $postedAt) : (int) date('Y');

$license = (string) ($content['copyright'] ?? '1');
$lat = $content['lat'] ?? '';
$lng = $content['lng'] ?? '';
$descr = (string) ($photo->content('comment') ?? $photo->i('postbody'));
$place = (string) $photo->i('place');
$galleryId = (int) $photo->i('gallery_id');
$isDeclined = (int) $photo->i('moderated') === 2;
$entitydataId = (int) $photo->i('entitydata_id');
$linkedEntity = null;
if ($entitydataId > 0) {
    $linkedRows = DB::query(
        'SELECT ed.*, e.title AS entity_type FROM entities_data ed JOIN entities e ON e.id = ed.entityid WHERE ed.id = :id',
        [':id' => $entitydataId]
    );
    $linkedEntity = $linkedRows[0] ?? null;
}
$entityRoute = (string) ($content['entityroute'] ?? '');
$entityNotes = (string) ($content['entitycomment'] ?? '');

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/LoadHead.php'); ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>

<body>
    <div id="backgr"></div>
    <table class="tmain">
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Navbar.php'); ?>
        <tr>
            <td class="main">
                <h1>Редактирование фотографии #<?= $photoId ?></h1>
                <p class="sm"><a href="/lk/history">← Вернуться в журнал</a> · <a href="/photo/<?= $photoId ?>/" target="_blank">Открыть на сайте</a></p>

                <?php if ($isDeclined) { ?>
                    <div class="p20 s3" style="margin:15px 0">
                        <b>Фотография отклонена модератором.</b>
                        <?php if (!empty($content['declineComment'])) { ?>
                            <br>Комментарий: <?= htmlspecialchars($content['declineComment']) ?>
                        <?php } ?>
                        <br>После сохранения изменений фото снова попадёт на модерацию.
                    </div>
                <?php } ?>

                <div class="p20p" style="margin-bottom:20px">
                    <img src="<?= htmlspecialchars($photo->i('photourl')) ?>" style="max-width:320px" alt="">
                </div>

                <form id="editForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $photoId ?>">
                    <input type="hidden" name="lat" id="markerLat" value="<?= htmlspecialchars((string) $lat) ?>">
                    <input type="hidden" name="lng" id="markerLng" value="<?= htmlspecialchars((string) $lng) ?>">

                    <table width="100%">
                        <col width="190">
                        <tbody class="p20">
                            <tr>
                                <td class="lcol">Заменить файл</td>
                                <td>
                                    <label class="button">
                                        Выбрать файл... <input type="file" name="image" accept="image/*,video/*">
                                    </label>
                                    <div class="sm" style="padding-top:5px">Оставьте пустым, если менять файл не нужно.</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="lcol">Дата съёмки</td>
                                <td>
                                    <select name="day" id="day" style="width:48px">
                                        <?php for ($d = 1; $d <= 31; $d++) {
                                            echo '<option value="' . $d . '"' . ($d === $day ? ' selected' : '') . '>' . $d . '</option>';
                                        } ?>
                                    </select>
                                    <select name="month" id="month" style="width:160px; margin:0 -1px">
                                        <?php
                                        $months = [1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'];
                                        foreach ($months as $num => $title) {
                                            echo '<option value="' . $num . '"' . ($num === $month ? ' selected' : '') . '>' . $title . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <select name="year" id="year" style="width:65px">
                                        <?= Date::yearSelectOptions($year) ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="lcol">Сущность</td>
                                <td>
                                    <input type="hidden" name="nid" id="entity_nid" value="<?= $entitydataId ?>">
                                    <table class="nospaces">
                                        <tr>
                                            <td class="sm" style="padding-right:10px">Вид:</td>
                                            <td>
                                                <select id="entity_type" style="min-width:180px">
                                                    <?php
                                                    foreach (DB::query('SELECT * FROM entities ORDER BY title') as $e) {
                                                        $selected = $linkedEntity && (int) $linkedEntity['entityid'] === (int) $e['id'] ? ' selected' : '';
                                                        echo '<option value="' . $e['id'] . '"' . $selected . '>' . htmlspecialchars($e['title']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="sm" style="padding-right:10px">ID/название:</td>
                                            <td>
                                                <input type="text" id="entity_search" maxlength="20" style="width:150px">
                                                <input type="button" id="entity_search_btn" value="Найти" style="margin-left:4px">
                                            </td>
                                        </tr>
                                    </table>
                                    <div id="entity_vlist" class="shadow" style="display:none; margin-top:6px"></div>
                                    <div id="entity_selected" style="margin-top:10px<?= $linkedEntity ? '' : '; display:none' ?>">
                                        <?php if ($linkedEntity) { ?>
                                            <b><a href="/vehicle/<?= (int) $linkedEntity['id'] ?>" target="_blank">#<?= (int) $linkedEntity['id'] ?> <?= htmlspecialchars($linkedEntity['title']) ?></a></b>
                                            <span class="sm" style="color:#777">(<?= htmlspecialchars($linkedEntity['entity_type']) ?>)</span>
                                            <a href="#" id="entity_clear" style="margin-left:10px">убрать привязку</a>
                                        <?php } ?>
                                    </div>
                                    <div id="entity_extra" style="margin-top:10px<?= $linkedEntity ? '' : '; display:none' ?>">
                                        Маршрут: <input type="text" name="entity_route" maxlength="7" style="width:60px" value="<?= htmlspecialchars($entityRoute) ?>">
                                        Примечание: <input type="text" name="entity_notes" maxlength="100" style="width:220px" value="<?= htmlspecialchars($entityNotes) ?>">
                                    </div>
                                    <div class="sm" style="color:#888; padding-top:8px">Необязательно. Оставьте пустым, если фото не относится к конкретной модели.</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="lcol">Галерея</td>
                                <td>
                                    <select name="gallery">
                                        <option value="0"<?= $galleryId === 0 ? ' selected' : '' ?>>Общая</option>
                                        <?php
                                        foreach (DB::query('SELECT * FROM galleries ORDER BY title') as $g) {
                                            $selected = ((int) $g['id'] === $galleryId) ? ' selected' : '';
                                            echo '<option value="' . $g['id'] . '"' . $selected . '>' . htmlspecialchars($g['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="lcol">Место</td>
                                <td><input type="text" name="place" maxlength="255" style="width:506px" value="<?= htmlspecialchars($place) ?>"></td>
                            </tr>
                            <tr>
                                <td class="lcol">Описание</td>
                                <td><textarea name="descr" style="width:506px; height:100px"><?= htmlspecialchars($descr) ?></textarea></td>
                            </tr>
                            <tr>
                                <td class="lcol">Лицензия</td>
                                <td>
                                    <select name="license">
                                        <?php
                                        $licenses = [
                                            '0' => 'Не указана',
                                            '1' => 'Copyright ©',
                                            '2' => 'Атрибуция (BY)',
                                            '3' => 'Атрибуция — С сохранением условий (BY-SA)',
                                            '4' => 'Атрибуция — Некоммерческое использование (BY-NC)',
                                            '5' => 'Атрибуция — Некоммерческое использование — С сохранением условий (BY-NC-SA)',
                                            '6' => 'Атрибуция — Без производных работ (BY-ND)',
                                            '7' => 'Атрибуция — Некоммерческое использование — Без производных работ (BY-NC-ND)',
                                            '8' => 'Передача в общественное достояние (Zero)',
                                            '9' => 'Нет авторских прав (Mark)',
                                        ];
                                        foreach ($licenses as $value => $label) {
                                            echo '<option value="' . $value . '"' . ($license === $value ? ' selected' : '') . '>' . $label . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="lcol">Карта</td>
                                <td>
                                    <input type="checkbox" name="nomap" id="nomap" value="1"<?= ($lat === '' || $lat === null) && ($lng === '' || $lng === null) ? ' checked' : '' ?>>
                                    <label for="nomap">Не указывать место на карте</label>
                                    <div id="map_canvas" style="width:600px; height:300px; margin-top:10px"></div>
                                </td>
                            </tr>
                            <tr>
                                <td class="lcol">Опции</td>
                                <td>
                                    <div><input type="checkbox" name="disablecomments" value="1" id="disablecomments"<?= ($content['comments'] ?? '') === 'disabled' ? ' checked' : '' ?>> <label for="disablecomments">Отключить комментарии</label></div>
                                    <div><input type="checkbox" name="disablerating" value="1" id="disablerating"<?= ($content['rating'] ?? '') === 'disabled' ? ' checked' : '' ?>> <label for="disablerating">Отключить оценку</label></div>
                                    <div><input type="checkbox" name="disableshowtop" value="1" id="disableshowtop"<?= ($content['showtop'] ?? '') === 'disabled' ? ' checked' : '' ?>> <label for="disableshowtop">Не продвигать в общем топе</label></div>
                                    <div><input type="checkbox" name="disableexif" value="1" id="disableexif"<?= ($photo->i('exif') && str_contains($photo->i('exif'), 'disabled')) ? ' checked' : '' ?>> <label for="disableexif">Скрыть EXIF</label></div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td style="padding-top:15px">
                                    <button type="submit" id="submitbtn" class="btn btn-primary">Сохранить изменения</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </td>
        </tr>
        <?php include($_SERVER['DOCUMENT_ROOT'] . '/views/components/Footer.php'); ?>
    </table>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        const defaultLat = <?= ($lat !== '' && $lat !== null) ? (float) $lat : '55.7558' ?>;
        const defaultLng = <?= ($lng !== '' && $lng !== null) ? (float) $lng : '37.6173' ?>;
        const hasCoords = <?= ($lat !== '' && $lat !== null && $lng !== '' && $lng !== null) ? 'true' : 'false' ?>;

        const map = L.map('map_canvas').setView([defaultLat, defaultLng], hasCoords ? 14 : 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = null;
        if (hasCoords) {
            marker = L.marker([defaultLat, defaultLng]).addTo(map);
        }

        map.on('click', function(e) {
            if (document.getElementById('nomap').checked) return;
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
            document.getElementById('markerLat').value = e.latlng.lat;
            document.getElementById('markerLng').value = e.latlng.lng;
        });

        document.getElementById('nomap').addEventListener('change', function() {
            document.getElementById('map_canvas').style.display = this.checked ? 'none' : 'block';
        });
        if (document.getElementById('nomap').checked) {
            document.getElementById('map_canvas').style.display = 'none';
        }

        function setEntityBinding(id, title, typeTitle) {
            $('#entity_nid').val(id);
            if (id > 0) {
                $('#entity_selected').html(
                    '<b><a href="/vehicle/' + id + '" target="_blank">#' + id + ' ' + $('<span>').text(title).html() + '</a></b> ' +
                    '<span class="sm" style="color:#777">(' + $('<span>').text(typeTitle).html() + ')</span> ' +
                    '<a href="#" id="entity_clear" style="margin-left:10px">убрать привязку</a>'
                ).show();
                $('#entity_extra').show();
            } else {
                $('#entity_selected').hide().html('');
                $('#entity_extra').hide();
                $('input[name="entity_route"], input[name="entity_notes"]').val('');
            }
            $('#entity_vlist').hide().html('');
        }

        $('#entity_search_btn').on('click', function() {
            const num = $('#entity_search').val().trim();
            if (!num) return;
            $('#entity_vlist').html('<div style="padding:6px 10px">Поиск...</div>').show();
            $.get('/api/vehicles/load', { type: $('#entity_type').val(), num: num }, function(html) {
                $('#entity_vlist').html(html);
            });
        });

        $('#entity_vlist').on('click', '.found_vehicle', function() {
            const vid = $(this).data('vid');
            const title = $('.mname', this).text();
            const typeTitle = $('td.d', this).last().text();
            setEntityBinding(vid, title, typeTitle);
        });

        $('#entity_selected').on('click', '#entity_clear', function(e) {
            e.preventDefault();
            setEntityBinding(0, '', '');
        });

        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            $('#submitbtn').prop('disabled', true);
            const formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: '/api/photo/edit',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    let data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.errorcode === 0) {
                        Notify.noty('success', 'Сохранено');
                        window.location.href = '/photo/' + data.id + '/';
                        return;
                    }
                    Notify.noty('danger', data.error || 'Ошибка сохранения');
                    $('#submitbtn').prop('disabled', false);
                },
                error: function(jx) {
                    Notify.noty('danger', jx.responseText || 'Ошибка сервера');
                    $('#submitbtn').prop('disabled', false);
                }
            });
        });
    </script>
</body>

</html>