-- Contest closure metadata (forced end / cancel with reason)

SET @s = (SELECT IF(
    EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'contests'
        AND COLUMN_NAME = 'closure_meta'
    ),
    'SELECT 1',
    'ALTER TABLE `contests` ADD COLUMN `closure_meta` mediumtext NULL AFTER `status`'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;