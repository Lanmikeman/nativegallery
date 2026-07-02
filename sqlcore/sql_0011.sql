-- Общие радиостанции сайта (добавляются администратором)

CREATE TABLE IF NOT EXISTS `audio_global_streams` (
    `id` int NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL DEFAULT '',
    `url` varchar(1024) NOT NULL,
    `sort_order` int NOT NULL DEFAULT 0,
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `audio_global_streams_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;