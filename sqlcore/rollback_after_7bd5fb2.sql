-- Откат схемы, добавленной после коммита 7bd5fb2 (sql_0012.sql + sql_0013.sql)
-- Запуск на сервере:
--   mysql -u USER -p DATABASE < sqlcore/rollback_after_7bd5fb2.sql

SET FOREIGN_KEY_CHECKS = 0;

-- sql_0013.sql — личные сообщения
DROP TABLE IF EXISTS `message_entries`;
DROP TABLE IF EXISTS `message_chat_members`;
DROP TABLE IF EXISTS `message_chats`;

-- sql_0012.sql — социальная база
DROP TABLE IF EXISTS `social_mentions`;
DROP TABLE IF EXISTS `user_friendships`;
DROP TABLE IF EXISTS `user_identity_links`;
DROP TABLE IF EXISTS `social_outbound_queue`;

SET FOREIGN_KEY_CHECKS = 1;