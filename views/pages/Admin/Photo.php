<?php

use App\Services\{Auth, DB, Date};
use App\Models\User;

$photoStatusLabel = static function (int $moderated): array {
    return match ($moderated) {
        0 => ['Ожидает модерации', 's0'],
        2 => ['Отклонено', 's15'],
        default => ['Опубликовано', 's12'],
    };
};

$activeTab = (isset($_GET['full_tab']) && $_GET['full_tab'] === '1') ? 'full' : 'moderate';

$fullListPerPageOptions = [10, 25, 50, 100];
$fullListSortOptions = [
    'id' => 'ID',
    'posted_at' => 'Дата съёмки',
    'timeupload' => 'Дата публикации',
    'place' => 'Подпись',
    'user_id' => 'Автор (ID)',
    'moderated' => 'Статус',
];

$fullListQuery = [
    'full_tab' => $activeTab === 'full' ? '1' : null,
    'full_page' => max(1, (int) ($_GET['full_page'] ?? 1)),
    'full_per' => (int) ($_GET['full_per'] ?? 25),
    'full_sort' => (string) ($_GET['full_sort'] ?? 'id'),
    'full_order' => strtolower((string) ($_GET['full_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
    'full_status' => (string) ($_GET['full_status'] ?? 'all'),
    'full_q' => trim((string) ($_GET['full_q'] ?? '')),
];

if (!in_array($fullListQuery['full_per'], $fullListPerPageOptions, true)) {
    $fullListQuery['full_per'] = 25;
}
if (!array_key_exists($fullListQuery['full_sort'], $fullListSortOptions)) {
    $fullListQuery['full_sort'] = 'id';
}
if (!in_array($fullListQuery['full_status'], ['all', 'pending', 'published', 'declined'], true)) {
    $fullListQuery['full_status'] = 'all';
}

$fullListWhere = ['1=1'];
$fullListParams = [];
if ($fullListQuery['full_status'] === 'pending') {
    $fullListWhere[] = 'moderated = 0';
} elseif ($fullListQuery['full_status'] === 'published') {
    $fullListWhere[] = 'moderated = 1';
} elseif ($fullListQuery['full_status'] === 'declined') {
    $fullListWhere[] = 'moderated = 2';
}
if ($fullListQuery['full_q'] !== '') {
    if (ctype_digit($fullListQuery['full_q'])) {
        $fullListWhere[] = '(id = :search_id OR place LIKE :search_like)';
        $fullListParams[':search_id'] = (int) $fullListQuery['full_q'];
        $fullListParams[':search_like'] = '%' . $fullListQuery['full_q'] . '%';
    } else {
        $fullListWhere[] = 'place LIKE :search_like';
        $fullListParams[':search_like'] = '%' . $fullListQuery['full_q'] . '%';
    }
}

$fullListWhereSql = implode(' AND ', $fullListWhere);
$fullListTotal = (int) DB::query(
    'SELECT COUNT(*) AS cnt FROM photos WHERE ' . $fullListWhereSql,
    $fullListParams
)[0]['cnt'];
$fullListPages = max(1, (int) ceil($fullListTotal / $fullListQuery['full_per']));
if ($fullListQuery['full_page'] > $fullListPages) {
    $fullListQuery['full_page'] = $fullListPages;
}
$fullListOffset = ($fullListQuery['full_page'] - 1) * $fullListQuery['full_per'];
$fullListOrderSql = $fullListQuery['full_sort'] . ' ' . strtoupper($fullListQuery['full_order']);
$fullListPhotos = DB::query(
    'SELECT * FROM photos WHERE ' . $fullListWhereSql . ' ORDER BY ' . $fullListOrderSql . ' LIMIT ' . (int) $fullListQuery['full_per'] . ' OFFSET ' . (int) $fullListOffset,
    $fullListParams
);
$fullListFrom = $fullListTotal > 0 ? $fullListOffset + 1 : 0;
$fullListTo = min($fullListOffset + $fullListQuery['full_per'], $fullListTotal);

$fullListPageNumbers = [];
if ($fullListPages <= 9) {
    $fullListPageNumbers = range(1, $fullListPages);
} else {
    $fullListPageNumbers[] = 1;
    $start = max(2, $fullListQuery['full_page'] - 2);
    $end = min($fullListPages - 1, $fullListQuery['full_page'] + 2);
    if ($start > 2) {
        $fullListPageNumbers[] = '…';
    }
    for ($pageNum = $start; $pageNum <= $end; $pageNum++) {
        $fullListPageNumbers[] = $pageNum;
    }
    if ($end < $fullListPages - 1) {
        $fullListPageNumbers[] = '…';
    }
    $fullListPageNumbers[] = $fullListPages;
}

$photoAdminUrl = static function (array $overrides = []) use ($fullListQuery): string {
    $query = array_merge($fullListQuery, $overrides);
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        }
    }
    $query['type'] = 'Photo';

    return '/admin?' . http_build_query($query);
};

?>
<style>
.admin-photo-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 14px;
    align-items: flex-end;
    margin: 12px 0 16px;
}
.admin-photo-toolbar .toolbar-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.admin-photo-toolbar label {
    font-size: 12px;
    color: var(--theme-muted-color);
}
.admin-photo-toolbar select,
.admin-photo-toolbar input[type="text"],
.admin-photo-toolbar input[type="number"] {
    min-width: 120px;
}
.admin-photo-toolbar .toolbar-search {
    min-width: 220px;
}
.admin-photo-pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    margin: 16px 0 4px;
}
.admin-photo-pagination .page-link {
    display: inline-block;
    min-width: 34px;
    padding: 4px 8px;
    text-align: center;
    border: 1px solid var(--theme-border-color);
    background: var(--theme-form-color);
    color: var(--theme-text-color);
    font-family: var(--narrow-font);
    font-size: 16px;
}
.admin-photo-pagination .page-link:hover {
    background: var(--theme-link-hover-bg-color);
}
.admin-photo-pagination .page-link.is-active {
    background: var(--theme-bg-color);
    color: var(--theme-fg-color);
    border-color: var(--theme-bg-color);
}
.admin-photo-pagination .page-link.is-disabled {
    opacity: 0.45;
    pointer-events: none;
}
.admin-photo-summary {
    margin: 0 0 8px;
}
    #sbmt {
        display: inline-block;
    box-sizing: border-box;
    vertical-align: middle;
    cursor: pointer;
    position: relative;
    padding: 2px 15px 3px;
    height: auto;
    text-align: center;
    font-family: var(--narrow-font);
    font-size: 17px;
    font-weight: bold;
    color: var(--theme-fg-color);
    background-color: #777;
    background-color: var(--theme-bg-color);
    transition: none;
    border: none;
    user-select: none;
    -moz-user-select: none;
    -webkit-user-select: none;
    -ms-user-select: none;
    border-radius: 0;
    -webkit-border-radius: 0;
    }
    </style>
