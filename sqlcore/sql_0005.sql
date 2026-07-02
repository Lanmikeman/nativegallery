-- Chronology (news.php) and site links (links.php)

CREATE TABLE IF NOT EXISTS `chronology` (
  `id` int NOT NULL AUTO_INCREMENT,
  `city` varchar(255) NOT NULL DEFAULT '',
  `geodb_id` int NOT NULL DEFAULT 0,
  `transit_type` int NOT NULL DEFAULT 0,
  `time` int NOT NULL,
  `body` mediumtext NOT NULL,
  `main` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `main` (`main`),
  KEY `geodb_id` (`geodb_id`),
  KEY `transit_type` (`transit_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `site_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `sort` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;