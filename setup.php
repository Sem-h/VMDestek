<?php
require_once __DIR__ . '/db.php';

// Create notes table
db()->query("CREATE TABLE IF NOT EXISTS `conversation_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `admin_id` INT NOT NULL,
    `admin_name` VARCHAR(100) NOT NULL,
    `note` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB");

// Create reminders table
db()->query("CREATE TABLE IF NOT EXISTS `conversation_reminders` (
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
) ENGINE=InnoDB");

echo "Tables created successfully!\n";
