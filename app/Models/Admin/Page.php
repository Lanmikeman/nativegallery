<?php

namespace App\Models\Admin;

use App\Services\{DB, Date, SitePage};

class Page
{
    public int $id;

    /** @var object */
    public $table;

    public function __construct(int $id)
    {
        $this->id = $id;
        $result = DB::query('SELECT * FROM pages WHERE id = :id', [':id' => $this->id]);
        $this->table = !empty($result) ? (object) $result[0] : (object) [];
    }

    public function i(string $key): mixed
    {
        return $this->table->$key ?? null;
    }

    public function view(): void
    {
        $title = htmlspecialchars((string) ($this->table->title ?? 'Без названия'));
        $pageId = (int) $this->id;
        $titleJson = htmlspecialchars(json_encode((string) ($this->table->title ?? ''), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $bodyJson = htmlspecialchars(json_encode((string) ($this->table->body ?? ''), JSON_UNESCAPED_UNICODE), ENT_QUOTES);

        echo '<div class="card mb-3" id="page' . $pageId . '"><div class="card-body">';
        echo '<div class="d-flex justify-content-between align-items-start gap-3">';
        echo '<div><b>#' . $pageId . '</b> — ' . $title;
        echo '<div class="sm text-muted mt-1"><a href="/page/' . $pageId . '" target="_blank">/page/' . $pageId . '</a></div>';
        if (!empty($this->table->updated_at)) {
            echo '<div class="sm text-muted" style="margin-top:4px">Обновлено '
                . htmlspecialchars(Date::formatDate((int) $this->table->updated_at))
                . ' — ' . htmlspecialchars(SitePage::editorName((int) ($this->table->updated_by ?? 0)))
                . '</div>';
        } else {
            echo '<div class="sm text-muted" style="margin-top:4px">Создано '
                . htmlspecialchars(Date::formatDate((int) ($this->table->created_at ?? 0)))
                . '</div>';
        }
        echo '</div>';
        echo '<div class="text-nowrap">';
        echo '<a class="btn btn-secondary btn-sm me-2 edit-page-btn" href="#" data-id="' . $pageId . '" data-page-title="' . $titleJson . '" data-page-body="' . $bodyJson . '">Редактировать</a>';
        echo '<a class="btn btn-danger btn-sm" href="#" onclick="deletePage(' . $pageId . '); return false;">Удалить</a>';
        echo '</div></div>';
        echo '<div class="mt-3 break-links">' . ($this->table->body ?? '') . '</div>';
        echo '</div></div>';
    }
}