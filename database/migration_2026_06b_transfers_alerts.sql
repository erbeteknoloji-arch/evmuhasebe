-- =====================================================================
--  Ev Muhasebe · Göç (migration) #2 · 2026-06
--  Hesaplar arası transfer, etiketler, planlı ödeme otomatik işleme,
--  bütçe aşım uyarıları.
--  Tekrar çalıştırmaya dayanıklıdır (var olan sütun/tablolar atlanır).
-- =====================================================================

-- transactions.transfer_id  (transfer çiftini bağlayan jeton)
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='transactions' AND COLUMN_NAME='transfer_id');
SET @sql := IF(@col=0,
  'ALTER TABLE `transactions` ADD COLUMN `transfer_id` VARCHAR(40) NULL DEFAULT NULL AFTER `source`',
  'SELECT "transactions.transfer_id zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- transactions.tags  (boşlukla/virgülle ayrılmış etiketler)
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='transactions' AND COLUMN_NAME='tags');
SET @sql := IF(@col=0,
  'ALTER TABLE `transactions` ADD COLUMN `tags` VARCHAR(255) NULL DEFAULT NULL AFTER `description`',
  'SELECT "transactions.tags zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- transactions.transfer_id için indeks (çift bulma)
SET @idx := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='transactions' AND INDEX_NAME='idx_transfer_id');
SET @sql := IF(@idx=0,
  'ALTER TABLE `transactions` ADD INDEX `idx_transfer_id` (`transfer_id`)',
  'SELECT "idx_transfer_id zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- scheduled_items.auto_post  (vadesinde otomatik işle)
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='scheduled_items' AND COLUMN_NAME='auto_post');
SET @sql := IF(@col=0,
  'ALTER TABLE `scheduled_items` ADD COLUMN `auto_post` TINYINT(1) NOT NULL DEFAULT 0 AFTER `recurrence`',
  'SELECT "scheduled_items.auto_post zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- budget_alerts  (bir bütçe için dönem+seviye başına tek uyarı/e-posta)
CREATE TABLE IF NOT EXISTS `budget_alerts` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `household_id` INT(10) UNSIGNED NOT NULL,
  `budget_id` INT(10) UNSIGNED NOT NULL,
  `period` VARCHAR(7) NOT NULL,      -- YYYY-MM
  `level` TINYINT UNSIGNED NOT NULL, -- 80 veya 100
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_budget_period_level` (`budget_id`, `period`, `level`),
  KEY `idx_household` (`household_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
