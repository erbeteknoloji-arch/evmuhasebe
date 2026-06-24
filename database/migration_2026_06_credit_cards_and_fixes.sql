-- =====================================================================
--  Ev Muhasebe · Göç (migration) betiği
--  Tarih: 2026-06 · Kredi Kartı Yönetimi ve ilgili iyileştirmeler
-- ---------------------------------------------------------------------
--  Bu betik mevcut veritabanına UYGULANABİLİR (idempotent olacak şekilde
--  yazılmıştır; tekrar çalıştırılırsa zaten var olan sütun/tablolar
--  atlanır). MySQL/MariaDB içindir.
--
--  Çalıştırma:
--    mysql -u KULLANICI -p VERITABANI < migration_2026_06_credit_cards_and_fixes.sql
--  veya phpMyAdmin > İçe Aktar.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) accounts: Kredi kartı alanları
--    credit_limit      : Kart limiti (NULL = limit yok / kredi kartı değil)
--    statement_day      : Hesap kesim günü (ayın günü, 1-31)
--    due_day            : Son ödeme günü (ayın günü, 1-31)
--    min_payment_pct    : Asgari ödeme yüzdesi (varsayılan %20)
-- ---------------------------------------------------------------------

-- accounts.credit_limit
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounts' AND COLUMN_NAME = 'credit_limit');
SET @sql := IF(@col = 0,
  'ALTER TABLE `accounts` ADD COLUMN `credit_limit` DECIMAL(14,2) NULL DEFAULT NULL AFTER `opening_balance`',
  'SELECT "accounts.credit_limit zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- accounts.statement_day
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounts' AND COLUMN_NAME = 'statement_day');
SET @sql := IF(@col = 0,
  'ALTER TABLE `accounts` ADD COLUMN `statement_day` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `credit_limit`',
  'SELECT "accounts.statement_day zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- accounts.due_day
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounts' AND COLUMN_NAME = 'due_day');
SET @sql := IF(@col = 0,
  'ALTER TABLE `accounts` ADD COLUMN `due_day` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `statement_day`',
  'SELECT "accounts.due_day zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- accounts.min_payment_pct
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounts' AND COLUMN_NAME = 'min_payment_pct');
SET @sql := IF(@col = 0,
  'ALTER TABLE `accounts` ADD COLUMN `min_payment_pct` DECIMAL(5,2) NOT NULL DEFAULT 20.00 AFTER `due_day`',
  'SELECT "accounts.min_payment_pct zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------
-- 2) cc_statements: Otomatik üretilen kredi kartı ekstreleri
--    Her hesap kesim döneminde bir kayıt; tekrar üretmeyi engeller.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cc_statements` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `household_id` INT(10) UNSIGNED NOT NULL,
  `account_id` INT(10) UNSIGNED NOT NULL,
  `period_start` DATE NULL DEFAULT NULL,
  `period_end` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `statement_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `min_payment` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `scheduled_item_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_account_period` (`account_id`, `period_end`),
  KEY `idx_household` (`household_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
