-- ============================================================
-- Migration: users table from email model to ITS + phone model
-- ============================================================

USE `anjuman_ezzy`;

ALTER TABLE `users`
  ADD COLUMN `its_number` CHAR(8) NULL AFTER `id`,
  ADD COLUMN `phone` VARCHAR(20) NULL AFTER `name`;

-- Backfill placeholders for existing rows so constraints can be applied.
-- IMPORTANT: Replace placeholder values with real ITS numbers and phones.
UPDATE `users`
SET
  `its_number` = LPAD(`id`, 8, '0'),
  `phone` = '0000000000'
WHERE `its_number` IS NULL OR `phone` IS NULL;

UPDATE `users`
SET `its_number` = '12345678'
WHERE `role` = 'admin'
ORDER BY `id` ASC
LIMIT 1;

ALTER TABLE `users`
  MODIFY COLUMN `its_number` CHAR(8) NOT NULL,
  MODIFY COLUMN `phone` VARCHAR(20) NOT NULL,
  ADD UNIQUE KEY `uq_users_its_number` (`its_number`),
  ADD KEY `idx_users_phone` (`phone`);

-- Drop old email uniqueness and column after migration.
ALTER TABLE `users`
  DROP INDEX `uq_users_email`;

ALTER TABLE `users`
  DROP COLUMN `email`;
