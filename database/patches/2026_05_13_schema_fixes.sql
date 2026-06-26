-- =====================================================================
-- Schema patch: 2026-05-13
-- Brings live tables in line with model `$fillable` declarations that
-- were never written into migrations.
--
-- Safe to run multiple times — each ALTER is guarded by an
-- information_schema check, so re-running is a no-op.
--
-- Usage:
--   mysql -uroot -p<password> <database> < database/patches/2026_05_13_schema_fixes.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- website_contacts: add `subject` and `remark`
--   Referenced by App\Models\WebsiteContact $fillable
--   Used in App\Livewire\SuperAdmin\Dashboard (whereNull('remark'))
-- ---------------------------------------------------------------------

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'website_contacts'
               AND COLUMN_NAME = 'subject');
SET @sql := IF(@col = 0,
    'ALTER TABLE `website_contacts` ADD COLUMN `subject` VARCHAR(255) NULL AFTER `school_name`',
    'SELECT ''website_contacts.subject already exists, skipping'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'website_contacts'
               AND COLUMN_NAME = 'remark');
SET @sql := IF(@col = 0,
    'ALTER TABLE `website_contacts` ADD COLUMN `remark` TEXT NULL AFTER `description`',
    'SELECT ''website_contacts.remark already exists, skipping'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- organizations: add `affiliation_no` and `udise_number`
--   Referenced by App\Livewire\SuperAdmin\Schools when creating a school
-- ---------------------------------------------------------------------

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'organizations'
               AND COLUMN_NAME = 'affiliation_no');
SET @sql := IF(@col = 0,
    'ALTER TABLE `organizations` ADD COLUMN `affiliation_no` VARCHAR(255) NULL AFTER `school_code`',
    'SELECT ''organizations.affiliation_no already exists, skipping'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'organizations'
               AND COLUMN_NAME = 'udise_number');
SET @sql := IF(@col = 0,
    'ALTER TABLE `organizations` ADD COLUMN `udise_number` VARCHAR(255) NULL AFTER `affiliation_no`',
    'SELECT ''organizations.udise_number already exists, skipping'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
