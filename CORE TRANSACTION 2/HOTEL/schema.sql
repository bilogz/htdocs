-- Hotel Management System Schema (based on provided ERD)
-- DB: MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `hotel_core` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_core`;

-- Drop in dependency order
DROP TABLE IF EXISTS `billing`;
DROP TABLE IF EXISTS `front_office`;
DROP TABLE IF EXISTS `housekeeping_laundry`;
DROP TABLE IF EXISTS `booking`;
DROP TABLE IF EXISTS `facilities_management`;
DROP TABLE IF EXISTS `reservation`;
DROP TABLE IF EXISTS `supplier_management`;
DROP TABLE IF EXISTS `core_human_capital_management`;
DROP TABLE IF EXISTS `room_facilities`;
DROP TABLE IF EXISTS `customer_guest_management`;

-- 1) customer_guest_management
CREATE TABLE `customer_guest_management` (
  `guest_id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `contact_no` VARCHAR(30) NULL,
  `email` VARCHAR(120) NULL,
  `address` VARCHAR(255) NULL,
  PRIMARY KEY (`guest_id`),
  UNIQUE KEY `uk_cgm_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) room_facilities
CREATE TABLE `room_facilities` (
  `room_id` VARCHAR(36) NOT NULL,
  `room_type` VARCHAR(60) NOT NULL,
  `capacity` INT NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `facility_name` VARCHAR(100) NULL,
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) core_human_capital_management
CREATE TABLE `core_human_capital_management` (
  `staff_id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(60) NOT NULL,
  `salary` DECIMAL(12,2) NULL,
  `shift` VARCHAR(40) NULL,
  PRIMARY KEY (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) supplier_management
CREATE TABLE `supplier_management` (
  `supplier_id` VARCHAR(36) NOT NULL,
  `supplier_name` VARCHAR(120) NOT NULL,
  `contact` VARCHAR(120) NULL,
  `item_provided` VARCHAR(120) NULL,
  `delivery_date` DATE NULL,
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) facilities_management (FK supplier_id)
CREATE TABLE `facilities_management` (
  `facility_id` VARCHAR(36) NOT NULL,
  `supplier_id` VARCHAR(36) NOT NULL,
  `facility_type` VARCHAR(100) NOT NULL,
  `maintenance_schedule` VARCHAR(100) NULL,
  `status` VARCHAR(30) NOT NULL,
  PRIMARY KEY (`facility_id`),
  KEY `idx_facilities_supplier` (`supplier_id`),
  CONSTRAINT `fk_facilities_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier_management` (`supplier_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) reservation (FK room_id, guest_id)
CREATE TABLE `reservation` (
  `reservation_id` VARCHAR(36) NOT NULL,
  `room_id` VARCHAR(36) NOT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `guest_id` VARCHAR(36) NOT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_reservation_room` (`room_id`),
  KEY `idx_reservation_guest` (`guest_id`),
  CONSTRAINT `fk_reservation_room` FOREIGN KEY (`room_id`) REFERENCES `room_facilities` (`room_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reservation_guest` FOREIGN KEY (`guest_id`) REFERENCES `customer_guest_management` (`guest_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) booking (FK reservation_id)
CREATE TABLE `booking` (
  `booking_id` VARCHAR(36) NOT NULL,
  `reservation_id` VARCHAR(36) NOT NULL,
  `booking_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `uk_booking_reservation` (`reservation_id`),
  CONSTRAINT `fk_booking_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`reservation_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8) housekeeping_laundry (FK room_id, staff_id)
CREATE TABLE `housekeeping_laundry` (
  `hk_id` VARCHAR(36) NOT NULL,
  `task_assigned` VARCHAR(120) NULL,
  `laundry_status` VARCHAR(30) NULL,
  `shift_time` VARCHAR(40) NULL,
  `room_id` VARCHAR(36) NOT NULL,
  `staff_id` VARCHAR(36) NOT NULL,
  PRIMARY KEY (`hk_id`),
  KEY `idx_hk_room` (`room_id`),
  KEY `idx_hk_staff` (`staff_id`),
  CONSTRAINT `fk_hk_room` FOREIGN KEY (`room_id`) REFERENCES `room_facilities` (`room_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_hk_staff` FOREIGN KEY (`staff_id`) REFERENCES `core_human_capital_management` (`staff_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) front_office (FK reservation_id, staff_id, guest_id)
CREATE TABLE `front_office` (
  `office_id` VARCHAR(36) NOT NULL,
  `reservation_id` VARCHAR(36) NOT NULL,
  `staff_id` VARCHAR(36) NOT NULL,
  `office_name` VARCHAR(100) NULL,
  `shift_schedule` VARCHAR(60) NULL,
  `action_log` TEXT NULL,
  `guest_id` VARCHAR(36) NOT NULL,
  PRIMARY KEY (`office_id`),
  KEY `idx_front_office_reservation` (`reservation_id`),
  KEY `idx_front_office_staff` (`staff_id`),
  KEY `idx_front_office_guest` (`guest_id`),
  CONSTRAINT `fk_front_office_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`reservation_id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_front_office_staff` FOREIGN KEY (`staff_id`) REFERENCES `core_human_capital_management` (`staff_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_front_office_guest` FOREIGN KEY (`guest_id`) REFERENCES `customer_guest_management` (`guest_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10) billing (FK booking_id, guest_id)
CREATE TABLE `billing` (
  `bill_id` VARCHAR(36) NOT NULL,
  `booking_id` VARCHAR(36) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_status` VARCHAR(30) NOT NULL,
  `payment_date` DATE NULL,
  `guest_id` VARCHAR(36) NOT NULL,
  PRIMARY KEY (`bill_id`),
  KEY `idx_billing_booking` (`booking_id`),
  KEY `idx_billing_guest` (`guest_id`),
  CONSTRAINT `fk_billing_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_billing_guest` FOREIGN KEY (`guest_id`) REFERENCES `customer_guest_management` (`guest_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


