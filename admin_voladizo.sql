-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 09-10-2026 a las 17:05:21
-- Versión del servidor: 8.0.46-cll-lve
-- Versión de PHP: 8.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `xkiwgtqp_admin_voladizo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banner_sliders`
--

CREATE TABLE `banner_sliders` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'slider',
  `screen_percentage` tinyint UNSIGNED NOT NULL DEFAULT '100',
  `show_arrows` tinyint(1) NOT NULL DEFAULT '1',
  `show_indicators` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `banner_sliders`
--

INSERT INTO `banner_sliders` (`id`, `name`, `display_type`, `screen_percentage`, `show_arrows`, `show_indicators`, `created_at`, `updated_at`) VALUES
(2, 'Principal', 'slider', 100, 1, 1, '2026-10-07 21:04:10', '2026-10-07 21:04:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banner_slides`
--

CREATE TABLE `banner_slides` (
  `id` bigint UNSIGNED NOT NULL,
  `banner_slider_id` bigint UNSIGNED NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `media_file_id` bigint UNSIGNED DEFAULT NULL,
  `external_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('voladizo-cache-356a192b7913b04c54574d18c28d46e6395428ab', 'i:2;', 1791389512),
('voladizo-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1791389512;', 1791389512);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_profiles`
--

CREATE TABLE `company_profiles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruc` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slogan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maps_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_hours` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_2` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiktok` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `behance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_title` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `company_profiles`
--

INSERT INTO `company_profiles` (`id`, `name`, `legal_name`, `ruc`, `slogan`, `address`, `maps_url`, `business_hours`, `website`, `email`, `email_2`, `phone`, `whatsapp`, `whatsapp_2`, `facebook`, `instagram`, `youtube`, `tiktok`, `linkedin`, `behance`, `description`, `meta_title`, `meta_description`, `logo_path`, `favicon_path`, `created_at`, `updated_at`) VALUES
(1, 'Voladizo', 'Voladizo', '20744530134', 'Diseño | Construcción', 'Av. Militar 2500, Lince / Lima, Peru', 'https://maps.app.goo.gl/9sGuUStQbt1NNL1f6', NULL, 'https://voladizo.pe', 'contacto@voladizo.com', NULL, '+51 999 449 230', '+51 999 449 230', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-05 19:57:45', '2026-10-07 21:11:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `media_files`
--

CREATE TABLE `media_files` (
  `id` bigint UNSIGNED NOT NULL,
  `media_folder_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `media_files`
--

INSERT INTO `media_files` (`id`, `media_folder_id`, `name`, `path`, `extension`, `mime_type`, `size`, `created_at`, `updated_at`) VALUES
(12, 7, 'planta_amazon.jpg', 'files/2026/10/SPiy6VTL0PPkFQmmvYDvKrEhVzRgRj0GfSohclFt.jpg', 'jpg', 'image/jpeg', 943558, '2026-10-07 21:10:53', '2026-10-07 21:10:53'),
(13, 7, 'slider2_voladizo.jpg', 'files/2026/10/Jhyf960N8SZoGGTGoVbdkAxWPLeA1r8f2NlLyvRJ.jpg', 'jpg', 'image/png', 2122337, '2026-10-07 21:10:56', '2026-10-07 21:10:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `media_folders`
--

CREATE TABLE `media_folders` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `media_folders`
--

INSERT INTO `media_folders` (`id`, `name`, `created_at`, `updated_at`) VALUES
(7, 'Banners', '2026-10-07 21:10:47', '2026-10-07 21:10:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_10_05_000000_create_company_profiles_table', 2),
(5, '2026_10_05_000001_add_extra_fields_to_company_profiles_table', 3),
(6, '2026_10_05_000002_create_media_tables', 4),
(7, '2026_10_06_000000_create_banner_tables', 5),
(8, '2026_10_06_000001_add_text_to_banner_slides_table', 6),
(9, '2026_10_07_000000_create_project_tables', 7),
(10, '2026_10_08_000000_create_project_categories_table', 8),
(11, '2026_10_09_000000_create_popup_tables', 9),
(12, '2026_10_10_000000_add_display_type_to_banner_sliders_table', 10),
(13, '2026_10_11_000000_create_testimonials_table', 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `popups`
--

CREATE TABLE `popups` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `is_visible` tinyint(1) NOT NULL DEFAULT '0',
  `show_header` tinyint(1) NOT NULL DEFAULT '0',
  `show_border` tinyint(1) NOT NULL DEFAULT '0',
  `link_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `popup_items`
--

CREATE TABLE `popup_items` (
  `id` bigint UNSIGNED NOT NULL,
  `popup_id` bigint UNSIGNED NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `media_file_id` bigint UNSIGNED DEFAULT NULL,
  `external_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `projects`
--

CREATE TABLE `projects` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(220) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_category_id` bigint UNSIGNED DEFAULT NULL,
  `execution_percentage` tinyint UNSIGNED DEFAULT NULL,
  `year` smallint UNSIGNED DEFAULT NULL,
  `video_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_media_file_id` bigint UNSIGNED DEFAULT NULL,
  `video_youtube_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_categories`
--

CREATE TABLE `project_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `project_categories`
--

INSERT INTO `project_categories` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Residencial', 'residencial', '2026-10-07 08:15:23', '2026-10-07 08:15:23'),
(2, 'Oficina', 'oficina', '2026-10-07 08:19:13', '2026-10-07 08:19:13'),
(3, 'Comercial', 'comercial', '2026-10-07 08:19:13', '2026-10-07 08:19:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_phases`
--

CREATE TABLE `project_phases` (
  `id` bigint UNSIGNED NOT NULL,
  `project_id` bigint UNSIGNED NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `media_file_id` bigint UNSIGNED DEFAULT NULL,
  `external_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `position` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0QrjgC7cs2NzDvdNXMdk94PKSJs0HJvUACNZQjGB', NULL, '104.253.228.190', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJZM1hrRFZUYjhZQzRtSFNCTGZxazBjcEdnS2ZuOFZuRjBVV1R1NEEzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791514890),
('2UW3hwj6rAKCLdsmMo0q5SWCxcdEvwPthNOXbkYQ', NULL, '172.225.84.169', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.4 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.4 facebookexternalhit/1.1 Facebot Twitterbot/1.0', 'eyJfdG9rZW4iOiJpNVRxQUx2YnVSOG1HYUREdmw1Z1NMa3hFRnhyaUVQTTN1MDNnTmI3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791398472),
('6a6jpewWxnEsubEz7yzRWSTFHpbkeSIzsbCWeoY2', NULL, '146.190.218.173', 'Mozilla/5.0 (X11; Linux x86_64; rv:153.0) Gecko/20100101 Firefox/153.0', 'eyJfdG9rZW4iOiJhQXZKMlFSZVJGWERHaExmSlhocUFkTHlaRUcyM096MHdVdkVabVZyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1791446030),
('7BpT4d1Tzm7TJ1i3z9Az3DtZtAC4GqAlFhfJDnka', NULL, '44.206.113.158', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.66 Safari/537.36', 'eyJfdG9rZW4iOiI4Q3hMcDFVUHJkOXludWZrOU1lS2g5N0J6Z0RXSFBCeEZiSWM2M1ZoIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FkbWluLnZvbGFkaXpvLnBlXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1791393726),
('8EFsbWpHlyCJ9D1G2IctwSNhSgFKeineoly3EpQR', NULL, '149.57.180.26', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJkdEdIRkdUWmRpakZSZnFZSTZuWTRDMUd3WU5Ma2VqS2ZON2twdWx0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791434890),
('8OcG1P4MEoX3GmgTyNTu3LaSW55S4tGrA0spySy5', NULL, '38.25.62.241', 'WhatsApp/2.23.20.0', 'eyJfdG9rZW4iOiJ6Wm1nVTc1am80TVY5VzFDQ0dVbjB0dVl4MkFKSVFvaGpOSHFMdUFVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1791389500),
('BDTDrQiNuoc9wEFH0wm19j1xf0zegOuyJWr0PXmN', NULL, '146.190.218.173', 'Mozilla/5.0 (X11; Linux x86_64; rv:153.0) Gecko/20100101 Firefox/153.0', 'eyJfdG9rZW4iOiJLWFFzajk5RFVER0RWRWdJUHdxdWJQaDM0NE14QkV0a2ZUZkNiTUo4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FkbWluLnZvbGFkaXpvLnBlIiwicm91dGUiOiJob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791446030),
('DlVhlTeeBqx1qU6w5xuOl0ytVV8MgM3ZpgQpDgKZ', NULL, '54.237.154.80', 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.60 Safari/537.36', 'eyJfdG9rZW4iOiI3Ukl6ZEJrQ2V4YXAxNVFUYUVyWmtvWDJzcHBheWl6amFIWDhsU1hJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1791393424),
('eBFc1QC6QIX8rFwqfTLDFS01wppt2inBMEHh9sd4', NULL, '87.106.148.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJVNnFMaEFBcEZjSnpGaHM3dE1kNG1PemFMeHFsS2RDMjVpRTBvejhNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1791575418),
('fG1zRjL9rSKXKYZKtQ2FrNELmam9iU1A7zs4oy8b', NULL, '44.206.113.158', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.66 Safari/537.36', 'eyJfdG9rZW4iOiJ1R1ZiMHJrMHdOdks0dW9rOU85b0c1bWhTRU84ZjFHQjhESWRBM1BCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FkbWluLnZvbGFkaXpvLnBlIiwicm91dGUiOiJob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791393725),
('fQyDp2Y7rsde1LJiCtIAX1fRke1RzTH3qgftbO75', NULL, '34.136.170.60', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'eyJfdG9rZW4iOiJsazNNcHBFMTg5U2pDUHR4bGh6R1FpcmZlUWlkaUIwS3duWlZseTJRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FkbWluLnZvbGFkaXpvLnBlIiwicm91dGUiOiJob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791472126),
('G2coaGP4l2SiJAJNNauA5sdJ5F8X70SGqKc2qP5K', NULL, '8.228.82.0', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'eyJfdG9rZW4iOiIwYm1Vbk1yZXl6T0VhM0tJTUZGZm9mUjhMQUxqMFRwRktLNDBnN0RRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FkbWluLnZvbGFkaXpvLnBlXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1791459781),
('hDbCZ6JcGeNN6TcqLpeDS27ix4ZBABMsTuQUm4dI', NULL, '54.247.57.72', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36', 'eyJfdG9rZW4iOiIzZzZZWnRCVUYxR05LRTBPTDRuTDZnYnpXWDU0UmtzdXMzNVRHZDN6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791407487),
('KsJL3nDogFXH4g8m83nHP2m5r8J387XqFOw0pj73', NULL, '136.85.121.169', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJDVUR5aUZmZG9vdG9scGw5RFJHSkxVNHFxdUtwdUE0VE9uRWRHblpCIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1791403595),
('MRmRnpoPYNI8N8LmHiI1GRjHJaQYozLuT666LH9w', NULL, '38.25.62.241', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJ4Qjl6Wk13OHVTOG5RUmUwZlFpYUF1MVNhRFJDazcwUGVEVVMySlpTIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL2FkbWluLnZvbGFkaXpvLnBlXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifX0=', 1791389533),
('Np1KHpSB7zIKZxWnvu1HA4jZCQklqm9ubgKhMBaW', NULL, '34.7.254.57', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3; rv:114.0.1) Gecko/20100101 Firefox/114.0.1', 'eyJfdG9rZW4iOiJySUpSNUZRRENUcTczOGNVZ2RWNmV1M1JDT1NGTWVmd0RXTlY3NmlQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791424972),
('poaTDu8Uo5fi7OkphxmyTsv5pvSL9frdGvXW0Q43', NULL, '185.133.119.44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJXM1RySFhLUGNncUpPbHQzTEE2YVhZbDhwbDlKYmFmUEo5R3d6VmZuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1791519134),
('QCFEj2WKdtgNwAVBTTFEEEEJ3jPxu557VCiFXG6G', NULL, '81.168.41.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJvd29BUjAwaG9GYVZ2cUtsOHFUd3lmWUluTU5tNWZ0eUhuanJ6VVBuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791428627),
('qmmRyN4WymtWCz4g2bqDWJZHP8EK746dlCD0HFqZ', NULL, '54.247.57.72', 'Mozilla/5.0 (X11; Linux x86_64; rv:83.0) Gecko/20100101 Firefox/83.0', 'eyJfdG9rZW4iOiIwMWFFckIzZmNxOU1ucEExNnB4bnpVV1RxblhKMEgyZW1meW1jNGx2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791407482),
('TH0hJZjpd7MfNF5dC6G18RuZwMStK75a77MyePQ1', NULL, '38.25.62.241', 'WhatsApp/2.23.20.0', 'eyJfdG9rZW4iOiJzZ2dPUW84dXM5bVl5NlRuQ0ZmTlVWRFZUb1VKRnZtVUFxeTYxWndSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791389501),
('TXhn0ZimvYJmas9EmAQ6Aakc1twfHHdTJMwjPeg1', NULL, '54.237.154.80', 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.60 Safari/537.36', 'eyJfdG9rZW4iOiJGemF4ZnREVnpzc2NYQXBJQ3M5V1RZWkY2VDdVYnp4SGF3Zk1tNmtCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791393424),
('UI3aBp7eukYM5kmlTx4WVGnPgoiVMXr3EyRA8krm', NULL, '34.136.170.60', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'eyJfdG9rZW4iOiJmV2R6Tlhpb0FVWTBIRlJRZDdOUGVzNW9DaVpWT0pCR3ZMOTJXM2J4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FkbWluLnZvbGFkaXpvLnBlXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1791472129),
('VDPqMIjW2XP4HPoKBeUUoeaOj9OShOO7g7ts7qRI', NULL, '8.228.82.0', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'eyJfdG9rZW4iOiJZSWM3V0xkUXo2Q2plenJkVERQT0ZEbVJEbHJPbFlMSXVJOEdocHJFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2FkbWluLnZvbGFkaXpvLnBlIiwicm91dGUiOiJob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791459778),
('w5pmMMawFt20phnzpwla32H40bjPi0Hw50YrVxoK', NULL, '54.247.57.72', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJ5THpvQnNremQ1a3NuTnNGY0lJbjZiTXdXRHBtcXVQbHl3bGh3eWVDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1791407484),
('ZOkRU6skfNeiB7g7wSLwPo07mNeCiB2tB29XX2FN', 1, '38.25.57.218', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJYN2Y3SDN4cFEwN2JmQ0ZHR241VENPeFJBZ2JLc21OUnc1elV2YXZNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9hZG1pbi52b2xhZGl6by5wZVwvYWRtaW5cL3Byb3llY3RvcyIsInJvdXRlIjoiYWRtaW4ucHJvamVjdHMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiJmZmU0ZWU2NGVlODFlNTk2NWVhZjM3Zjc4NTJhOWU2ZWNmNzI4MjZhZmYwZDk1MzhmZjBmNzNkZTRiZTYxMDdlIn0=', 1791390569);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `testimonials`
--

CREATE TABLE `testimonials` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `media_file_id` bigint UNSIGNED DEFAULT NULL,
  `external_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@admin.com', '2026-10-05 19:42:21', '$2y$12$aHmYN20AY4CXExQ75nfseenOYeOub.becYf1oX4fANk84f3UqwJ4C', 'Geex9oBsVzpiTyURncO18IJoORSb7ngHHaNAn70ZYwqpu56kibNNGVRfQDSx', '2026-10-05 19:42:21', '2026-10-05 19:42:21');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `banner_sliders`
--
ALTER TABLE `banner_sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `banner_slides`
--
ALTER TABLE `banner_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banner_slides_media_file_id_foreign` (`media_file_id`),
  ADD KEY `banner_slides_banner_slider_id_position_index` (`banner_slider_id`,`position`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indices de la tabla `company_profiles`
--
ALTER TABLE `company_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `media_files_media_folder_id_foreign` (`media_folder_id`),
  ADD KEY `media_files_created_at_index` (`created_at`);

--
-- Indices de la tabla `media_folders`
--
ALTER TABLE `media_folders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `media_folders_name_unique` (`name`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `popups`
--
ALTER TABLE `popups`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `popup_items`
--
ALTER TABLE `popup_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `popup_items_media_file_id_foreign` (`media_file_id`),
  ADD KEY `popup_items_popup_id_position_index` (`popup_id`,`position`);

--
-- Indices de la tabla `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `projects_slug_unique` (`slug`),
  ADD KEY `projects_video_media_file_id_foreign` (`video_media_file_id`),
  ADD KEY `projects_is_published_created_at_index` (`is_published`,`created_at`),
  ADD KEY `projects_project_category_id_foreign` (`project_category_id`);

--
-- Indices de la tabla `project_categories`
--
ALTER TABLE `project_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_categories_slug_unique` (`slug`);

--
-- Indices de la tabla `project_phases`
--
ALTER TABLE `project_phases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_phases_media_file_id_foreign` (`media_file_id`),
  ADD KEY `project_phases_project_id_position_index` (`project_id`,`position`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `testimonials_media_file_id_foreign` (`media_file_id`),
  ADD KEY `testimonials_is_published_created_at_index` (`is_published`,`created_at`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `banner_sliders`
--
ALTER TABLE `banner_sliders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `banner_slides`
--
ALTER TABLE `banner_slides`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `company_profiles`
--
ALTER TABLE `company_profiles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `media_files`
--
ALTER TABLE `media_files`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `media_folders`
--
ALTER TABLE `media_folders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `popups`
--
ALTER TABLE `popups`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `popup_items`
--
ALTER TABLE `popup_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `project_categories`
--
ALTER TABLE `project_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `project_phases`
--
ALTER TABLE `project_phases`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `banner_slides`
--
ALTER TABLE `banner_slides`
  ADD CONSTRAINT `banner_slides_banner_slider_id_foreign` FOREIGN KEY (`banner_slider_id`) REFERENCES `banner_sliders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `banner_slides_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `media_files`
--
ALTER TABLE `media_files`
  ADD CONSTRAINT `media_files_media_folder_id_foreign` FOREIGN KEY (`media_folder_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `popup_items`
--
ALTER TABLE `popup_items`
  ADD CONSTRAINT `popup_items_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `popup_items_popup_id_foreign` FOREIGN KEY (`popup_id`) REFERENCES `popups` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_project_category_id_foreign` FOREIGN KEY (`project_category_id`) REFERENCES `project_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_video_media_file_id_foreign` FOREIGN KEY (`video_media_file_id`) REFERENCES `media_files` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `project_phases`
--
ALTER TABLE `project_phases`
  ADD CONSTRAINT `project_phases_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `project_phases_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `testimonials`
--
ALTER TABLE `testimonials`
  ADD CONSTRAINT `testimonials_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
