<?php
require_once __DIR__ . '/../config.php';

// Get settings
require_once __DIR__ . '/../db.php';
$settings = [];
$rows = db()->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($rows as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

$widgetColor = $settings['widget_color'] ?? '#667eea';
$gradientEnd = $settings['widget_gradient_end'] ?? '#764ba2';
$companyName = $settings['company_name'] ?? 'VMDestek';

// Check if any admin online
$adminOnline = db()->fetch("SELECT COUNT(*) as cnt FROM admins WHERE is_online = 1");
$isOnline = ($adminOnline['cnt'] ?? 0) > 0;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>
        <?= htmlspecialchars($companyName) ?> - VMDestek
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="css/widget.css" rel="stylesheet">
    <style>
        :root {
            --w-color:
                <?= $widgetColor ?>
            ;
            --w-gradient-end:
                <?= $gradientEnd ?>
            ;
            --w-gradient: linear-gradient(135deg,
                    <?= $widgetColor ?>
                    0%,
                    <?= $gradientEnd ?>
                    100%);
        }
    </style>
</head>

<body>
    <div class="widget-container" id="widgetContainer">
        <!-- Pre-chat form -->
        <div class="widget-screen" id="screenPreChat">
            <div class="widget-header">
                <div class="widget-header-content">
                    <div class="widget-header-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="widget-header-text">
                        <h2>
                            <?= htmlspecialchars($companyName) ?>
                        </h2>
                        <div class="widget-status">
                            <span class="status-dot <?= $isOnline ? 'online' : 'offline' ?>"></span>
                            <span>
                                <?= $isOnline ? 'Çevrimiçi' : 'Çevrimdışı' ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="widget-header-actions">
                    <button class="widget-header-btn" onclick="minimizeWidget()" title="Küçült">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="widget-header-wave">
                    <svg viewBox="0 0 400 30" preserveAspectRatio="none">
                        <path d="M0,15 C100,25 200,5 300,15 C350,20 380,18 400,15 L400,30 L0,30 Z" fill="white" />
                    </svg>
                </div>
            </div>

            <div class="widget-body prechat-body">
                <div class="prechat-welcome">
                    <div class="welcome-emoji">👋</div>
                    <h3>Merhaba!</h3>
                    <p>
                        <?= htmlspecialchars($settings['welcome_message'] ?? 'Size nasıl yardımcı olabiliriz?') ?>
                    </p>
                </div>

                <form id="preChatForm" onsubmit="startChat(event)">
                    <div class="widget-field">
                        <label><i class="fas fa-user"></i> Adınız</label>
                        <input type="text" id="visitorNameInput" placeholder="Adınızı girin" required>
                    </div>
                    <div class="widget-field">
                        <label><i class="fas fa-envelope"></i> E-posta <span
                                style="opacity:0.5">(opsiyonel)</span></label>
                        <input type="email" id="visitorEmailInput" placeholder="E-posta adresiniz">
                    </div>
                    <button type="submit" class="widget-btn-start" id="startBtn">
                        <span class="btn-content">
                            <i class="fas fa-comment-dots"></i>
                            Sohbet Başlat
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Chat screen -->
        <div class="widget-screen" id="screenChat" style="display:none">
            <div class="widget-header compact">
                <div class="widget-header-content">
                    <div class="widget-header-icon small">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="widget-header-text">
                        <h2>
                            <?= htmlspecialchars($companyName) ?>
                        </h2>
                        <div class="widget-status">
                            <span class="status-dot online"></span>
                            <span id="chatStatus">Bağlandı</span>
                        </div>
                    </div>
                </div>
                <div class="widget-header-actions">
                    <button class="widget-header-btn" onclick="minimizeWidget()" title="Küçült">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <button class="widget-header-btn" onclick="endChat()" title="Sohbeti Bitir">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="widget-messages" id="widgetMessages">
                <!-- Messages appear here -->
            </div>

            <!-- Typing indicator -->
            <div class="widget-typing" id="widgetTyping" style="display:none">
                <div class="typing-bubble">
                    <span></span><span></span><span></span>
                </div>
                <span class="typing-text">Temsilci yazıyor...</span>
            </div>

            <!-- Input -->
            <div class="widget-input-area">
                <div class="widget-input-container">
                    <textarea id="widgetInput" placeholder="Mesajınızı yazın..." rows="1"
                        onkeydown="widgetKeyDown(event)" oninput="widgetInputChange()"></textarea>
                    <button class="widget-send-btn" id="widgetSendBtn" onclick="sendWidgetMessage()" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="widget-powered">
                    <span>Powered by <strong>VMDestek</strong></span>
                </div>
            </div>
        </div>

        <!-- End screen -->
        <div class="widget-screen" id="screenEnd" style="display:none">
            <div class="widget-header">
                <div class="widget-header-content">
                    <div class="widget-header-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="widget-header-text">
                        <h2>
                            <?= htmlspecialchars($companyName) ?>
                        </h2>
                        <div class="widget-status">
                            <span>Sohbet sona erdi</span>
                        </div>
                    </div>
                </div>
                <div class="widget-header-actions">
                    <button class="widget-header-btn" onclick="minimizeWidget()" title="Küçült">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="widget-header-wave">
                    <svg viewBox="0 0 400 30" preserveAspectRatio="none">
                        <path d="M0,15 C100,25 200,5 300,15 C350,20 380,18 400,15 L400,30 L0,30 Z" fill="white" />
                    </svg>
                </div>
            </div>

            <div class="widget-body prechat-body" style="text-align:center;padding-top:40px">
                <div class="welcome-emoji">🙏</div>
                <h3>Teşekkürler!</h3>
                <p style="margin-bottom:24px">Sohbetiniz sona erdi. Nasıl bir deneyim yaşadınız?</p>

                <div class="rating-container" id="ratingContainer">
                    <div class="rating-stars" id="ratingStars">
                        <span class="star" data-rating="1" onclick="setRating(1)">★</span>
                        <span class="star" data-rating="2" onclick="setRating(2)">★</span>
                        <span class="star" data-rating="3" onclick="setRating(3)">★</span>
                        <span class="star" data-rating="4" onclick="setRating(4)">★</span>
                        <span class="star" data-rating="5" onclick="setRating(5)">★</span>
                    </div>
                </div>

                <button class="widget-btn-start" onclick="resetWidget()" style="margin-top:24px">
                    <span class="btn-content">
                        <i class="fas fa-redo"></i>
                        Yeni Sohbet
                    </span>
                </button>
            </div>
        </div>
    </div>

    <script>
        const WIDGET_URL = '<?= SITE_URL ?>';
    </script>
    <script src="js/widget.js"></script>
</body>

</html>