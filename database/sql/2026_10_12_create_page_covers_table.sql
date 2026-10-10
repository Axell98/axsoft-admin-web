-- Portadas de las páginas internas (MySQL / MariaDB).
-- Equivale a la migración 2026_10_12_000000_create_page_covers_table.php,
-- para crear la tabla a mano cuando no se puede ejecutar `php artisan migrate`.
-- Se puede ejecutar más de una vez sin duplicar nada.

CREATE TABLE IF NOT EXISTS `page_covers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- Clave de la página interna (ver config/covers.php). Una portada por página.
    `page` VARCHAR(60) NOT NULL,
    `title` VARCHAR(150) NULL,
    -- file = imagen del gestor de archivos, image_url = imagen por link.
    `image_type` VARCHAR(20) NULL,
    `media_file_id` BIGINT UNSIGNED NULL,
    `external_url` VARCHAR(2048) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `page_covers_page_unique` (`page`),
    CONSTRAINT `page_covers_media_file_id_foreign`
        FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registra la migración como ejecutada, para que `php artisan migrate`
-- no intente crear la tabla otra vez cuando se regularice.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT `new`.`migration`, `new`.`batch`
FROM (
    SELECT
        '2026_10_12_000000_create_page_covers_table' AS `migration`,
        (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`) AS `batch`
) AS `new`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migrations`.`migration` = `new`.`migration`
);
