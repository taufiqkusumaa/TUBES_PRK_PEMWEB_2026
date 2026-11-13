CREATE DATABASE IF NOT EXISTS labcat_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE labcat_db;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    equipment_name VARCHAR(150) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    laboratory_name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    description TEXT NULL,
    specifications TEXT NULL,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_equipment_name (equipment_name),
    INDEX idx_equipment_code (equipment_code),
    INDEX idx_equipment_category (category_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipment_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_images_equipment
        FOREIGN KEY (equipment_id) REFERENCES equipment(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_equipment_image_path (equipment_id, image_path),
    INDEX idx_equipment_images_order (equipment_id, sort_order)
) ENGINE=InnoDB;
