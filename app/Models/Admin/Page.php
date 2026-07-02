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

    private function updatedLabel(): string
    {
        if (!empty($this->table->updated_at)) {
            return 'Обновлено '
                . Date::formatDate((int) $this->table->updated_at)
                . ' — '
                . SitePage::editorName((int) ($this->table->updated_by ?? 0));
        }

        return 'Создано ' . Date::formatDate((int) ($this->table->created_at ?? 0));
    }

    private function bodySizeLabel(): string
    {
        $plain = trim(strip_tags((string) ($this->table->body ?? '')));
        $len = mb_strlen($plain);
        if ($len === 0) {
            return 'пусто';
        }
        return $len . ' симв.';
    }

    public function view(): void
    {
        $this->viewRow();
    }

    public function viewRow(): void
    {
        $title = htmlspecialchars((string) ($this->table->title ?? 'Без названия'));
        $pageId = (int) $this->id;
        $updated = htmlspecialchars($this->updatedLabel());
        $size = htmlspecialchars($this->bodySizeLabel());

        echo '<tr id="page' . $pageId . '">';
        echo '<td class="admin-pages-table__id"><b>' . $pageId . '</b></td>';
        echo '<td class="admin-pages-table__title">' . $title . '</td>';
        echo '<td><a href="/page/' . $pageId . '" target="_blank" rel="noopener">/page/' . $pageId . '</a></td>';
        echo '<td class="admin-pages-table__meta sm text-muted">' . $updated . '<br><span class="admin-pages-table__size">' . $size . '</span></td>';
        echo '<td class="admin-pages-table__actions text-nowrap">';
        echo '<a class="btn btn-sm btn-secondary edit-page-btn" href="#" data-id="' . $pageId . '">Редактировать</a> ';
        echo '<a class="btn btn-sm btn-outline-primary" href="/page/' . $pageId . '" target="_blank" rel="noopener">Открыть</a> ';
        echo '<a class="btn btn-sm btn-danger" href="#" onclick="deletePage(' . $pageId . '); return false;">Удалить</a>';
        echo '</td>';
        echo '</tr>';
    }
}