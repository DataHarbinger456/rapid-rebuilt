-- ============================================================================
-- RAPID REBUILT - DATABASE UPGRADE SCRIPT
-- Version: 2.0
-- Fecha: 2026-04-08
-- Descripcion: Script para actualizar la base de datos con nuevas funcionalidades
-- ============================================================================

-- IMPORTANTE: Hacer backup antes de ejecutar este script
-- mysqldump -u root -p taxpros_rapid > backup_taxpros_rapid_$(date +%Y%m%d).sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- ============================================================================
-- 1. MODIFICAR TABLA USERS - Agregar rol de inspector
-- ============================================================================

-- Modificar el ENUM de user_type para incluir inspector y staff
ALTER TABLE `users`
MODIFY COLUMN `user_type` ENUM('user', 'dealer', 'admin', 'inspector', 'staff') DEFAULT 'user';

-- Agregar campos para inspectores
ALTER TABLE `users`
ADD COLUMN `inspector_license` VARCHAR(100) NULL AFTER `zones`,
ADD COLUMN `inspector_certification_date` DATE NULL AFTER `inspector_license`,
ADD COLUMN `inspector_active` TINYINT(1) DEFAULT 0 AFTER `inspector_certification_date`,
ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `inspector_active`,
ADD COLUMN `preferences` JSON NULL AFTER `avatar`,
ADD COLUMN `last_login_at` TIMESTAMP NULL AFTER `preferences`,
ADD COLUMN `deleted_at` TIMESTAMP NULL AFTER `email_verified_at`;

-- Indices para usuarios
ALTER TABLE `users` ADD INDEX `idx_user_type` (`user_type`);
ALTER TABLE `users` ADD INDEX `idx_inspector_active` (`inspector_active`);

