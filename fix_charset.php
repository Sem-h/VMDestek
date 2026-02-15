<?php
/**
 * VMDestek - Charset Fix Script
 * Bu script mevcut veritabanı ve tabloların charset'ini utf8mb4'e dönüştürür.
 * Kullanım: Tarayıcıda bu dosyayı açın, sorun düzeldikten sonra silin.
 */

require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    echo "<h2>VMDestek - Charset Düzeltme</h2><pre>";

    // 1. Veritabanı charset'ini değiştir
    $dbName = DB_NAME;
    $pdo->exec("ALTER DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Veritabanı '{$dbName}' charset'i utf8mb4_unicode_ci olarak güncellendi.\n";

    // 2. Tüm tabloları dönüştür
    $tables = ['admins', 'conversations', 'messages', 'canned_responses', 'settings', 'conversation_notes', 'conversation_reminders'];

    foreach ($tables as $table) {
        try {
            $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✅ Tablo '{$table}' utf8mb4_unicode_ci olarak dönüştürüldü.\n";
        } catch (PDOException $e) {
            echo "⚠️ Tablo '{$table}' dönüştürülemedi: " . $e->getMessage() . "\n";
        }
    }

    // 3. Mevcut bozuk Türkçe ayar verilerini düzelt (? olan karakterleri fixle)
    $fixes = [
        ['welcome_message', 'Merhaba! Size nasıl yardımcı olabiliriz?'],
        ['offline_message', 'Şu anda çevrimdışıyız. Lütfen mesajınızı bırakın, en kısa sürede dönüş yapacağız.'],
        ['auto_reply_message', 'Mesajınız alındı. Bir temsilci en kısa sürede size bağlanacak.'],
    ];

    $stmt = $pdo->prepare("UPDATE `settings` SET `setting_value` = ? WHERE `setting_key` = ?");
    foreach ($fixes as [$key, $value]) {
        // Sadece bozuk olanları güncelle
        $current = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = " . $pdo->quote($key))->fetchColumn();
        if ($current !== false && strpos($current, '?') !== false) {
            $stmt->execute([$value, $key]);
            echo "✅ Ayar '{$key}' düzeltildi.\n";
        } else {
            echo "ℹ️ Ayar '{$key}' zaten doğru.\n";
        }
    }

    // Hazır yanıtları düzelt
    $cannedFixes = [
        ['/hos', 'Hoşgeldiniz', 'Merhaba! Hoş geldiniz. Size nasıl yardımcı olabilirim?', 'Karşılama'],
        ['/bekle', 'Bekleyin', 'Lütfen bir dakika bekleyin, kontrol ediyorum.', 'Genel'],
        ['/tesekkur', 'Teşekkürler', 'Yardımcı olabildiğime sevindim. Başka bir sorunuz var mı?', 'Kapanış'],
        ['/iletisim', 'İletişim', 'Detaylı bilgi için bize info@sirket.com adresinden ulaşabilirsiniz.', 'Bilgi'],
        ['/bb', 'Güle Güle', 'İyi günler dilerim! Tekrar bekleriz.', 'Kapanış'],
    ];

    $stmt = $pdo->prepare("UPDATE `canned_responses` SET `title` = ?, `message` = ?, `category` = ? WHERE `shortcut` = ?");
    foreach ($cannedFixes as [$shortcut, $title, $message, $category]) {
        $stmt->execute([$title, $message, $category, $shortcut]);
        echo "✅ Hazır yanıt '{$shortcut}' düzeltildi.\n";
    }

    echo "\n🎉 Tüm düzeltmeler tamamlandı!\n";
    echo "⚠️ GÜVENLİK: Bu dosyayı sunucudan silmeyi unutmayın!\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "<h2>Hata</h2><pre>" . $e->getMessage() . "</pre>";
}
