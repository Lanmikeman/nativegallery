-- Редактирование новостей сайта: кто и когда правил запись
ALTER TABLE `news`
  ADD COLUMN `edited_at` int NOT NULL DEFAULT 0 AFTER `time`,
  ADD COLUMN `edited_by` int NOT NULL DEFAULT 0 AFTER `edited_at`;