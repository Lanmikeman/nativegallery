-- Optional photo reference on entity change requests

SET @s = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'entities_requests'
        AND COLUMN_NAME = 'photo_id'
    ),
    'SELECT 1',
    'ALTER TABLE `entities_requests` ADD COLUMN `photo_id` int(10) NOT NULL DEFAULT 0 AFTER `data`'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;