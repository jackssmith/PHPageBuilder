-- =====================================================
-- Global Settings
-- =====================================================
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';
SET time_zone = '+00:00';

START TRANSACTION;

-- =====================================================
-- Pages Table
-- =====================================================
CREATE TABLE pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  name VARCHAR(255) NOT NULL COMMENT 'Internal page name',
  slug VARCHAR(255) NOT NULL COMMENT 'URL-friendly identifier',

  layout VARCHAR(100) NOT NULL,
  data JSON NULL COMMENT 'Page builder / layout data',

  status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL DEFAULT NULL,

  CONSTRAINT uq_pages_slug UNIQUE (slug),
  INDEX idx_pages_status (status),
  INDEX idx_pages_deleted_at (deleted_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Page Translations Table
-- =====================================================
CREATE TABLE page_translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  page_id BIGINT UNSIGNED NOT NULL,
  locale VARCHAR(10) NOT NULL COMMENT 'ISO locale (e.g., en, fr, ar)',

  title VARCHAR(255) NOT NULL,
  meta_title VARCHAR(128) NULL,
  meta_description TEXT NULL,

  route VARCHAR(128) NOT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT uq_page_locale UNIQUE (page_id, locale),
  CONSTRAINT uq_locale_route UNIQUE (locale, route),

  INDEX idx_page_translations_page_id (page_id),
  INDEX idx_page_translations_locale (locale),

  CONSTRAINT fk_page_translations_page
    FOREIGN KEY (page_id)
    REFERENCES pages(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Uploads Table
-- =====================================================
CREATE TABLE uploads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  public_id VARCHAR(64) NOT NULL,
  original_file VARCHAR(512) NOT NULL,
  server_file VARCHAR(512) NOT NULL,

  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL COMMENT 'Size in bytes',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT uq_uploads_public_id UNIQUE (public_id),
  CONSTRAINT uq_uploads_server_file UNIQUE (server_file),

  INDEX idx_uploads_mime_type (mime_type),
  INDEX idx_uploads_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Settings Table
-- =====================================================
CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  `key` VARCHAR(120) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,

  is_array BOOLEAN NOT NULL DEFAULT FALSE,
  autoload BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Load setting on app boot',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT uq_settings_key UNIQUE (`key`),
  INDEX idx_settings_autoload (autoload)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Commit
-- =====================================================
COMMIT;