-- ============================================================================
-- 2. CREAR TABLA VEHICLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `vehicles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_case_id` BIGINT UNSIGNED NOT NULL,
    `vin` VARCHAR(17) NOT NULL,
    `title_number` VARCHAR(100) NULL,
    `title_state` VARCHAR(2) DEFAULT 'FL',
    `title_brand` ENUM('salvage', 'certificate_of_destruction', 'rebuilt', 'clean', 'flood', 'junk', 'other') DEFAULT 'salvage',
    `year` INT NOT NULL,
    `make` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `body_type` VARCHAR(50) NULL,
    `color` VARCHAR(50) NULL,
    `weight` INT NULL,
    `engine_type` VARCHAR(100) NULL,
    `fuel_type` VARCHAR(50) NULL,
    `license_plate` VARCHAR(20) NULL,
    `previous_state` VARCHAR(2) NULL,
    `odometer_reading` INT NULL,
    `odometer_date` DATE NULL,
    `odometer_status` ENUM('actual', 'exceeds_limits', 'not_actual') NULL,
    `vehicle_type` ENUM('rebuilt', 'aspt', 'kit_car', 'custom_vehicle', 'street_rod', 'replica', 'mobile_home', 'other') NULL,
    `vin_decoded_data` JSON NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_case_id`) REFERENCES `user_cases`(`id`) ON DELETE CASCADE,
    INDEX `idx_vin` (`vin`),
    INDEX `idx_user_case_id` (`user_case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. CREAR TABLA INSPECTIONS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `inspections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_case_id` BIGINT UNSIGNED NOT NULL,
    `scheduling_id` BIGINT UNSIGNED NULL,
    `inspector_id` BIGINT UNSIGNED NOT NULL,
    `result` ENUM('pending', 'in_progress', 'passed', 'failed', 'incomplete') DEFAULT 'pending',
    `vin_verified` TINYINT(1) DEFAULT 0,
    `vin_location_1` VARCHAR(255) NULL,
    `vin_location_2` VARCHAR(255) NULL,
    `vin_plates_match` TINYINT(1) DEFAULT 0,
    `vin_no_alterations` TINYINT(1) DEFAULT 0,
    `odometer_reading_verified` INT NULL,
    `odometer_functional` TINYINT(1) DEFAULT 0,
    `components_verified` JSON NULL,
    `all_receipts_match` TINYINT(1) DEFAULT 0,
    `brakes_functional` TINYINT(1) DEFAULT 0,
    `lights_functional` TINYINT(1) DEFAULT 0,
    `steering_functional` TINYINT(1) DEFAULT 0,
    `horn_functional` TINYINT(1) DEFAULT 0,
    `wipers_functional` TINYINT(1) DEFAULT 0,
    `airbags_present` TINYINT(1) NULL,
    `seatbelts_functional` TINYINT(1) DEFAULT 0,
    `inspector_notes` TEXT NULL,
    `failure_reasons` TEXT NULL,
    `inspector_signature` LONGTEXT NULL,
    `signed_at` TIMESTAMP NULL,
    `audit_number` VARCHAR(50) NULL,
    `region_number` VARCHAR(50) NULL,
    `decal_number` VARCHAR(50) NULL,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_case_id`) REFERENCES `user_cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`scheduling_id`) REFERENCES `user_form_schedulings`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`inspector_id`) REFERENCES `users`(`id`),
    INDEX `idx_case_result` (`user_case_id`, `result`),
    INDEX `idx_inspector` (`inspector_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. CREAR TABLA COMPONENTS (Componentes Mayores)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `components` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_case_id` BIGINT UNSIGNED NOT NULL,
    `part_type` ENUM(
        'fender_front_left', 'fender_front_right', 'fender_rear_left', 'fender_rear_right',
        'hood', 'bumper_front', 'bumper_rear', 'cowl_assembly',
        'quarter_panel_left', 'quarter_panel_right', 'trunk_lid', 'deck_lid',
        'door_front_left', 'door_front_right', 'door_rear_left', 'door_rear_right',
        'floor_pan', 'engine', 'frame', 'transmission', 'catalytic_converter',
        'airbag_driver', 'airbag_passenger', 'airbag_side_left', 'airbag_side_right', 'airbag_curtain',
        'electric_traction_motor', 'electronic_transmission', 'charge_port',
        'dc_power_converter', 'onboard_charger', 'power_electronics_controller',
        'thermal_system', 'traction_battery', 'other'
    ) NOT NULL,
    `other_description` VARCHAR(255) NULL,
    `condition` ENUM('new', 'used', 'repaired', 'rebuilt', 'original') NOT NULL,
    `source_name` VARCHAR(255) NULL,
    `source_phone` VARCHAR(20) NULL,
    `source_address` VARCHAR(255) NULL,
    `source_city` VARCHAR(100) NULL,
    `source_state` VARCHAR(2) NULL,
    `source_zip` VARCHAR(10) NULL,
    `part_number` VARCHAR(100) NULL,
    `serial_number` VARCHAR(100) NULL,
    `receipt_file_id` BIGINT UNSIGNED NULL,
    `purchase_price` DECIMAL(10,2) NULL,
    `purchase_date` DATE NULL,
    `receipt_number` VARCHAR(100) NULL,
    `verified` TINYINT(1) DEFAULT 0,
    `verified_by` BIGINT UNSIGNED NULL,
    `verified_at` TIMESTAMP NULL,
    `verification_notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_case_id`) REFERENCES `user_cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receipt_file_id`) REFERENCES `uploaded_files`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_case_part` (`user_case_id`, `part_type`),
    INDEX `idx_verified` (`verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. CREAR TABLA INSPECTION_PHOTOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `inspection_photos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `inspection_id` BIGINT UNSIGNED NOT NULL,
    `uploaded_file_id` BIGINT UNSIGNED NOT NULL,
    `photo_type` ENUM(
        'vin_windshield', 'vin_doorjamb', 'vin_engine', 'vin_sticker',
        'odometer',
        'exterior_front', 'exterior_rear', 'exterior_left', 'exterior_right',
        'exterior_front_left', 'exterior_front_right', 'exterior_rear_left', 'exterior_rear_right',
        'interior_dashboard', 'interior_front_seats', 'interior_rear_seats', 'interior_trunk',
        'component_engine', 'component_frame', 'component_transmission', 'component_other',
        'damage_before', 'repair_after', 'other'
    ) NOT NULL,
    `description` VARCHAR(255) NULL,
    `latitude` DECIMAL(10,8) NULL,
    `longitude` DECIMAL(11,8) NULL,
    `taken_at` TIMESTAMP NULL,
    `device_info` VARCHAR(255) NULL,
    `component_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`inspection_id`) REFERENCES `inspections`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_file_id`) REFERENCES `uploaded_files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`component_id`) REFERENCES `components`(`id`) ON DELETE SET NULL,
    INDEX `idx_inspection_type` (`inspection_id`, `photo_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. CREAR TABLA CASE_TIMELINE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `case_timeline` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_case_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `event_type` ENUM(
        'case_created', 'case_submitted', 'case_assigned', 'status_changed',
        'document_uploaded', 'document_approved', 'document_rejected', 'document_requested',
        'payment_initiated', 'payment_received', 'payment_failed',
        'appointment_scheduled', 'appointment_confirmed', 'appointment_rescheduled',
        'appointment_cancelled', 'appointment_no_show',
        'inspection_started', 'inspection_paused', 'inspection_completed',
        'inspection_passed', 'inspection_failed',
        'documents_generated', 'documents_printed', 'sent_to_dmv', 'dmv_received', 'title_issued',
        'case_completed', 'case_cancelled', 'note_added', 'message_sent', 'reminder_sent', 'other'
    ) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `metadata` JSON NULL,
    `is_system_event` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_case_id`) REFERENCES `user_cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_case_timeline` (`user_case_id`, `created_at`),
    INDEX `idx_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. CREAR TABLA MESSAGES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_case_id` BIGINT UNSIGNED NOT NULL,
    `sender_id` BIGINT UNSIGNED NOT NULL,
    `recipient_id` BIGINT UNSIGNED NULL,
    `message` TEXT NOT NULL,
    `attachments` JSON NULL,
    `is_internal` TINYINT(1) DEFAULT 0,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` TIMESTAMP NULL,
    `message_type` ENUM('text', 'document_request', 'status_update', 'system_notification', 'reminder') DEFAULT 'text',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_case_id`) REFERENCES `user_cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_case_messages` (`user_case_id`, `created_at`),
    INDEX `idx_recipient_unread` (`recipient_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. CREAR TABLA SETTINGS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `type` VARCHAR(20) DEFAULT 'string',
    `group` VARCHAR(50) DEFAULT 'general',
    `description` TEXT NULL,
    `is_public` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`),
    INDEX `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones iniciales
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `description`, `is_public`) VALUES
-- Precios
('inspection_fee', '40.00', 'decimal', 'pricing', 'Tarifa de inspeccion inicial', 0),
('reinspection_fee', '20.00', 'decimal', 'pricing', 'Tarifa de re-inspeccion', 0),
('service_fee_standard', '150.00', 'decimal', 'pricing', 'Tarifa de servicio estandar', 0),
('service_fee_expedited', '250.00', 'decimal', 'pricing', 'Tarifa de servicio express', 0),
('service_fee_dealer', '125.00', 'decimal', 'pricing', 'Tarifa de servicio para dealers', 0),
-- Horarios
('business_hours_start', '08:00', 'string', 'schedule', 'Hora de inicio de operaciones', 0),
('business_hours_end', '17:00', 'string', 'schedule', 'Hora de fin de operaciones', 0),
('appointment_duration', '30', 'integer', 'schedule', 'Duracion de cita en minutos', 0),
('max_appointments_per_day', '16', 'integer', 'schedule', 'Maximo de citas por dia por inspector', 0),
('days_in_advance_booking', '30', 'integer', 'schedule', 'Dias maximos de anticipacion para agendar', 0),
('min_hours_before_booking', '24', 'integer', 'schedule', 'Horas minimas de anticipacion', 0),
('work_days', '["monday","tuesday","wednesday","thursday","friday"]', 'json', 'schedule', 'Dias laborables', 0),
-- Notificaciones
('send_reminder_24h', '1', 'boolean', 'notifications', 'Enviar recordatorio 24h antes', 0),
('send_reminder_1h', '1', 'boolean', 'notifications', 'Enviar recordatorio 1h antes', 0),
('send_email_on_status_change', '1', 'boolean', 'notifications', 'Notificar cambios de estado por email', 0),
('send_sms_reminders', '0', 'boolean', 'notifications', 'Enviar recordatorios por SMS', 0),
('admin_notification_email', 'admin@rapidrebuilt.com', 'string', 'notifications', 'Email para notificaciones de admin', 0),
-- Empresa
('company_name', 'Rapid Rebuilt', 'string', 'company', 'Nombre de la empresa', 1),
('company_address', '', 'string', 'company', 'Direccion de la empresa', 1),
('company_city', 'Tampa', 'string', 'company', 'Ciudad', 1),
('company_state', 'FL', 'string', 'company', 'Estado', 1),
('company_zip', '', 'string', 'company', 'Codigo postal', 1),
('company_phone', '', 'string', 'company', 'Telefono', 1),
('company_email', 'info@rapidrebuilt.com', 'string', 'company', 'Email de contacto', 1),
('prvip_license_number', '', 'string', 'company', 'Numero de licencia PRVIP', 0),
('tax_id', '', 'string', 'company', 'Tax ID / EIN', 0),
-- Integraciones
('stripe_enabled', '1', 'boolean', 'integrations', 'Stripe habilitado', 0),
('twilio_enabled', '0', 'boolean', 'integrations', 'Twilio SMS habilitado', 0),
('vin_decoder_api', 'nhtsa', 'string', 'integrations', 'API de decodificacion de VIN', 0)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- ============================================================================
-- 9. MEJORAR TABLA USER_CASES
-- ============================================================================

-- Agregar columnas nuevas a user_cases
ALTER TABLE `user_cases`
ADD COLUMN IF NOT EXISTS `assigned_inspector_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `service_type` ENUM('rebuilt_title', 'salvage_inspection', 'dealer_package', 'expedited', 'reinspection') DEFAULT 'rebuilt_title' AFTER `case_status`,
ADD COLUMN IF NOT EXISTS `inspection_fee` DECIMAL(10,2) DEFAULT 40.00 AFTER `service_type`,
ADD COLUMN IF NOT EXISTS `service_fee` DECIMAL(10,2) DEFAULT 0.00 AFTER `inspection_fee`,
ADD COLUMN IF NOT EXISTS `reinspection_fee` DECIMAL(10,2) DEFAULT 0.00 AFTER `service_fee`,
ADD COLUMN IF NOT EXISTS `total_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `reinspection_fee`,
ADD COLUMN IF NOT EXISTS `is_paid` TINYINT(1) DEFAULT 0 AFTER `total_amount`,
ADD COLUMN IF NOT EXISTS `payment_id` BIGINT UNSIGNED NULL AFTER `is_paid`,
ADD COLUMN IF NOT EXISTS `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal' AFTER `payment_id`,
ADD COLUMN IF NOT EXISTS `inspection_location` VARCHAR(255) NULL AFTER `priority`,
ADD COLUMN IF NOT EXISTS `customer_notes` TEXT NULL AFTER `inspection_location`,
ADD COLUMN IF NOT EXISTS `internal_notes` TEXT NULL AFTER `customer_notes`,
ADD COLUMN IF NOT EXISTS `submitted_at` TIMESTAMP NULL AFTER `internal_notes`,
ADD COLUMN IF NOT EXISTS `paid_at` TIMESTAMP NULL AFTER `submitted_at`,
ADD COLUMN IF NOT EXISTS `scheduled_at` TIMESTAMP NULL AFTER `paid_at`,
ADD COLUMN IF NOT EXISTS `inspected_at` TIMESTAMP NULL AFTER `scheduled_at`,
ADD COLUMN IF NOT EXISTS `completed_at` TIMESTAMP NULL AFTER `inspected_at`,
ADD COLUMN IF NOT EXISTS `cancelled_at` TIMESTAMP NULL AFTER `completed_at`,
ADD COLUMN IF NOT EXISTS `cancellation_reason` VARCHAR(255) NULL AFTER `cancelled_at`,
ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL AFTER `updated_at`;

