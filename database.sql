-- VMDestek Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS `livesupport` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `livesupport`;

-- Admin kullanıcıları
CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin','operator') NOT NULL DEFAULT 'operator',
    `is_online` TINYINT(1) NOT NULL DEFAULT 0,
    `last_seen` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Konuşmalar
CREATE TABLE `conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `visitor_name` VARCHAR(100) NOT NULL,
    `visitor_email` VARCHAR(100) DEFAULT NULL,
    `visitor_ip` VARCHAR(45) DEFAULT NULL,
    `visitor_user_agent` TEXT DEFAULT NULL,
    `visitor_page` VARCHAR(500) DEFAULT NULL,
    `site_url` VARCHAR(500) DEFAULT NULL,
    `session_id` VARCHAR(64) NOT NULL UNIQUE,
    `assigned_admin_id` INT DEFAULT NULL,
    `department` VARCHAR(50) DEFAULT 'Genel',
    `status` ENUM('waiting','active','closed') NOT NULL DEFAULT 'waiting',
    `rating` TINYINT DEFAULT NULL,
    `rating_comment` TEXT DEFAULT NULL,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ended_at` DATETIME DEFAULT NULL,
    `last_message_at` DATETIME DEFAULT NULL,
    `is_visitor_typing` TINYINT(1) NOT NULL DEFAULT 0,
    `is_admin_typing` TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (`assigned_admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Mesajlar
CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `sender_type` ENUM('visitor','admin','system') NOT NULL,
    `sender_name` VARCHAR(100) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `message_type` ENUM('text','image','file','system') NOT NULL DEFAULT 'text',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Hazır yanıtlar
CREATE TABLE `canned_responses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `shortcut` VARCHAR(20) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT 'Genel',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sistem ayarları
CREATE TABLE `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================
-- Default Data
-- ========================

-- Varsayılan admin (şifre: admin123)
INSERT INTO `admins` (`username`, `password_hash`, `name`, `email`, `role`) VALUES
('admin', '$2y$10$5NF0UAXE/AK1GeyNp6LNCuzJIkoiBznGAeBUwgZn3B3483OsUgW1u', 'Admin', 'admin@livesupport.com', 'admin');

-- Varsayılan ayarlar
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_title', 'VMDestek'),
('welcome_message', 'Merhaba! Size nasıl yardımcı olabiliriz?'),
('offline_message', 'Şu anda çevrimdışıyız. Lütfen mesajınızı bırakın, en kısa sürede dönüş yapacağız.'),
('widget_color', '#667eea'),
('widget_gradient_end', '#764ba2'),
('widget_position', 'right'),
('working_hours_start', '09:00'),
('working_hours_end', '18:00'),
('working_days', '1,2,3,4,5'),
('auto_reply_enabled', '1'),
('auto_reply_message', 'Mesajınız alındı. Bir temsilci en kısa sürede size bağlanacak.'),
('sound_enabled', '1'),
('max_file_size', '5242880'),
('company_name', 'VMDestek'),
('company_logo', '');

-- Varsayılan hazır yanıtlar
INSERT INTO `canned_responses` (`title`, `message`, `shortcut`, `category`) VALUES
('Hoşgeldiniz', 'Merhaba! Hoş geldiniz. Size nasıl yardımcı olabilirim?', '/hos', 'Karşılama'),
('Bekleyin', 'Lütfen bir dakika bekleyin, kontrol ediyorum.', '/bekle', 'Genel'),
('Teşekkürler', 'Yardımcı olabildiğime sevindim. Başka bir sorunuz var mı?', '/tesekkur', 'Kapanış'),
('İletişim', 'Detaylı bilgi için bize info@sirket.com adresinden ulaşabilirsiniz.', '/iletisim', 'Bilgi'),
('Güle Güle', 'İyi günler dilerim! Tekrar bekleriz.', '/bb', 'Kapanış');

-- Konuşma notları
CREATE TABLE `conversation_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `admin_id` INT NOT NULL,
    `admin_name` VARCHAR(100) NOT NULL,
    `note` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Hatırlatmalar
CREATE TABLE `conversation_reminders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `admin_id` INT NOT NULL,
    `admin_name` VARCHAR(100) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `remind_at` DATETIME NOT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- İndeksler
CREATE INDEX `idx_conversations_status` ON `conversations`(`status`);
CREATE INDEX `idx_conversations_session` ON `conversations`(`session_id`);
CREATE INDEX `idx_conversations_last_message` ON `conversations`(`last_message_at`);
CREATE INDEX `idx_messages_conversation` ON `messages`(`conversation_id`);
CREATE INDEX `idx_messages_created` ON `messages`(`created_at`);
CREATE INDEX `idx_messages_read` ON `messages`(`is_read`);
CREATE INDEX `idx_notes_conversation` ON `conversation_notes`(`conversation_id`);
CREATE INDEX `idx_reminders_conversation` ON `conversation_reminders`(`conversation_id`);
CREATE INDEX `idx_reminders_admin` ON `conversation_reminders`(`admin_id`, `is_completed`);
