-- =====================================================
-- Database Configuration
-- =====================================================

SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';
SET SESSION time_zone = '+00:00';

START TRANSACTION;


-- =====================================================
-- 1. Pages
-- =====================================================

CREATE TABLE pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(255) NOT NULL COMMENT 'Internal page name',
    slug VARCHAR(255) NOT NULL COMMENT 'Unique URL-friendly identifier',

    layout VARCHAR(100) NOT NULL COMMENT 'Page layout identifier',
    data JSON DEFAULT NULL COMMENT 'Page builder and layout data',

    status ENUM('draft', 'published', 'archived')
        NOT NULL DEFAULT 'draft',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_pages_slug (slug),

    KEY idx_pages_status_deleted (status, deleted_at),
    KEY idx_pages_created_at (created_at)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 2. Page Translations
-- =====================================================

CREATE TABLE page_translations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    page_id BIGINT UNSIGNED NOT NULL,

    locale VARCHAR(10) NOT NULL
        COMMENT 'Language and regional identifier (en, fr, ar, en-US)',

    title VARCHAR(255) NOT NULL,
    meta_title VARCHAR(128) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,

    route VARCHAR(255) NOT NULL
        COMMENT 'Localized page route',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_page_translations_page_locale (page_id, locale),
    UNIQUE KEY uq_page_translations_locale_route (locale, route),

    KEY idx_page_translations_locale (locale),

    CONSTRAINT fk_page_translations_page
        FOREIGN KEY (page_id)
        REFERENCES pages (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 3. Uploads
-- =====================================================

CREATE TABLE uploads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    public_id VARCHAR(64) NOT NULL
        COMMENT 'Public identifier exposed to the application',

    original_file VARCHAR(512) NOT NULL
        COMMENT 'Original uploaded filename',

    server_file VARCHAR(512) NOT NULL
        COMMENT 'Stored filename or storage key',

    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL
        COMMENT 'File size in bytes',

    file_extension VARCHAR(20) DEFAULT NULL,

    storage_disk VARCHAR(50) NOT NULL DEFAULT 'local'
        COMMENT 'Storage provider or disk identifier',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_uploads_public_id (public_id),
    UNIQUE KEY uq_uploads_server_file (server_file),

    KEY idx_uploads_mime_type (mime_type),
    KEY idx_uploads_created_at (created_at),
    KEY idx_uploads_storage_disk (storage_disk),

    CONSTRAINT chk_uploads_file_size
        CHECK (file_size >= 0)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 4. Application Settings
-- =====================================================

CREATE TABLE settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    `key` VARCHAR(120) NOT NULL,
    `value` MEDIUMTEXT NOT NULL,

    value_type ENUM('string', 'number', 'boolean', 'json')
        NOT NULL DEFAULT 'string'
        COMMENT 'Defines how the setting value should be interpreted',

    autoload BOOLEAN NOT NULL DEFAULT TRUE
        COMMENT 'Whether to load the setting during application boot',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_settings_key (`key`),
    KEY idx_settings_autoload (autoload)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- Commit Transaction
-- =====================================================

COMMIT;
