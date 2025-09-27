-- SQL Migration to create custom_fields table
-- This should be run directly in your MySQL database

CREATE TABLE IF NOT EXISTS `custom_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `options` json NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_fields_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data if needed for testing
-- INSERT INTO `custom_fields` (`key`, `label`, `type`, `options`, `created_at`, `updated_at`) VALUES 
-- ('favorite_strain', 'Favorite Strain', 'text', NULL, NOW(), NOW()),
-- ('preferred_consumption', 'Preferred Consumption Method', 'dropdown', '{"0": "Flower", "1": "Edible", "2": "Concentrate"}', NOW(), NOW());