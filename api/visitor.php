<?php
/**
 * VMDestek - Visitor Tracking API
 * Tracks real-time visitors on sites with the embed script.
 */

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auto-create visitors table if not exists
try {
    db()->query("CREATE TABLE IF NOT EXISTS `visitors` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `visitor_uid` VARCHAR(64) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `page_url` VARCHAR(500) DEFAULT NULL,
        `page_title` VARCHAR(200) DEFAULT NULL,
        `referrer` VARCHAR(500) DEFAULT NULL,
        `user_agent` VARCHAR(500) DEFAULT NULL,
        `first_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_visitor_uid` (`visitor_uid`),
        KEY `idx_last_seen` (`last_seen`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    // Table likely already exists
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'heartbeat':
        handleHeartbeat();
        break;
    case 'list':
        listVisitors();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function handleHeartbeat()
{
    $input = json_decode(file_get_contents('php://input'), true);

    $visitorUid = $input['visitor_uid'] ?? '';
    $pageUrl = $input['page_url'] ?? '';
    $pageTitle = $input['page_title'] ?? '';
    $referrer = $input['referrer'] ?? '';

    if (empty($visitorUid)) {
        echo json_encode(['error' => 'visitor_uid required']);
        return;
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Upsert: insert or update on duplicate key
    $sql = "INSERT INTO visitors (visitor_uid, ip_address, page_url, page_title, referrer, user_agent, first_seen, last_seen)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                page_url = VALUES(page_url),
                page_title = VALUES(page_title),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                last_seen = NOW()";

    db()->query($sql, [$visitorUid, $ip, $pageUrl, $pageTitle, $referrer, $userAgent]);

    echo json_encode(['success' => true]);
}

function listVisitors()
{
    // Return visitors seen in the last 30 seconds (active)
    $visitors = db()->fetchAll(
        "SELECT *, TIMESTAMPDIFF(SECOND, first_seen, NOW()) as duration_seconds 
         FROM visitors 
         WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
         ORDER BY last_seen DESC"
    );

    $total = db()->fetch("SELECT COUNT(*) as cnt FROM visitors WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 30 SECOND)");

    echo json_encode([
        'success' => true,
        'visitors' => $visitors,
        'total' => (int) ($total['cnt'] ?? 0)
    ]);
}