<h1><b>Фотографии</b></h1>
                    <div class="v-header__tabs">
                <div class="v-tabs">
                    <div class="v-tabs__scroll">
                        <div class="v-tabs__content"><a href="<?= htmlspecialchars($photoAdminUrl(['full_tab' => '1', 'full_page' => 1])) ?>" id="full" class="v-tab v-tab-b<?= $activeTab === 'full' ? ' v-tab--active' : '' ?>"><span class="v-tab__label">
                                    Полный список

                                    </span></a><a href="<?= htmlspecialchars($photoAdminUrl(['full_tab' => null, 'full_page' => null, 'full_per' => null, 'full_sort' => null, 'full_order' => null, 'full_status' => null, 'full_q' => null])) ?>" id="moderate" class="v-tab v-tab-b<?= $activeTab === 'moderate' ? ' v-tab--active' : '' ?>"><span class="v-tab__label">
                                    Ожидают модерации

                                    </span></a>


                        </div>
                    </div>
                </div>
            </div>
                    <script src="/js/diff.js"></script>
                    <script src="/js/pwrite-compare.js"></script>
                 <div id="moderate__block" class="<?= $activeTab === 'moderate' ? 'active__block' : '' ?>"<?= $activeTab !== 'moderate' ? ' style="display:none"' : '' ?>>
                    <div class="p20w" style="display:block">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th width="100">Изображение</th>
                                    <th width="50%">Информация</th>
                                    <th>Действия</th>
                                </tr>
                               
                                
                               <?php
                               $photos = DB::query('SELECT * FROM photos WHERE moderated=0 ORDER BY id DESC');
                               foreach ($photos as $p) {
                                    if ($p['moderated'] === 0) {
                                        $color = 's0';
                                    } else if ($p['moderated'] === 2) {
                                        $color = 's15';
                                    } else {
                                        $color = 's12';
                                    }
                                    $author = new User($p['user_id']);
                                    echo ' <tr id="pht'.$p['id'].'" class="'.$color.'">
                                    <td>
                                        <a href="/photo/'.$p['id'].'/" target="_blank" class="prw">
                                            <img src="'.$p['photourl'].'" class="f">
                                            
                                        </a>
                                    </td>
                                    <td>
                                        <p><span style="word-spacing:-1px"><b>'.htmlspecialchars($p['place']).'</b></span></p>
                                        <p class="sm"><b>'.Date::zmdate($p['posted_at']).'</b><br>Автор: <a href="/author/'.$p['user_id'].'/">'.htmlspecialchars($author->i('username')).'</a></p>
                                       
                                    </td>
                                    <td class="c">
                                   ';
                                   if ($p['moderated'] === 0) {
                                    echo '<a data-bs-toggle="modal" data-bs-target="#acceptPhotoModal'.$p['id'].'" href="#" class="btn btn-primary">Принять</a>
                                    <a data-bs-toggle="modal" data-bs-target="#declinePhotoModal'.$p['id'].'" href="#" class="btn btn-danger">Отклонить</a>';
                                   }
                                   echo '
                                    </td>';
                                    if ($p['endmoderation'] === -1) {
                                        $endm = 'На модерации';
                                    }
                                   echo '
                                </tr>

   <div class="modal fade" id="acceptPhotoModal'.$p['id'].'" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel"><b>Принятие фотографии</b></h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
       <div class="form-check">
  <input name="accept'.$p['id'].'" value="0" class="form-check-input" type="radio" name="flexRadioDefault" id="acceptReason1" checked>
  <label class="form-check-label" for="acceptReason1">
    Нормальная публикация
  </label>
</div>
<div class="form-check">
  <input name="accept'.$p['id'].'" value="1" class="form-check-input" type="radio" name="flexRadioDefault" id="acceptReason3">
  <label class="form-check-label" for="acceptReason3">
    Условная публикация
  </label>
</div>
<div class="form-check">
  <input name="accept'.$p['id'].'" value="2" class="form-check-input" type="radio" name="flexRadioDefault" id="acceptReason2">
  <label class="form-check-label" for="acceptReason2">
    Временная публикация
  </label>
</div>
<div class="form-check">
  <input name="accept'.$p['id'].'" value="3" class="form-check-input" type="radio" name="flexRadioDefault" id="acceptReason4">
  <label class="form-check-label" for="acceptReason4">
    Техническая публикация
  </label>
</div>
<h6 class="mt-3">Оценка</h6>
<div class="row">
<div class="col-6">
<div class="form-check">
  <input name="iRate'.$p['id'].'" value="1" class="form-check-input" type="radio" name="flexRadioDefault" id="iRate1" checked>
  <label class="form-check-label" for="iRate1">
    И+
  </label>
</div>
<div class="form-check">
  <input name="iRate'.$p['id'].'" value="0" class="form-check-input" type="radio" name="flexRadioDefault" id="iRate0">
  <label class="form-check-label" for="iRate0">
    И-
  </label>
</div>
</div>
<div class="col-6">
<div class="form-check">
  <input name="kRate'.$p['id'].'" value="1" class="form-check-input" type="radio" name="flexRadioDefault" id="kRate1" checked>
  <label class="form-check-label" for="kRate1">
    К+
  </label>
</div>
<div class="form-check">
  <input name="kRate'.$p['id'].'" value="0" class="form-check-input" type="radio" name="flexRadioDefault" id="kRate0">
  <label class="form-check-label" for="kRate0">
    К-
  </label>
</div>
</div>
</div>
<h6 class="mt-3">Другие действия</h6>
<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">Дополнительный комментарий</label>
  <textarea class="form-control" id="exampleFormControlTextarea1" name="comment" rows="3"></textarea>
</div>
      </div>
      <div class="modal-footer">
        <a type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</a>'; ?>
        <a href="#" onclick="photoAction(<?=$p['id']?>, document.querySelector(`input[name='accept<?=$p['id']?>']:checked`).value, document.querySelector(`input[name='kRate<?=$p['id']?>']:checked`).value, document.querySelector(`input[name='iRate<?=$p['id']?>']:checked`).value, 1); return false;" data-bs-dismiss="modal" class="btn btn-primary">Сохранить</a>
        <?php echo '
      </div>
    </div>
  </div>
</div>



                                
                                <div class="modal fade" id="declinePhotoModal'.$p['id'].'" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel"><b>Причина отклонения</b></h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
       <div class="form-check">
  <input name="decline'.$p['id'].'" value="1" class="form-check-input" type="radio" name="flexRadioDefault" id="declineReason1">
  <label class="form-check-label" for="declineReason1">
    Малоинформативный бред
  </label>
</div>
<div class="form-check">
  <input name="decline'.$p['id'].'" checked value="2" class="form-check-input" type="radio" name="flexRadioDefault" id="declineReason2">
  <label class="form-check-label" for="declineReason2">
    Не подходит для сайта
  </label>
</div>
<div class="form-check">
  <input name="decline'.$p['id'].'" value="3" class="form-check-input" type="radio" name="flexRadioDefault" id="declineReason3">
  <label class="form-check-label" for="declineReason3">
    Порнография
  </label>
</div>
<div class="form-check">
  <input name="decline'.$p['id'].'" value="4" class="form-check-input" type="radio" name="flexRadioDefault" id="declineReason4">
  <label class="form-check-label" for="declineReason4">
    Травля/издевательство над человеком
  </label>
</div>
<div class="form-check">
  <input name="decline'.$p['id'].'" value="5" class="form-check-input" type="radio" name="flexRadioDefault" id="declineReason5">
  <label class="form-check-label" for="declineReason5">
    Расчленёнка
  </label>
</div>
<div class="form-check">
  <input name="decline'.$p['id'].'" value="6" class="form-check-input" type="radio" name="flexRadioDefault" id="declineReason6">
  <label class="form-check-label" for="declineReason6">
    Файл сломан
  </label>
</div>

<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">Дополнительный комментарий</label>
  <textarea class="form-control" id="exampleFormControlTextarea1" name="comment" rows="3"></textarea>
</div>
      </div>
      <div class="modal-footer">
        <a type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</a>'; ?>
        <a href="#" onclick="photoAction(<?=$p['id']?>, document.querySelector(`input[name='decline<?=$p['id']?>']:checked`).value, 2); return false;" data-bs-dismiss="modal" class="btn btn-primary">Сохранить</a>
        <?php echo '
      </div>
    </div>
  </div>
</div>
                                
                                ';
                               }
                               ?>
                             

                            </tbody>
                        </table>
                    </div></div>
                    <div id="full__block" class="<?= $activeTab === 'full' ? 'active__block' : '' ?>"<?= $activeTab !== 'full' ? ' style="display:none"' : '' ?>>
                    <div class="p20w" style="display:block">
                        <form class="admin-photo-toolbar" method="get" action="/admin">
                            <input type="hidden" name="type" value="Photo">
                            <input type="hidden" name="full_tab" value="1">
                            <div class="toolbar-field">
                                <label for="full_status">Статус</label>
                                <select name="full_status" id="full_status">
                                    <option value="all"<?= $fullListQuery['full_status'] === 'all' ? ' selected' : '' ?>>Все</option>
                                    <option value="pending"<?= $fullListQuery['full_status'] === 'pending' ? ' selected' : '' ?>>Ожидают модерации</option>
                                    <option value="published"<?= $fullListQuery['full_status'] === 'published' ? ' selected' : '' ?>>Опубликовано</option>
                                    <option value="declined"<?= $fullListQuery['full_status'] === 'declined' ? ' selected' : '' ?>>Отклонено</option>
                                </select>
                            </div>
                            <div class="toolbar-field">
                                <label for="full_sort">Сортировка</label>
                                <select name="full_sort" id="full_sort">
                                    <?php foreach ($fullListSortOptions as $sortKey => $sortLabel) { ?>
                                        <option value="<?= htmlspecialchars($sortKey) ?>"<?= $fullListQuery['full_sort'] === $sortKey ? ' selected' : '' ?>><?= htmlspecialchars($sortLabel) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="toolbar-field">
                                <label for="full_order">Порядок</label>
                                <select name="full_order" id="full_order">
                                    <option value="desc"<?= $fullListQuery['full_order'] === 'desc' ? ' selected' : '' ?>>По убыванию</option>
                                    <option value="asc"<?= $fullListQuery['full_order'] === 'asc' ? ' selected' : '' ?>>По возрастанию</option>
                                </select>
                            </div>
                            <div class="toolbar-field">
                                <label for="full_per">На странице</label>
                                <select name="full_per" id="full_per">
                                    <?php foreach ($fullListPerPageOptions as $perPageOption) { ?>
                                        <option value="<?= $perPageOption ?>"<?= $fullListQuery['full_per'] === $perPageOption ? ' selected' : '' ?>><?= $perPageOption ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="toolbar-field toolbar-search">
                                <label for="full_q">Поиск (ID или подпись)</label>
                                <input type="text" name="full_q" id="full_q" value="<?= htmlspecialchars($fullListQuery['full_q']) ?>" placeholder="Например, 42 или Москва">
                            </div>
                            <div class="toolbar-field">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Применить</button>
                            </div>
                        </form>

                        <p class="sm text-muted admin-photo-summary">
                            Показано <b><?= $fullListFrom ?>–<?= $fullListTo ?></b> из <b><?= $fullListTotal ?></b>.
                            Для приёма/отклонения из очереди используйте вкладку «Ожидают модерации».
                        </p>

                        <table class="table">
                            <tbody>
                                <tr>
                                    <th width="70">ID</th>
                                    <th width="100">Изображение</th>
                                    <th width="40%">Информация</th>
                                    <th width="16%">Статус</th>
                                    <th>Действия</th>
                                </tr>
                                <?php
                                if ($fullListPhotos === []) {
                                    echo '<tr><td colspan="5" class="sm text-muted">Фотографии не найдены.</td></tr>';
                                }
                                foreach ($fullListPhotos as $p) {
                                    [$status, $color] = $photoStatusLabel((int) $p['moderated']);
                                    $author = new User((int) $p['user_id']);
                                    $photoId = (int) $p['id'];
                                    echo '<tr id="pht-full' . $photoId . '" class="' . $color . '">';
                                    echo '<td><a href="/photo/' . $photoId . '/" target="_blank">#' . $photoId . '</a></td>';
                                    echo '<td><a href="/photo/' . $photoId . '/" target="_blank" class="prw"><img src="' . htmlspecialchars((string) $p['photourl']) . '" class="f" alt=""></a></td>';
                                    echo '<td><p><span style="word-spacing:-1px"><b>' . htmlspecialchars((string) $p['place']) . '</b></span></p>';
                                    echo '<p class="sm"><b>' . Date::zmdate((int) $p['posted_at']) . '</b>';
                                    if ((int) $p['timeupload'] > 0) {
                                        echo '<br>Опубликовано: ' . Date::zmdate((int) $p['timeupload']);
                                    }
                                    echo '<br>Автор: <a href="/author/' . (int) $p['user_id'] . '/">' . htmlspecialchars((string) $author->i('username')) . '</a></p></td>';
                                    echo '<td>' . htmlspecialchars($status) . '</td><td class="c">';
                                    echo '<a href="/lk/editimage?id=' . $photoId . '" class="btn btn-sm btn-primary me-1">Редактировать</a>';
                                    echo '<a href="/photo/' . $photoId . '/" target="_blank" class="btn btn-sm btn-outline-primary me-1">Открыть</a>';
                                    if ((int) $p['moderated'] === 0) {
                                        echo '<a href="' . htmlspecialchars($photoAdminUrl(['full_tab' => null, 'full_page' => null, 'full_per' => null, 'full_sort' => null, 'full_order' => null, 'full_status' => null, 'full_q' => null])) . '" class="btn btn-sm btn-outline-secondary">К модерации</a>';
                                    }
                                    echo '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>

                        <?php if ($fullListPages > 1) { ?>
                        <div class="admin-photo-pagination">
                            <?php if ($fullListQuery['full_page'] > 1) { ?>
                                <a class="page-link" href="<?= htmlspecialchars($photoAdminUrl(['full_page' => $fullListQuery['full_page'] - 1])) ?>">‹</a>
                            <?php } else { ?>
                                <span class="page-link is-disabled">‹</span>
                            <?php } ?>

                            <?php foreach ($fullListPageNumbers as $pageNumber) {
                                if ($pageNumber === '…') {
                                    echo '<span class="page-link is-disabled">…</span>';
                                    continue;
                                }
                                $isActive = (int) $pageNumber === $fullListQuery['full_page'];
                                echo '<a class="page-link' . ($isActive ? ' is-active' : '') . '" href="' . htmlspecialchars($photoAdminUrl(['full_page' => (int) $pageNumber])) . '">' . (int) $pageNumber . '</a>';
                            } ?>

                            <?php if ($fullListQuery['full_page'] < $fullListPages) { ?>
                                <a class="page-link" href="<?= htmlspecialchars($photoAdminUrl(['full_page' => $fullListQuery['full_page'] + 1])) ?>">›</a>
                            <?php } else { ?>
                                <span class="page-link is-disabled">›</span>
                            <?php } ?>

                            <span class="sm text-muted" style="margin-left:8px">Страница <?= $fullListQuery['full_page'] ?> из <?= $fullListPages ?></span>
                        </div>
                        <?php } ?>
                    </div>
                    </div>
<script>
function photoAction(photo_id, decline_reason, iRate, kRate, mod) {
   $.ajax({
                type: "GET",
                url: '/api/admin/images/setvisibility?id='+photo_id+'&mod='+mod+'&reason='+decline_reason+'&irate='+iRate+'&krate='+kRate,
                data: $(this).serialize(),
                success: function(response) {
                $('#pht'+photo_id).remove();
                        Notify.noty('success', 'OK!');
                        //$("#result").html("<div class='alert alert-successnew container mt-5' role='alert'>Успешный вход!</div>");
                      
                        
                    }
                
            });
}
</script>
           