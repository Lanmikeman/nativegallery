-- Музыкальная библиотека: загрузки, потоки, плейлисты

CREATE TABLE IF NOT EXISTS `audio_tracks` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `title` varchar(255) NOT NULL DEFAULT '',
    `artist` varchar(255) NOT NULL DEFAULT '',
    `source_type` enum('upload','url') NOT NULL DEFAULT 'upload',
    `src` varchar(1024) NOT NULL,
    `duration` int NOT NULL DEFAULT 0,
    `file_size` int NOT NULL DEFAULT 0,
    `created_at` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `audio_tracks_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audio_streams` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `title` varchar(255) NOT NULL DEFAULT '',
    `url` varchar(1024) NOT NULL,
    `created_at` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `audio_streams_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audio_playlists` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `title` varchar(255) NOT NULL DEFAULT '',
    `created_at` int NOT NULL DEFAULT 0,
    `updated_at` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `audio_playlists_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audio_playlist_items` (
    `id` int NOT NULL AUTO_INCREMENT,
    `playlist_id` int NOT NULL,
    `item_type` enum('track','stream','url') NOT NULL DEFAULT 'track',
    `item_id` int NOT NULL DEFAULT 0,
    `url` varchar(1024) NOT NULL DEFAULT '',
    `title` varchar(255) NOT NULL DEFAULT '',
    `sort_order` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `audio_playlist_items_pl` (`playlist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;