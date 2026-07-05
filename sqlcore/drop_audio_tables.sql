-- Удаление таблиц музыки (sql_0010.sql + sql_0011.sql)
--   mysql -u USER -p DATABASE < sqlcore/drop_audio_tables.sql

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audio_playlist_items`;
DROP TABLE IF EXISTS `audio_playlists`;
DROP TABLE IF EXISTS `audio_streams`;
DROP TABLE IF EXISTS `audio_tracks`;
DROP TABLE IF EXISTS `audio_global_streams`;

SET FOREIGN_KEY_CHECKS = 1;