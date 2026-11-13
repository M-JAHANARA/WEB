CREATE DATABASE IF NOT EXISTS barber_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barber_booking;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    price BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_services_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reference_code VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(25) NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    service_name VARCHAR(160) NOT NULL,
    service_price BIGINT UNSIGNED NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_appointments_start_status (starts_at, status),
    INDEX idx_appointments_time_window (starts_at, ends_at),
    INDEX idx_appointments_phone (customer_phone),
    INDEX idx_appointments_status_created (status, created_at)
) ENGINE=InnoDB;

INSERT INTO services (name, description, duration_minutes, price, sort_order)
SELECT 'اصلاح مو', 'اصلاح مرتب و متناسب با استایل شما', 30, 250000, 1
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'اصلاح مو');
INSERT INTO services (name, description, duration_minutes, price, sort_order)
SELECT 'اصلاح مو و ریش', 'رسیدگی کامل به مو و فرم ریش', 45, 350000, 2
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'اصلاح مو و ریش');
INSERT INTO services (name, description, duration_minutes, price, sort_order)
SELECT 'اصلاح ریش', 'فرم‌دهی دقیق و مرتب‌سازی ریش', 20, 150000, 3
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'اصلاح ریش');
INSERT INTO services (name, description, duration_minutes, price, sort_order)
SELECT 'پاکسازی پوست', 'مراقبت و پاکسازی متناسب با پوست', 45, 400000, 4
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'پاکسازی پوست');
INSERT INTO services (name, description, duration_minutes, price, sort_order)
SELECT 'پکیج ویژه', 'ترکیبی از خدمات منتخب آرایشگاه', 60, 600000, 5
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'پکیج ویژه');
