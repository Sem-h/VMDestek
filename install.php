<?php
/**
 * VMDestek - Kurulum Sihirbazı
 * Bu dosya kurulumdan sonra otomatik olarak kilitlenir.
 * 
 * © 2026 Semih AKBAŞ - semihakbas.com.tr
 */

// Eğer install.lock varsa kurulumu engelle
if (file_exists(__DIR__ . '/install.lock')) {
    die('
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <title>Kurulum Tamamlandı</title>
        <style>
            body { font-family: "Inter", "Segoe UI", sans-serif; background: #0f1629; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .box { background: rgba(30,41,59,0.8); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 48px; text-align: center; max-width: 460px; }
            .box i { font-size: 48px; color: #10b981; margin-bottom: 16px; }
            .box h1 { font-size: 22px; margin: 0 0 12px; }
            .box p { color: #94a3b8; font-size: 14px; line-height: 1.6; }
            .box a { color: #667eea; text-decoration: none; font-weight: 600; }
        </style>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="box">
            <i class="fas fa-check-circle"></i>
            <h1>VMDestek Zaten Kurulu</h1>
            <p>Kurulum daha önce tamamlanmıştır. Güvenlik nedeniyle kurulum sihirbazı kilitlenmiştir.</p>
            <p style="margin-top:20px"><a href="admin/"><i class="fas fa-arrow-right"></i> Admin Paneline Git</a></p>
        </div>
    </body>
    </html>');
}

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$error = '';
$success = '';

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'check_db') {
        // Veritabanı bağlantısını test et
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';

        if (empty($dbName) || empty($dbUser)) {
            $error = 'Veritabanı adı ve kullanıcı adı gerekli.';
            $step = 2;
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$dbHost};charset=utf8mb4",
                    $dbUser,
                    $dbPass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Veritabanını oluştur
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");

                // Tabloları oluştur
                $tables = "
                CREATE TABLE IF NOT EXISTS `admins` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `conversations` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `messages` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `conversation_id` INT NOT NULL,
                    `sender_type` ENUM('visitor','admin','system') NOT NULL,
                    `sender_name` VARCHAR(100) DEFAULT NULL,
                    `message` TEXT NOT NULL,
                    `message_type` ENUM('text','image','file','system') NOT NULL DEFAULT 'text',
                    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `canned_responses` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(100) NOT NULL,
                    `message` TEXT NOT NULL,
                    `shortcut` VARCHAR(20) DEFAULT NULL,
                    `category` VARCHAR(50) DEFAULT 'Genel',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `settings` (
                    `setting_key` VARCHAR(50) PRIMARY KEY,
                    `setting_value` TEXT NOT NULL,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `conversation_notes` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `conversation_id` INT NOT NULL,
                    `admin_id` INT NOT NULL,
                    `admin_name` VARCHAR(100) NOT NULL,
                    `note` TEXT NOT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `conversation_reminders` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";

                // Her tabloyu ayrı ayrı çalıştır
                foreach (explode(';', $tables) as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $pdo->exec($query);
                    }
                }

                // İndeksleri ekle (hata varsa atla - zaten var olabilir)
                $indexes = [
                    "CREATE INDEX `idx_conversations_status` ON `conversations`(`status`)",
                    "CREATE INDEX `idx_conversations_session` ON `conversations`(`session_id`)",
                    "CREATE INDEX `idx_conversations_last_message` ON `conversations`(`last_message_at`)",
                    "CREATE INDEX `idx_messages_conversation` ON `messages`(`conversation_id`)",
                    "CREATE INDEX `idx_messages_created` ON `messages`(`created_at`)",
                    "CREATE INDEX `idx_messages_read` ON `messages`(`is_read`)",
                    "CREATE INDEX `idx_notes_conversation` ON `conversation_notes`(`conversation_id`)",
                    "CREATE INDEX `idx_reminders_conversation` ON `conversation_reminders`(`conversation_id`)",
                    "CREATE INDEX `idx_reminders_admin` ON `conversation_reminders`(`admin_id`, `is_completed`)",
                ];

                foreach ($indexes as $idx) {
                    try {
                        $pdo->exec($idx);
                    } catch (Exception $e) {
                        // İndeks zaten varsa atla
                    }
                }

                // Varsayılan ayarları ekle
                $defaults = [
                    ['site_title', 'VMDestek'],
                    ['welcome_message', 'Merhaba! Size nasıl yardımcı olabiliriz?'],
                    ['offline_message', 'Şu anda çevrimdışıyız. Lütfen mesajınızı bırakın, en kısa sürede dönüş yapacağız.'],
                    ['widget_color', '#667eea'],
                    ['widget_gradient_end', '#764ba2'],
                    ['widget_position', 'right'],
                    ['working_hours_start', '09:00'],
                    ['working_hours_end', '18:00'],
                    ['working_days', '1,2,3,4,5'],
                    ['auto_reply_enabled', '1'],
                    ['auto_reply_message', 'Mesajınız alındı. Bir temsilci en kısa sürede size bağlanacak.'],
                    ['sound_enabled', '1'],
                    ['max_file_size', '5242880'],
                    ['company_name', 'VMDestek'],
                    ['company_logo', ''],
                ];

                $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
                foreach ($defaults as $d) {
                    $stmt->execute($d);
                }

                // Varsayılan hazır yanıtlar
                $cannedCount = $pdo->query("SELECT COUNT(*) FROM canned_responses")->fetchColumn();
                if ($cannedCount == 0) {
                    $canneds = [
                        ['Hoşgeldiniz', 'Merhaba! Hoş geldiniz. Size nasıl yardımcı olabilirim?', '/hos', 'Karşılama'],
                        ['Bekleyin', 'Lütfen bir dakika bekleyin, kontrol ediyorum.', '/bekle', 'Genel'],
                        ['Teşekkürler', 'Yardımcı olabildiğime sevindim. Başka bir sorunuz var mı?', '/tesekkur', 'Kapanış'],
                        ['İletişim', 'Detaylı bilgi için bize info@sirket.com adresinden ulaşabilirsiniz.', '/iletisim', 'Bilgi'],
                        ['Güle Güle', 'İyi günler dilerim! Tekrar bekleriz.', '/bb', 'Kapanış'],
                    ];

                    $stmt = $pdo->prepare("INSERT INTO `canned_responses` (`title`, `message`, `shortcut`, `category`) VALUES (?, ?, ?, ?)");
                    foreach ($canneds as $c) {
                        $stmt->execute($c);
                    }
                }

                // Session'a DB bilgilerini kaydet
                session_start();
                $_SESSION['install_db'] = [
                    'host' => $dbHost,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass,
                ];

                $step = 3;
            } catch (PDOException $e) {
                $error = 'Veritabanı bağlantı hatası: ' . $e->getMessage();
                $step = 2;
            }
        }
    } elseif ($action === 'create_admin') {
        session_start();
        $dbInfo = $_SESSION['install_db'] ?? null;

        if (!$dbInfo) {
            $error = 'Oturum süresi dolmuş. Lütfen 2. adıma geri dönün.';
            $step = 2;
        } else {
            $adminName = trim($_POST['admin_name'] ?? '');
            $adminUser = trim($_POST['admin_user'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass = $_POST['admin_pass'] ?? '';
            $adminPass2 = $_POST['admin_pass2'] ?? '';
            $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');

            if (empty($adminName) || empty($adminUser) || empty($adminPass)) {
                $error = 'Ad Soyad, kullanıcı adı ve şifre gerekli.';
                $step = 3;
            } elseif (strlen($adminPass) < 6) {
                $error = 'Şifre en az 6 karakter olmalı.';
                $step = 3;
            } elseif ($adminPass !== $adminPass2) {
                $error = 'Şifreler eşleşmiyor.';
                $step = 3;
            } elseif (empty($siteUrl)) {
                $error = 'Site URL gerekli.';
                $step = 3;
            } else {
                try {
                    $pdo = new PDO(
                        "mysql:host={$dbInfo['host']};dbname={$dbInfo['name']};charset=utf8mb4",
                        $dbInfo['user'],
                        $dbInfo['pass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

                    // Admin kullanıcı oluştur
                    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password_hash`, `name`, `email`, `role`) VALUES (?, ?, ?, ?, 'admin')");
                    $stmt->execute([$adminUser, $hash, $adminName, $adminEmail]);

                    // config.php oluştur
                    $configContent = '<?php
/**
 * VMDestek - Configuration
 * Bu dosya kurulum sihirbazı tarafından oluşturulmuştur.
 * © ' . date('Y') . ' Semih AKBAŞ - semihakbas.com.tr
 */

// Error reporting
error_reporting(E_ALL);
ini_set(\'display_errors\', 0);
ini_set(\'log_errors\', 1);

// Timezone
date_default_timezone_set(\'Europe/Istanbul\');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database
define(\'DB_HOST\', \'' . addslashes($dbInfo['host']) . '\');
define(\'DB_NAME\', \'' . addslashes($dbInfo['name']) . '\');
define(\'DB_USER\', \'' . addslashes($dbInfo['user']) . '\');
define(\'DB_PASS\', \'' . addslashes($dbInfo['pass']) . '\');
define(\'DB_CHARSET\', \'utf8mb4\');

// Site
define(\'SITE_URL\', \'' . addslashes($siteUrl) . '\');
define(\'SITE_TITLE\', \'VMDestek\');

// Polling
define(\'POLL_TIMEOUT\', 25);
define(\'POLL_INTERVAL\', 1);

// File uploads
define(\'MAX_FILE_SIZE\', 5 * 1024 * 1024); // 5MB
define(\'UPLOAD_DIR\', __DIR__ . \'/uploads/\');

// CORS
header(\'Access-Control-Allow-Origin: *\');
header(\'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS\');
header(\'Access-Control-Allow-Headers: Content-Type, Authorization\');

if ($_SERVER[\'REQUEST_METHOD\'] === \'OPTIONS\') {
    http_response_code(200);
    exit;
}
';

                    file_put_contents(__DIR__ . '/config.php', $configContent);

                    // uploads klasörünü oluştur
                    if (!is_dir(__DIR__ . '/uploads')) {
                        mkdir(__DIR__ . '/uploads', 0755, true);
                    }

                    // install.lock dosyası oluştur
                    file_put_contents(__DIR__ . '/install.lock', 'Kurulum: ' . date('Y-m-d H:i:s') . "\nAdmin: " . $adminUser);

                    // Session temizle
                    unset($_SESSION['install_db']);

                    $step = 4;
                    $success = 'Kurulum başarıyla tamamlandı!';
                } catch (PDOException $e) {
                    $error = 'Hata: ' . $e->getMessage();
                    $step = 3;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMDestek - Kurulum Sihirbazı</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #0a0e1a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(102, 126, 234, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(118, 75, 162, 0.06) 0%, transparent 60%),
                radial-gradient(ellipse at 50% 80%, rgba(102, 126, 234, 0.04) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .installer {
            width: 100%;
            max-width: 640px;
            position: relative;
            z-index: 1;
        }

        .installer-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .installer-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin: 0 auto 16px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }

        .installer-header h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .installer-header p {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Steps indicator */
        .steps {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 32px;
        }

        .step-dot {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: #64748b;
            transition: all 0.3s;
        }

        .step-dot.active .step-num {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }

        .step-dot.completed .step-num {
            background: #10b981;
            border-color: transparent;
            color: white;
        }

        .step-line {
            width: 40px;
            height: 2px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2px;
        }

        .step-dot.completed+.step-line,
        .step-dot.completed+.step-line+.step-dot .step-line {
            background: #10b981;
        }

        /* Card */
        .card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .card h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .card .subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 24px;
        }

        /* Form */
        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            color: #e2e8f0;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .field input:focus {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.06);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .field input::placeholder {
            color: #475569;
        }

        .field-row {
            display: flex;
            gap: 12px;
        }

        .field-row .field {
            flex: 1;
        }

        .field-hint {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 28px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(16, 185, 129, 0.4);
        }

        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        /* Requirements */
        .req-list {
            list-style: none;
        }

        .req-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .req-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        .req-icon.pass {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .req-icon.fail {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .req-name {
            flex: 1;
            font-weight: 500;
        }

        .req-value {
            color: #94a3b8;
            font-size: 12px;
        }

        /* Alert */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        /* Complete */
        .complete-screen {
            text-align: center;
            padding: 20px 0;
        }

        .complete-icon {
            width: 80px;
            height: 80px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #10b981;
            margin: 0 auto 24px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.3);
            }

            70% {
                box-shadow: 0 0 0 20px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .complete-screen h2 {
            font-size: 22px;
            margin-bottom: 8px;
            color: #10b981;
        }

        .complete-screen p {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
        }

        .complete-links {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 28px;
        }

        .complete-links a {
            text-decoration: none;
        }

        .footer-credit {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #475569;
        }

        .footer-credit a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="installer">
        <div class="installer-header">
            <div class="installer-logo">
                <i class="fas fa-headset"></i>
            </div>
            <h1>VMDestek Kurulum</h1>
            <p>Canlı destek sisteminizi birkaç adımda kurun</p>
        </div>

        <!-- Steps -->
        <div class="steps">
            <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                <div class="step-num">
                    <?= $step > 1 ? '<i class="fas fa-check"></i>' : '1' ?>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                <div class="step-num">
                    <?= $step > 2 ? '<i class="fas fa-check"></i>' : '2' ?>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step-dot <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">
                <div class="step-num">
                    <?= $step > 3 ? '<i class="fas fa-check"></i>' : '3' ?>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step-dot <?= $step >= 4 ? 'active' : '' ?>">
                <div class="step-num">
                    <?= $step >= 4 ? '<i class="fas fa-check"></i>' : '4' ?>
                </div>
            </div>
        </div>

        <div class="card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- Step 1: Requirements Check -->
                <?php
                $phpVersion = PHP_VERSION;
                $phpOk = version_compare($phpVersion, '7.4.0', '>=');
                $pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
                $mbOk = extension_loaded('mbstring');
                $jsonOk = extension_loaded('json');
                $sessionOk = extension_loaded('session');
                $configWritable = is_writable(__DIR__);
                $allOk = $phpOk && $pdoOk && $mbOk && $jsonOk && $sessionOk && $configWritable;
                ?>
                <h2>Sistem Gereksinimleri</h2>
                <p class="subtitle">Sunucunuzun VMDestek gereksinimlerini karşılayıp karşılamadığını kontrol edin</p>

                <ul class="req-list">
                    <li class="req-item">
                        <div class="req-icon <?= $phpOk ? 'pass' : 'fail' ?>">
                            <i class="fas <?= $phpOk ? 'fa-check' : 'fa-times' ?>"></i>
                        </div>
                        <span class="req-name">PHP Sürümü</span>
                        <span class="req-value">
                            <?= $phpVersion ?> (≥ 7.4 gerekli)
                        </span>
                    </li>
                    <li class="req-item">
                        <div class="req-icon <?= $pdoOk ? 'pass' : 'fail' ?>">
                            <i class="fas <?= $pdoOk ? 'fa-check' : 'fa-times' ?>"></i>
                        </div>
                        <span class="req-name">PDO MySQL</span>
                        <span class="req-value">
                            <?= $pdoOk ? 'Aktif' : 'Eksik' ?>
                        </span>
                    </li>
                    <li class="req-item">
                        <div class="req-icon <?= $mbOk ? 'pass' : 'fail' ?>">
                            <i class="fas <?= $mbOk ? 'fa-check' : 'fa-times' ?>"></i>
                        </div>
                        <span class="req-name">mbstring</span>
                        <span class="req-value">
                            <?= $mbOk ? 'Aktif' : 'Eksik' ?>
                        </span>
                    </li>
                    <li class="req-item">
                        <div class="req-icon <?= $jsonOk ? 'pass' : 'fail' ?>">
                            <i class="fas <?= $jsonOk ? 'fa-check' : 'fa-times' ?>"></i>
                        </div>
                        <span class="req-name">JSON</span>
                        <span class="req-value">
                            <?= $jsonOk ? 'Aktif' : 'Eksik' ?>
                        </span>
                    </li>
                    <li class="req-item">
                        <div class="req-icon <?= $sessionOk ? 'pass' : 'fail' ?>">
                            <i class="fas <?= $sessionOk ? 'fa-check' : 'fa-times' ?>"></i>
                        </div>
                        <span class="req-name">Session</span>
                        <span class="req-value">
                            <?= $sessionOk ? 'Aktif' : 'Eksik' ?>
                        </span>
                    </li>
                    <li class="req-item">
                        <div class="req-icon <?= $configWritable ? 'pass' : 'fail' ?>">
                            <i class="fas <?= $configWritable ? 'fa-check' : 'fa-times' ?>"></i>
                        </div>
                        <span class="req-name">Dizin Yazma İzni</span>
                        <span class="req-value">
                            <?= $configWritable ? 'Yazılabilir' : 'Yazılamıyor' ?>
                        </span>
                    </li>
                </ul>

                <div class="btn-group">
                    <?php if ($allOk): ?>
                        <a href="?step=2" class="btn btn-primary">
                            Devam Et <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary" disabled style="opacity:0.5;cursor:not-allowed">
                            Gereksinimler karşılanmıyor
                        </button>
                    <?php endif; ?>
                </div>

            <?php elseif ($step === 2): ?>
                <!-- Step 2: Database -->
                <h2>Veritabanı Ayarları</h2>
                <p class="subtitle">MySQL/MariaDB veritabanı bağlantı bilgilerinizi girin</p>

                <form method="POST">
                    <input type="hidden" name="action" value="check_db">

                    <div class="field">
                        <label>Veritabanı Sunucusu</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"
                            placeholder="localhost">
                    </div>

                    <div class="field">
                        <label>Veritabanı Adı</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'vmdestek') ?>"
                            placeholder="vmdestek">
                        <p class="field-hint">Veritabanı yoksa otomatik oluşturulur</p>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label>Kullanıcı Adı</label>
                            <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>"
                                placeholder="root">
                        </div>
                        <div class="field">
                            <label>Şifre</label>
                            <input type="password" name="db_pass" value="" placeholder="Veritabanı şifresi">
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="?step=1" class="btn" style="color:#94a3b8">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Bağlantıyı Test Et ve Kur <i class="fas fa-database"></i>
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <!-- Step 3: Admin Account -->
                <?php
                // Site URL'yi otomatik tespit et
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $path = dirname($_SERVER['SCRIPT_NAME']);
                $detectedUrl = $protocol . '://' . $host . rtrim($path, '/');
                ?>
                <h2>Admin Hesabı</h2>
                <p class="subtitle">İlk yönetici hesabınızı oluşturun ve site URL'nizi belirleyin</p>

                <form method="POST">
                    <input type="hidden" name="action" value="create_admin">

                    <div class="field">
                        <label>Site URL</label>
                        <input type="text" name="site_url"
                            value="<?= htmlspecialchars($_POST['site_url'] ?? $detectedUrl) ?>"
                            placeholder="https://siteadresiniz.com/livesupport">
                        <p class="field-hint">VMDestek'in kurulu olduğu tam adres (sondaki / olmadan)</p>
                    </div>

                    <hr style="border:none;border-top:1px solid rgba(255,255,255,0.06);margin:20px 0">

                    <div class="field-row">
                        <div class="field">
                            <label>Ad Soyad</label>
                            <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>"
                                placeholder="Adınız Soyadınız" required>
                        </div>
                        <div class="field">
                            <label>Kullanıcı Adı</label>
                            <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>"
                                placeholder="admin" required>
                        </div>
                    </div>

                    <div class="field">
                        <label>E-posta (opsiyonel)</label>
                        <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>"
                            placeholder="admin@sirket.com">
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label>Şifre</label>
                            <input type="password" name="admin_pass" placeholder="En az 6 karakter" required>
                        </div>
                        <div class="field">
                            <label>Şifre Tekrar</label>
                            <input type="password" name="admin_pass2" placeholder="Şifreyi tekrar girin" required>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="?step=2" class="btn" style="color:#94a3b8">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                        <button type="submit" class="btn btn-success">
                            Kurulumu Tamamla <i class="fas fa-check"></i>
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 4): ?>
                <!-- Step 4: Complete -->
                <div class="complete-screen">
                    <div class="complete-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2>Kurulum Tamamlandı!</h2>
                    <p>VMDestek başarıyla kuruldu. Artık admin paneline giriş yaparak<br>canlı destek sisteminizi kullanmaya
                        başlayabilirsiniz.</p>

                    <div class="complete-links">
                        <a href="admin/" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Admin Paneli
                        </a>
                    </div>

                    <p style="margin-top:20px;font-size:12px;color:#64748b">
                        <i class="fas fa-lock"></i> Güvenlik için kurulum sihirbazı kilitlenmiştir.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-credit">
            VMDestek v1.0 &bull; Geliştirici: <a href="https://semihakbas.com.tr" target="_blank">Semih AKBAŞ</a>
        </div>
    </div>
</body>

</html>