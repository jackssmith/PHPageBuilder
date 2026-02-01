SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- Pages Table
-- =====================================================
CREATE TABLE `pages` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Internal page name',
  `slug` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL friendly identifier',
  `layout` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` JSON DEFAULT NULL COMMENT 'Page builder / layout data',
  `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pages_slug` (`slug`),
  KEY `idx_pages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Page Translations Table
-- =====================================================
CREATE TABLE `page_translations` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `page_id` BIGINT(20) NOT NULL,
  `locale` VARCHAR(10) NOT NULL COMMENT 'e.g. en, fr, de',
  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_page_locale` (`page_id`, `locale`),
  UNIQUE KEY `uniq_locale_route` (`locale`, `route`),
  KEY `idx_page_translations_page_id` (`page_id`),
  CONSTRAINT `fk_page_translations_page`
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Uploads Table
-- =====================================================
CREATE TABLE `uploads` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `public_id` VARCHAR(50) NOT NULL,
  `original_file` VARCHAR(512) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `server_file` VARCHAR(512) NOT NULL,
  `file_size` BIGINT(20) NOT NULL COMMENT 'Size in bytes',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_uploads_public_id` (`public_id`),
  UNIQUE KEY `uniq_uploads_server_file` (`server_file`),
  KEY `idx_uploads_mime_type` (`mime_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Settings Table
-- =====================================================
CREATE TABLE `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting` VARCHAR(191) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `is_array` TINYINT(1) NOT NULL DEFAULT 0,
  `autoload` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Load setting on app boot',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_settings_key` (`setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Commit & Restore Charset
-- =====================================================
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
