-- Add missing columns to experts table
ALTER TABLE `experts` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `expertise_level`;
ALTER TABLE `experts` ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;
ALTER TABLE `experts` ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_verified`;
ALTER TABLE `experts` ADD COLUMN `avatar_url` VARCHAR(255) AFTER `is_admin`;
ALTER TABLE `experts` ADD COLUMN `last_login` DATETIME AFTER `avatar_url`;
ALTER TABLE `experts` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `last_login`;
ALTER TABLE `experts` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add indexes for better query performance
ALTER TABLE `experts` ADD INDEX `idx_username` (`username`);
ALTER TABLE `experts` ADD INDEX `idx_email` (`email`);
ALTER TABLE `experts` ADD INDEX `idx_is_active` (`is_active`);
