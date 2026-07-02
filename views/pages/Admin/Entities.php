<?php

use \App\Services\{Auth, DB, Date};
use \App\Models\User;


$user = new \App\Models\User(Auth::userid());

if (!isset($_GET['type']) || $_GET['type'] != 'Photo') {
    if ($user->i('admin') === 2) {
        header('Location: ?type=Photo');
    }
}

?>
<h1><b>Сущности</b></h1>
        <a href="?type=EntityCreate" class="btn btn-primary">Создать</a>
        <div class="p20w" style="display:block">
            <table class="table">
                <tbody>
                    <tr>
                        <th width="100">ID</th>
                        <th width="50%">Название</th>
                        <th>Действия</th>
                    </tr>

                    <?php
                    $photos = DB::query('SELECT * FROM entities ORDER BY id DESC');
                    foreach ($photos as $p) {
                        $color = '';

                        echo ' <tr id="pht' . $p['id'] . '" class="' . $color . '">
                                    <td>
                                      '.$p['id'].'
                                    </td>
                                    <td>
                                       '.$p['title'].'
                                       
                                    </td>
                                    <td class="c">
                                   ';

                            echo '<a href="/admin?type=EntityEdit&id=' . $p['id'] . '" class="btn btn-primary">Редактировать</a>
                                    <a data-bs-toggle="modal" data-bs-target="#declinePhotoModal' . $p['id'] . '" href="#" class="btn btn-danger">Удалить</a>
                      
                                    </td>';
                     
                        echo '
                                </tr>
                                
                          
                                
                                ';
                    }
                    ?>


                </tbody>
            </table>
        </div><br>