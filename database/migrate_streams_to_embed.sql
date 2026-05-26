-- ============================================================
-- Migration: streams table to embedded YouTube URL model
-- ============================================================

USE `anjuman_ezzy`;

ALTER TABLE `streams`
  ADD COLUMN `youtube_url` VARCHAR(500) NULL AFTER `title`,
  ADD COLUMN `youtube_video_id` VARCHAR(20) NULL AFTER `youtube_url`,
  ADD COLUMN `created_by` INT(11) UNSIGNED NULL AFTER `status`;

UPDATE `streams`
SET
  `youtube_url` = COALESCE(`youtube_url`, ''),
  `youtube_video_id` = COALESCE(`youtube_video_id`, ''),
  `created_by` = COALESCE(`created_by`, 1)
WHERE `youtube_url` IS NULL
   OR `youtube_video_id` IS NULL
   OR `created_by` IS NULL;

ALTER TABLE `streams`
  MODIFY COLUMN `youtube_url` VARCHAR(500) NOT NULL,
  MODIFY COLUMN `youtube_video_id` VARCHAR(20) NOT NULL,
  MODIFY COLUMN `created_by` INT(11) UNSIGNED NOT NULL;
