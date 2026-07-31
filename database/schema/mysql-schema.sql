/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `case_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_timeline` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_case_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event_type` enum('case_created','case_submitted','case_assigned','status_changed','document_uploaded','document_approved','document_rejected','document_requested','payment_initiated','payment_received','payment_failed','appointment_scheduled','appointment_confirmed','appointment_rescheduled','appointment_cancelled','appointment_no_show','inspection_started','inspection_paused','inspection_completed','inspection_passed','inspection_failed','documents_generated','documents_printed','sent_to_dmv','dmv_received','title_issued','case_completed','case_cancelled','note_added','message_sent','reminder_sent','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_system_event` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_timeline_user_id_foreign` (`user_id`),
  KEY `case_timeline_user_case_id_created_at_index` (`user_case_id`,`created_at`),
  KEY `case_timeline_event_type_index` (`event_type`),
  CONSTRAINT `case_timeline_user_case_id_foreign` FOREIGN KEY (`user_case_id`) REFERENCES `user_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_timeline_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `case_timeline_chk_1` CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_case_id` bigint unsigned NOT NULL,
  `part_type` enum('fender_front_left','fender_front_right','fender_rear_left','fender_rear_right','hood','bumper_front','bumper_rear','cowl_assembly','quarter_panel_left','quarter_panel_right','trunk_lid','deck_lid','door_front_left','door_front_right','door_rear_left','door_rear_right','floor_pan','engine','frame','transmission','catalytic_converter','airbag_driver','airbag_passenger','airbag_side_left','airbag_side_right','airbag_curtain','electric_traction_motor','electronic_transmission','charge_port','dc_power_converter','onboard_charger','power_electronics_controller','thermal_system','traction_battery','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `other_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition` enum('new','used','repaired','rebuilt','original') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_state` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_zip` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_file_id` bigint unsigned DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `components_receipt_file_id_foreign` (`receipt_file_id`),
  KEY `components_verified_by_foreign` (`verified_by`),
  KEY `components_user_case_id_part_type_index` (`user_case_id`,`part_type`),
  KEY `components_verified_index` (`verified`),
  CONSTRAINT `components_receipt_file_id_foreign` FOREIGN KEY (`receipt_file_id`) REFERENCES `uploaded_files` (`id`) ON DELETE SET NULL,
  CONSTRAINT `components_user_case_id_foreign` FOREIGN KEY (`user_case_id`) REFERENCES `user_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `components_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dmv_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dmv_forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_type` enum('HSMV_default-form','HSMV_84490','HSMV_84491','HSMV_82050','HSMV_82053') COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `form_data` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_number` longtext COLLATE utf8mb4_unicode_ci,
  `fleet_number` longtext COLLATE utf8mb4_unicode_ci,
  `unit_number` longtext COLLATE utf8mb4_unicode_ci,
  `owner_county` longtext COLLATE utf8mb4_unicode_ci,
  `is_florida_resident` tinyint(1) DEFAULT NULL,
  `is_us_citizen` tinyint(1) DEFAULT NULL,
  `is_deaf_hard_of_hearing` tinyint(1) DEFAULT NULL,
  `owner_name` longtext COLLATE utf8mb4_unicode_ci,
  `owner_phone` longtext COLLATE utf8mb4_unicode_ci,
  `owner_email` longtext COLLATE utf8mb4_unicode_ci,
  `owner_dl_id` longtext COLLATE utf8mb4_unicode_ci,
  `owner_dob` date DEFAULT NULL,
  `owner_address` longtext COLLATE utf8mb4_unicode_ci,
  `owner_city` longtext COLLATE utf8mb4_unicode_ci,
  `owner_state` longtext COLLATE utf8mb4_unicode_ci,
  `owner_zip` longtext COLLATE utf8mb4_unicode_ci,
  `mail_to_name` longtext COLLATE utf8mb4_unicode_ci,
  `mail_to_phone` longtext COLLATE utf8mb4_unicode_ci,
  `mail_to_email` longtext COLLATE utf8mb4_unicode_ci,
  `mail_to_address` longtext COLLATE utf8mb4_unicode_ci,
  `mail_to_city` longtext COLLATE utf8mb4_unicode_ci,
  `mail_to_state` longtext COLLATE utf8mb4_unicode_ci,
  `mail_to_zip` longtext COLLATE utf8mb4_unicode_ci,
  `is_original` tinyint(1) DEFAULT NULL,
  `is_transfer` tinyint(1) DEFAULT NULL,
  `vin` longtext COLLATE utf8mb4_unicode_ci,
  `title_number` longtext COLLATE utf8mb4_unicode_ci,
  `license_plate_number` longtext COLLATE utf8mb4_unicode_ci,
  `previous_state_of_issue` longtext COLLATE utf8mb4_unicode_ci,
  `make` longtext COLLATE utf8mb4_unicode_ci,
  `model` longtext COLLATE utf8mb4_unicode_ci,
  `year` int DEFAULT NULL,
  `body_type` longtext COLLATE utf8mb4_unicode_ci,
  `color` longtext COLLATE utf8mb4_unicode_ci,
  `weight` decimal(10,2) DEFAULT NULL,
  `gvw` longtext COLLATE utf8mb4_unicode_ci,
  `bhp_cc` longtext COLLATE utf8mb4_unicode_ci,
  `odometer_reading` longtext COLLATE utf8mb4_unicode_ci,
  `odometer_reading_date` date DEFAULT NULL,
  `odometer_actual_mileage` tinyint(1) DEFAULT NULL,
  `odometer_exceeds_limits` tinyint(1) DEFAULT NULL,
  `odometer_not_actual_mileage` tinyint(1) DEFAULT NULL,
  `affidavit_1` longtext COLLATE utf8mb4_unicode_ci,
  `affidavit_2` longtext COLLATE utf8mb4_unicode_ci,
  `seller_signature` longtext COLLATE utf8mb4_unicode_ci,
  `seller_printed_name` longtext COLLATE utf8mb4_unicode_ci,
  `seller_date` date DEFAULT NULL,
  `co_seller_signature` longtext COLLATE utf8mb4_unicode_ci,
  `co_seller_printed_name` longtext COLLATE utf8mb4_unicode_ci,
  `co_seller_date` date DEFAULT NULL,
  `rebuilt` tinyint(1) DEFAULT NULL,
  `aspt` tinyint(1) DEFAULT NULL,
  `kit_car` tinyint(1) DEFAULT NULL,
  `other` longtext COLLATE utf8mb4_unicode_ci,
  `year_84490` int DEFAULT NULL,
  `make_84490` longtext COLLATE utf8mb4_unicode_ci,
  `id_84490` longtext COLLATE utf8mb4_unicode_ci,
  `color_84490` longtext COLLATE utf8mb4_unicode_ci,
  `body_84490` longtext COLLATE utf8mb4_unicode_ci,
  `length_84490` longtext COLLATE utf8mb4_unicode_ci,
  `title_number_84490` longtext COLLATE utf8mb4_unicode_ci,
  `title_state_84490` longtext COLLATE utf8mb4_unicode_ci,
  `other_title_number_84490` longtext COLLATE utf8mb4_unicode_ci,
  `other_title_state_84490` longtext COLLATE utf8mb4_unicode_ci,
  `major_component_parts` longtext COLLATE utf8mb4_unicode_ci,
  `number_of_receipts` int DEFAULT NULL,
  `custom_vehicle` tinyint(1) DEFAULT NULL,
  `street_rod` tinyint(1) DEFAULT NULL,
  `tax_due_on` longtext COLLATE utf8mb4_unicode_ci,
  `mobile_home_with_tongue` tinyint(1) DEFAULT NULL,
  `mobile_home_without_tongue` tinyint(1) DEFAULT NULL,
  `comments_84490` longtext COLLATE utf8mb4_unicode_ci,
  `inspector_signature` longtext COLLATE utf8mb4_unicode_ci,
  `inspector_print_name` longtext COLLATE utf8mb4_unicode_ci,
  `inspector_region` longtext COLLATE utf8mb4_unicode_ci,
  `inspector_date` date DEFAULT NULL,
  `vin_84490` longtext COLLATE utf8mb4_unicode_ci,
  `odometer_84490` longtext COLLATE utf8mb4_unicode_ci,
  `audit_number` longtext COLLATE utf8mb4_unicode_ci,
  `region_number` longtext COLLATE utf8mb4_unicode_ci,
  `rebuilt_84491` tinyint(1) DEFAULT NULL,
  `aspt_84491` tinyint(1) DEFAULT NULL,
  `kit_car_84491` tinyint(1) DEFAULT NULL,
  `other_84491` longtext COLLATE utf8mb4_unicode_ci,
  `year_84491` int DEFAULT NULL,
  `make_84491` longtext COLLATE utf8mb4_unicode_ci,
  `id_84491` longtext COLLATE utf8mb4_unicode_ci,
  `color_84491` longtext COLLATE utf8mb4_unicode_ci,
  `body_84491` longtext COLLATE utf8mb4_unicode_ci,
  `length_84491` longtext COLLATE utf8mb4_unicode_ci,
  `repairs_description` longtext COLLATE utf8mb4_unicode_ci,
  `date_84491` date DEFAULT NULL,
  `applicant_name_84491` longtext COLLATE utf8mb4_unicode_ci,
  `applicant_signature_84491` longtext COLLATE utf8mb4_unicode_ci,
  `applicant_business_name_84491` longtext COLLATE utf8mb4_unicode_ci,
  `applicant_business_signature_84491` longtext COLLATE utf8mb4_unicode_ci,
  `notice_of_sale` tinyint(1) DEFAULT NULL,
  `bill_of_sale` tinyint(1) DEFAULT NULL,
  `year_82050` int DEFAULT NULL,
  `maker_82050` longtext COLLATE utf8mb4_unicode_ci,
  `body_type_82050` longtext COLLATE utf8mb4_unicode_ci,
  `model_82050` longtext COLLATE utf8mb4_unicode_ci,
  `color_82050` longtext COLLATE utf8mb4_unicode_ci,
  `title_number_82050` longtext COLLATE utf8mb4_unicode_ci,
  `current_title_issue_date_82050` date DEFAULT NULL,
  `vehicle_identification_number_82050` longtext COLLATE utf8mb4_unicode_ci,
  `purchaser_name_82050` longtext COLLATE utf8mb4_unicode_ci,
  `purchaser_address_82050` longtext COLLATE utf8mb4_unicode_ci,
  `purchaser_city_82050` longtext COLLATE utf8mb4_unicode_ci,
  `purchaser_state_82050` longtext COLLATE utf8mb4_unicode_ci,
  `purchaser_zipcode_82050` longtext COLLATE utf8mb4_unicode_ci,
  `date_of_sale_82050` date DEFAULT NULL,
  `selling_price_82050` decimal(10,2) DEFAULT NULL,
  `odometer_reading_82050` longtext COLLATE utf8mb4_unicode_ci,
  `odometer_reading_date_82050` date DEFAULT NULL,
  `odometer_actual_mileage_82050` tinyint(1) DEFAULT NULL,
  `odometer_exceeds_limits_82050` tinyint(1) DEFAULT NULL,
  `odometer_not_actual_mileage_82050` tinyint(1) DEFAULT NULL,
  `affidavit_82050_1` longtext COLLATE utf8mb4_unicode_ci,
  `affidavit_82050_2` longtext COLLATE utf8mb4_unicode_ci,
  `seller_signature_82050` longtext COLLATE utf8mb4_unicode_ci,
  `seller_printed_name_82050` longtext COLLATE utf8mb4_unicode_ci,
  `seller_date_82050` date DEFAULT NULL,
  `co_seller_signature_82050` longtext COLLATE utf8mb4_unicode_ci,
  `co_seller_printed_name_82050` longtext COLLATE utf8mb4_unicode_ci,
  `co_seller_date_82050` date DEFAULT NULL,
  `purchaser_signature_82050` longtext COLLATE utf8mb4_unicode_ci,
  `purchaser_printed_name_82050` longtext COLLATE utf8mb4_unicode_ci,
  `purchaser_date_82050` date DEFAULT NULL,
  `co_purchaser_signature_82050` longtext COLLATE utf8mb4_unicode_ci,
  `co_purchaser_printed_name_82050` longtext COLLATE utf8mb4_unicode_ci,
  `co_purchaser_date_82050` date DEFAULT NULL,
  `power_of_attorney_date_82053` date DEFAULT NULL,
  `attorney_in_fact_name_82053` longtext COLLATE utf8mb4_unicode_ci,
  `motor_vehicle_82053` tinyint(1) DEFAULT NULL,
  `mobile_home_82053` tinyint(1) DEFAULT NULL,
  `vessel_82053` tinyint(1) DEFAULT NULL,
  `vessel_with_untitled_trailer_82053` tinyint(1) DEFAULT NULL,
  `vessel_with_titled_trailer_82053` tinyint(1) DEFAULT NULL,
  `year_82053` int DEFAULT NULL,
  `make_82053` longtext COLLATE utf8mb4_unicode_ci,
  `body_type_82053` longtext COLLATE utf8mb4_unicode_ci,
  `title_number_82053` longtext COLLATE utf8mb4_unicode_ci,
  `vin_82053` longtext COLLATE utf8mb4_unicode_ci,
  `year_82053_1` int DEFAULT NULL,
  `make_82053_1` longtext COLLATE utf8mb4_unicode_ci,
  `body_type_82053_1` longtext COLLATE utf8mb4_unicode_ci,
  `title_number_82053_1` longtext COLLATE utf8mb4_unicode_ci,
  `vin_82053_1` longtext COLLATE utf8mb4_unicode_ci,
  `owner_name_82053` longtext COLLATE utf8mb4_unicode_ci,
  `owner_signature_82053` longtext COLLATE utf8mb4_unicode_ci,
  `owner_driver_license_number_82053` longtext COLLATE utf8mb4_unicode_ci,
  `owner_date_of_birth_82053` date DEFAULT NULL,
  `owner_address_82053` longtext COLLATE utf8mb4_unicode_ci,
  `owner_city_82053` longtext COLLATE utf8mb4_unicode_ci,
  `owner_state_82053` longtext COLLATE utf8mb4_unicode_ci,
  `owner_zipcode_82053` longtext COLLATE utf8mb4_unicode_ci,
  `co_owner_name_82053` longtext COLLATE utf8mb4_unicode_ci,
  `co_owner_signature_82053` longtext COLLATE utf8mb4_unicode_ci,
  `co_owner_driver_license_number_82053` longtext COLLATE utf8mb4_unicode_ci,
  `co_owner_date_of_birth_82053` date DEFAULT NULL,
  `co_owner_address_82053` longtext COLLATE utf8mb4_unicode_ci,
  `co_owner_city_82053` longtext COLLATE utf8mb4_unicode_ci,
  `co_owner_state_82053` longtext COLLATE utf8mb4_unicode_ci,
  `co_owner_zipcode_82053` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_form` (`user_id`,`form_type`),
  CONSTRAINT `dmv_forms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspection_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint unsigned NOT NULL,
  `uploaded_file_id` bigint unsigned NOT NULL,
  `photo_type` enum('vin_windshield','vin_doorjamb','vin_engine','vin_sticker','odometer','exterior_front','exterior_rear','exterior_left','exterior_right','exterior_front_left','exterior_front_right','exterior_rear_left','exterior_rear_right','interior_dashboard','interior_front_seats','interior_rear_seats','interior_trunk','component_engine','component_frame','component_transmission','component_other','damage_before','repair_after','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `taken_at` timestamp NULL DEFAULT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `component_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inspection_photos_uploaded_file_id_foreign` (`uploaded_file_id`),
  KEY `inspection_photos_component_id_foreign` (`component_id`),
  KEY `inspection_photos_inspection_id_photo_type_index` (`inspection_id`,`photo_type`),
  CONSTRAINT `inspection_photos_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inspection_photos_inspection_id_foreign` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspection_photos_uploaded_file_id_foreign` FOREIGN KEY (`uploaded_file_id`) REFERENCES `uploaded_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_case_id` bigint unsigned NOT NULL,
  `scheduling_id` bigint unsigned DEFAULT NULL,
  `inspector_id` bigint unsigned NOT NULL,
  `result` enum('pending','in_progress','passed','failed','incomplete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `vin_verified` tinyint(1) NOT NULL DEFAULT '0',
  `vin_location_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vin_location_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vin_plates_match` tinyint(1) NOT NULL DEFAULT '0',
  `vin_no_alterations` tinyint(1) NOT NULL DEFAULT '0',
  `odometer_reading_verified` int DEFAULT NULL,
  `odometer_functional` tinyint(1) NOT NULL DEFAULT '0',
  `components_verified` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `all_receipts_match` tinyint(1) NOT NULL DEFAULT '0',
  `brakes_functional` tinyint(1) NOT NULL DEFAULT '0',
  `lights_functional` tinyint(1) NOT NULL DEFAULT '0',
  `steering_functional` tinyint(1) NOT NULL DEFAULT '0',
  `horn_functional` tinyint(1) NOT NULL DEFAULT '0',
  `wipers_functional` tinyint(1) NOT NULL DEFAULT '0',
  `airbags_present` tinyint(1) DEFAULT NULL,
  `seatbelts_functional` tinyint(1) NOT NULL DEFAULT '0',
  `inspector_notes` text COLLATE utf8mb4_unicode_ci,
  `failure_reasons` text COLLATE utf8mb4_unicode_ci,
  `inspector_signature` longtext COLLATE utf8mb4_unicode_ci,
  `signed_at` timestamp NULL DEFAULT NULL,
  `audit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decal_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inspections_scheduling_id_foreign` (`scheduling_id`),
  KEY `inspections_user_case_id_result_index` (`user_case_id`,`result`),
  KEY `inspections_inspector_id_index` (`inspector_id`),
  CONSTRAINT `inspections_inspector_id_foreign` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`),
  CONSTRAINT `inspections_scheduling_id_foreign` FOREIGN KEY (`scheduling_id`) REFERENCES `user_form_schedulings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inspections_user_case_id_foreign` FOREIGN KEY (`user_case_id`) REFERENCES `user_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspections_chk_1` CHECK (json_valid(`components_verified`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_case_id` bigint unsigned NOT NULL,
  `sender_id` bigint unsigned NOT NULL,
  `recipient_id` bigint unsigned DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `message_type` enum('text','document_request','status_update','system_notification','reminder') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_sender_id_foreign` (`sender_id`),
  KEY `messages_user_case_id_created_at_index` (`user_case_id`,`created_at`),
  KEY `messages_recipient_id_is_read_index` (`recipient_id`,`is_read`),
  CONSTRAINT `messages_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_user_case_id_foreign` FOREIGN KEY (`user_case_id`) REFERENCES `user_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_chk_1` CHECK (json_valid(`attachments`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_case_id` int unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'unseen',
  `action_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_case_id_index` (`user_case_id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_chk_1` CHECK (json_valid(`data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `user_case_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uploaded_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uploaded_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_case_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int unsigned DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected','replaced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `size` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uploaded_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `uploaded_files_reviewed_by_foreign` (`reviewed_by`),
  KEY `uploaded_files_user_case_id_document_type_index` (`user_case_id`,`document_type`),
  KEY `uploaded_files_user_case_id_status_index` (`user_case_id`,`status`),
  KEY `uploaded_files_document_type_index` (`document_type`),
  KEY `uploaded_files_status_index` (`status`),
  CONSTRAINT `uploaded_files_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uploaded_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uploaded_files_user_case_id_foreign` FOREIGN KEY (`user_case_id`) REFERENCES `user_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `case_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned NOT NULL,
  `assigned_inspector_id` bigint unsigned DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `case_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_type` enum('rebuilt_title','salvage_inspection','dealer_package','expedited','reinspection') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rebuilt_title',
  `inspection_fee` decimal(10,2) NOT NULL DEFAULT '40.00',
  `service_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reinspection_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `payment_id` bigint unsigned DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `inspection_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_notes` text COLLATE utf8mb4_unicode_ci,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `inspected_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_cases_user_id_index` (`user_id`),
  KEY `user_cases_assigned_inspector_id_index` (`assigned_inspector_id`),
  KEY `user_cases_service_type_index` (`service_type`),
  KEY `user_cases_is_paid_index` (`is_paid`),
  KEY `user_cases_priority_index` (`priority`),
  CONSTRAINT `user_cases_assigned_inspector_id_foreign` FOREIGN KEY (`assigned_inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_form_schedulings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_form_schedulings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `user_case_id` int unsigned NOT NULL,
  `inspector_id` bigint unsigned DEFAULT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `duration_minutes` int NOT NULL DEFAULT '30',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_state` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FL',
  `location_zip` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','no_show','cancelled','rescheduled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `customer_notes` text COLLATE utf8mb4_unicode_ci,
  `inspector_notes` text COLLATE utf8mb4_unicode_ci,
  `reminder_24h_sent` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_1h_sent` tinyint(1) NOT NULL DEFAULT '0',
  `confirmation_sent` tinyint(1) NOT NULL DEFAULT '0',
  `status_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rescheduled_from_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_form_schedulings_user_id_index` (`user_id`),
  KEY `user_form_schedulings_user_case_id_index` (`user_case_id`),
  KEY `user_form_schedulings_rescheduled_from_id_foreign` (`rescheduled_from_id`),
  KEY `user_form_schedulings_inspector_id_index` (`inspector_id`),
  KEY `user_form_schedulings_start_status_index` (`start`,`status`),
  CONSTRAINT `user_form_schedulings_inspector_id_foreign` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_form_schedulings_rescheduled_from_id_foreign` FOREIGN KEY (`rescheduled_from_id`) REFERENCES `user_form_schedulings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` enum('user','dealer','admin','inspector','staff') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `states` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `county` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dealer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_type` enum('VI','VW','VA','SD','VF') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inspector_license` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inspector_certification_date` date DEFAULT NULL,
  `inspector_active` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `additional_info` text COLLATE utf8mb4_unicode_ci,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  CONSTRAINT `users_chk_1` CHECK (json_valid(`preferences`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_case_id` bigint unsigned NOT NULL,
  `vin` varchar(17) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_state` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FL',
  `title_brand` enum('salvage','certificate_of_destruction','rebuilt','clean','flood','junk','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'salvage',
  `year` int NOT NULL,
  `make` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` int DEFAULT NULL,
  `engine_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fuel_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_plate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odometer_reading` int DEFAULT NULL,
  `odometer_date` date DEFAULT NULL,
  `odometer_status` enum('actual','exceeds_limits','not_actual') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_type` enum('rebuilt','aspt','kit_car','custom_vehicle','street_rod','replica','mobile_home','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vin_decoded_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicles_vin_index` (`vin`),
  KEY `vehicles_user_case_id_index` (`user_case_id`),
  CONSTRAINT `vehicles_user_case_id_foreign` FOREIGN KEY (`user_case_id`) REFERENCES `user_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicles_chk_1` CHECK (json_valid(`vin_decoded_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2025_01_23_192140_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2025_01_23_210755_create_dmv_forms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_04_26_191751_create_uploaded_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_04_29_205456_create_user_form_schedulings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_05_02_202107_add_admin_to_user_type_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_05_09_214455_create_user_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_05_10_153222_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_05_10_223825_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_04_08_000001_add_inspector_role_to_users',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_04_08_000002_create_vehicles_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_04_08_000003_create_inspections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_04_08_000004_create_components_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_04_08_000005_create_inspection_photos_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_04_08_000006_create_case_timeline_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_04_08_000007_improve_user_cases_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_04_08_000008_improve_schedulings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_04_08_000009_create_messages_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_04_08_000010_create_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_04_08_000011_improve_notifications_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_04_09_000001_improve_uploaded_files_table',3);
