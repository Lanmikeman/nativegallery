<?php
namespace App\Models\Admin;
use \App\Services\{DB, Date, SiteNews};

class News {

    public $id;
    public $table;
    function __construct(int $id) {
        $this->id = $id;
        $result = DB::query("SELECT * FROM news WHERE id=:id", [':id' => $this->id]);
        if (!empty($result)) {
            $this->table = (object) $result[0];
        } else {
            $this->table = (object) [];
        }
    }
    public function i($key) {
        return $this->table->$key ?? null;
    }
    public function view() {
        echo '<div class="card mb-3"><div class="card-body">';
        echo '<i>' . Date::zmdate((int) ($this->table->time ?? 0)) . '</i>';
        if (!empty($this->table->edited_at)) {
            echo '<div class="sm text-muted" style="margin-top:4px">Отредактировано '
                . htmlspecialchars(Date::zmdate((int) $this->table->edited_at))
                . ' — ' . htmlspecialchars(SiteNews::editorName((int) ($this->table->edited_by ?? 0)))
                . '</div>';
        }
        echo '<div class="mt-2">' . ($this->table->body ?? '') . '</div>';
        echo '<div class="mt-3">';
        $bodyJson = htmlspecialchars(json_encode((string) ($this->table->body ?? ''), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        echo '<a class="btn btn-secondary me-2 edit-news-btn" href="#" data-id="' . (int) $this->id . '" data-news-body="' . $bodyJson . '">Редактировать</a>';
        echo '<a class="btn btn-danger" href="#" onclick="deleteNews(' . $this->id . '); return false;">Удалить</a>';
        echo '</div></div></div>';
    }
}