-- =====================================================================
--  Ev Muhasebe · Göç (migration) #4 · 2026-06
--  Çoklu dil: kullanıcı dil tercihi sütunu.
--  Tekrar çalıştırmaya dayanıklıdır.
-- =====================================================================

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='lang');
SET @sql := IF(@col=0,
  "ALTER TABLE `users` ADD COLUMN `lang` VARCHAR(5) NOT NULL DEFAULT 'tr' AFTER `theme`",
  'SELECT "users.lang zaten var" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
