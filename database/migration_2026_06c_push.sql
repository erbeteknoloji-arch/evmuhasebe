-- =====================================================================
--  Ev Muhasebe · Göç (migration) #3 · 2026-06
--  Web Push (tarayıcı/mobil bildirim) abonelikleri.
--  Tekrar çalıştırmaya dayanıklıdır.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `endpoint` VARCHAR(500) NOT NULL,
  `p256dh` VARCHAR(255) NOT NULL,
  `auth` VARCHAR(255) NOT NULL,
  `ua` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_endpoint` (`endpoint`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- İsteğe bağlı: kullanıcı başına push tercihi (varsayılan açık).
-- users.notify_push (abone olduysa zaten açık sayılır; bu sütun
-- kullanıcının push'u tamamen kapatmasına olanak verir).
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='notify_push');
SET @sql := IF(@col=0,
  'ALTER TABLE `users` ADD COLUMN `notify_push` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notify_goals`',
  'SELECT "users.notify_push zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