-- Agregar foreign key para inspector (si la columna existe)
-- ALTER TABLE `user_cases` ADD CONSTRAINT `fk_cases_inspector` FOREIGN KEY (`assigned_inspector_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Indices adicionales
ALTER TABLE `user_cases` ADD INDEX IF NOT EXISTS `idx_assigned_inspector` (`assigned_inspector_id`);
ALTER TABLE `user_cases` ADD INDEX IF NOT EXISTS `idx_service_type` (`service_type`);
ALTER TABLE `user_cases` ADD INDEX IF NOT EXISTS `idx_is_paid` (`is_paid`);
ALTER TABLE `user_cases` ADD INDEX IF NOT EXISTS `idx_priority` (`priority`);
ALTER TABLE `user_cases` ADD INDEX IF NOT EXISTS `idx_status_created` (`status`, `created_at`);

-- ============================================================================
-- 10. MEJORAR TABLA USER_FORM_SCHEDULINGS
-- ============================================================================

ALTER TABLE `user_form_schedulings`
ADD COLUMN IF NOT EXISTS `inspector_id` BIGINT UNSIGNED NULL AFTER `user_case_id`,
ADD COLUMN IF NOT EXISTS `duration_minutes` INT DEFAULT 30 AFTER `end`,
ADD COLUMN IF NOT EXISTS `location_name` VARCHAR(255) NULL AFTER `subject`,
ADD COLUMN IF NOT EXISTS `location_address` VARCHAR(255) NULL AFTER `location_name`,
ADD COLUMN IF NOT EXISTS `location_city` VARCHAR(100) NULL AFTER `location_address`,
ADD COLUMN IF NOT EXISTS `location_state` VARCHAR(2) DEFAULT 'FL' AFTER `location_city`,
ADD COLUMN IF NOT EXISTS `location_zip` VARCHAR(10) NULL AFTER `location_state`,
ADD COLUMN IF NOT EXISTS `location_lat` DECIMAL(10,8) NULL AFTER `location_zip`,
ADD COLUMN IF NOT EXISTS `location_lng` DECIMAL(11,8) NULL AFTER `location_lat`,
ADD COLUMN IF NOT EXISTS `customer_notes` TEXT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `inspector_notes` TEXT NULL AFTER `customer_notes`,
ADD COLUMN IF NOT EXISTS `reminder_24h_sent` TINYINT(1) DEFAULT 0 AFTER `inspector_notes`,
ADD COLUMN IF NOT EXISTS `reminder_1h_sent` TINYINT(1) DEFAULT 0 AFTER `reminder_24h_sent`,
ADD COLUMN IF NOT EXISTS `confirmation_sent` TINYINT(1) DEFAULT 0 AFTER `reminder_1h_sent`,
ADD COLUMN IF NOT EXISTS `status_reason` VARCHAR(255) NULL AFTER `confirmation_sent`,
ADD COLUMN IF NOT EXISTS `rescheduled_from_id` BIGINT UNSIGNED NULL AFTER `status_reason`;

-- Actualizar el ENUM de status para schedulings
ALTER TABLE `user_form_schedulings`
MODIFY COLUMN `status` ENUM('pending', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled', 'rescheduled') DEFAULT 'pending';

-- Indices
ALTER TABLE `user_form_schedulings` ADD INDEX IF NOT EXISTS `idx_inspector` (`inspector_id`);
ALTER TABLE `user_form_schedulings` ADD INDEX IF NOT EXISTS `idx_start_status` (`start`, `status`);

-- ============================================================================
-- 11. MEJORAR TABLA NOTIFICATIONS
-- ============================================================================

ALTER TABLE `notifications`
ADD COLUMN IF NOT EXISTS `user_id` BIGINT UNSIGNED NULL AFTER `notification`,
ADD COLUMN IF NOT EXISTS `type` VARCHAR(50) DEFAULT 'info' AFTER `user_case_id`,
ADD COLUMN IF NOT EXISTS `title` VARCHAR(255) NULL AFTER `type`,
ADD COLUMN IF NOT EXISTS `action_url` VARCHAR(255) NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `read_at` TIMESTAMP NULL AFTER `action_url`,
ADD COLUMN IF NOT EXISTS `data` JSON NULL AFTER `read_at`;

-- Indices
ALTER TABLE `notifications` ADD INDEX IF NOT EXISTS `idx_user` (`user_id`);
ALTER TABLE `notifications` ADD INDEX IF NOT EXISTS `idx_user_status` (`user_id`, `status`);

-- ============================================================================
-- 12. AGREGAR INDICES DE OPTIMIZACION
-- ============================================================================

-- Indices para busquedas comunes
ALTER TABLE `user_forms` ADD INDEX IF NOT EXISTS `idx_case_status` (`user_case_id`, `status`);
ALTER TABLE `user_form_details` ADD INDEX IF NOT EXISTS `idx_form_key` (`user_form_id`, `meta_key`);
ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_case_status` (`user_case_id`, `status`);

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================================
-- VERIFICACION - Ejecutar estas consultas para verificar la actualizacion
-- ============================================================================

-- SELECT 'Users table columns' as info;
-- SHOW COLUMNS FROM users;

-- SELECT 'New tables created' as info;
-- SHOW TABLES LIKE 'vehicles';
-- SHOW TABLES LIKE 'inspections';
-- SHOW TABLES LIKE 'components';
-- SHOW TABLES LIKE 'inspection_photos';
-- SHOW TABLES LIKE 'case_timeline';
-- SHOW TABLES LIKE 'messages';
-- SHOW TABLES LIKE 'settings';

-- SELECT 'Settings count' as info, COUNT(*) as total FROM settings;

SELECT '============================================' as '';
SELECT 'DATABASE UPGRADE COMPLETED SUCCESSFULLY!' as 'STATUS';
SELECT '============================================' as '';
