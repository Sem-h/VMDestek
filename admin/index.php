<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lang.php';

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$L = getAdminLang();
$langCode = getSystemLanguage();
$dir = isset($L['dir']) ? $L['dir'] : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>" dir="<?= $dir ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $L['page_title'] ?? 'VMDestek - Admin Panel' ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <span class="brand-name">VMDestek</span>
            </div>
        </div>
        <div class="topbar-center">
            <div class="stats-bar">
                <div class="stat-item online-agents-wrapper" title="<?= $L['online_agents'] ?? 'Online Temsilciler' ?>">
                    <i class="fas fa-headset" style="color: var(--success)"></i>
                    <span id="statOnlineAgents">0</span>
                    <div class="online-agents-popup" id="onlineAgentsPopup">
                        <div class="popup-title"><?= $L['online_agents'] ?? 'Online Temsilciler' ?></div>
                        <div class="popup-list" id="onlineAgentsList">
                            <div class="popup-empty"><?= $L['loading'] ?? 'Yükleniyor...' ?></div>
                        </div>
                    </div>
                </div>
                <div class="stat-item" title="<?= $L['visitors'] ?? 'Ziyaretçiler' ?>">
                    <i class="fas fa-eye"></i>
                    <span id="statVisitors">0</span>
                </div>
                <div class="stat-item" title="<?= $L['waiting'] ?? 'Bekleyen' ?>">
                    <i class="fas fa-clock"></i>
                    <span id="statWaiting">0</span>
                </div>
                <div class="stat-item" title="<?= $L['active'] ?? 'Aktif' ?>">
                    <i class="fas fa-comments"></i>
                    <span id="statActive">0</span>
                </div>
                <div class="stat-item" title="<?= $L['today'] ?? 'Bugün' ?>">
                    <i class="fas fa-calendar-day"></i>
                    <span id="statToday">0</span>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="admin-status-toggle" id="adminStatusToggle" onclick="toggleOnlineStatus()">
                <span class="status-dot online" id="adminStatusDot"></span>
                <span id="adminStatusText">
                    <?= htmlspecialchars($_SESSION['admin_name']) ?>
                </span>
                <i class="fas fa-circle-dot" id="adminStatusIcon"
                    style="font-size:10px;margin-left:4px;opacity:0.5"></i>
            </div>
            <a href="settings.php" class="topbar-btn" title="<?= $L['settings'] ?? 'Ayarlar' ?>">
                <i class="fas fa-cog"></i>
            </a>
            <button class="topbar-btn" onclick="handleLogout()" title="<?= $L['logout'] ?? 'Çıkış' ?>">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </header>

    <div class="app-container">
        <!-- Sidebar: Conversation List -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-tabs">
                    <button class="tab active" data-tab="active" onclick="switchTab(this)">
                        <i class="fas fa-comment-dots"></i> <?= $L['active_tab'] ?? 'Aktif' ?>
                        <span class="tab-badge" id="activeBadge"></span>
                    </button>
                    <button class="tab" data-tab="waiting" onclick="switchTab(this)">
                        <i class="fas fa-hourglass-half"></i> <?= $L['waiting_tab'] ?? 'Bekleyen' ?>
                        <span class="tab-badge" id="waitingBadge"></span>
                    </button>
                    <button class="tab" data-tab="closed" onclick="switchTab(this)">
                        <i class="fas fa-check-circle"></i> <?= $L['closed_tab'] ?? 'Kapanan' ?>
                    </button>
                    <button class="tab" data-tab="visitors" onclick="switchTab(this)">
                        <i class="fas fa-eye"></i> <?= $L['visitors_tab'] ?? 'Ziyaretçiler' ?>
                        <span class="tab-badge" id="visitorsBadge"></span>
                    </button>
                </div>
            </div>

            <div class="sidebar-search">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="<?= $L['search_placeholder'] ?? 'Konuşma ara...' ?>"
                        id="searchInput" oninput="filterConversations()">
                </div>
            </div>

            <div class="conversation-list" id="conversationList">
                <div class="empty-state" id="emptyState">
                    <i class="fas fa-inbox"></i>
                    <p><?= $L['no_conversations'] ?? 'Henüz konuşma yok' ?></p>
                </div>
            </div>
        </aside>

        <!-- Main: Chat Area -->
        <main class="chat-area" id="chatArea">
            <!-- Empty state -->
            <div class="chat-empty" id="chatEmpty">
                <div class="chat-empty-content">
                    <div class="chat-empty-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2><?= $L['panel_title'] ?? 'VMDestek Paneli' ?></h2>
                    <p><?= $L['select_conversation'] ?? 'Bir konuşma seçerek mesajlaşmaya başlayın' ?></p>
                    <div class="chat-empty-stats">
                        <div class="empty-stat">
                            <span class="empty-stat-number" id="emptyStatTotal">0</span>
                            <span class="empty-stat-label"><?= $L['total'] ?? 'Toplam' ?></span>
                        </div>
                        <div class="empty-stat">
                            <span class="empty-stat-number" id="emptyStatToday">0</span>
                            <span class="empty-stat-label"><?= $L['today'] ?? 'Bugün' ?></span>
                        </div>
                        <div class="empty-stat">
                            <span class="empty-stat-number" id="emptyStatRating">-</span>
                            <span class="empty-stat-label"><?= $L['avg_rating'] ?? 'Ortalama Puan' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active chat -->
            <div class="chat-active" id="chatActive" style="display:none">
                <!-- Chat header -->
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="visitor-avatar" id="visitorAvatar">
                            <span>?</span>
                        </div>
                        <div class="visitor-details">
                            <h3 id="visitorName"><?= $L['visitor'] ?? 'Ziyaretçi' ?></h3>
                            <div class="visitor-meta">
                                <span id="visitorEmail" class="meta-item"><i class="fas fa-envelope"></i> -</span>
                                <span id="visitorPage" class="meta-item"><i class="fas fa-globe"></i> -</span>
                            </div>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <button class="action-btn" onclick="showTransferModal()"
                            title="<?= $L['transfer_to_agent'] ?? 'Temsilciye Aktar' ?>">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <button class="action-btn" onclick="leaveConversation()"
                            title="<?= $L['leave_chat'] ?? 'Görüşmeden Ayrıl' ?>">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                        <button class="action-btn" onclick="togglePanel('notes')"
                            title="<?= $L['notes'] ?? 'Notlar' ?>">
                            <i class="fas fa-sticky-note"></i>
                        </button>
                        <button class="action-btn" onclick="togglePanel('reminders')"
                            title="<?= $L['reminders'] ?? 'Hatırlatmalar' ?>">
                            <i class="fas fa-bell"></i>
                            <span class="reminder-badge" id="reminderBadge" style="display:none">0</span>
                        </button>
                        <button class="action-btn" onclick="togglePanel('info')"
                            title="<?= $L['visitor_info'] ?? 'Ziyaretçi Bilgileri' ?>">
                            <i class="fas fa-info-circle"></i>
                        </button>
                        <button class="action-btn danger" onclick="closeConversation()"
                            title="<?= $L['close_conversation'] ?? 'Konuşmayı Kapat' ?>">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>

                <!-- Side Panel (info/notes/reminders) -->
                <div class="side-panel" id="sidePanel" style="display:none">
                    <div class="side-panel-tabs">
                        <button class="sp-tab active" data-tab="info" onclick="switchPanelTab('info')">
                            <i class="fas fa-info-circle"></i> <?= $L['info'] ?? 'Bilgi' ?>
                        </button>
                        <button class="sp-tab" data-tab="notes" onclick="switchPanelTab('notes')">
                            <i class="fas fa-sticky-note"></i> <?= $L['notes'] ?? 'Notlar' ?>
                        </button>
                        <button class="sp-tab" data-tab="reminders" onclick="switchPanelTab('reminders')">
                            <i class="fas fa-bell"></i> <?= $L['reminder'] ?? 'Hatırlatma' ?>
                        </button>
                    </div>

                    <!-- Info Tab -->
                    <div class="sp-content active" id="tabInfo">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-user"></i> <?= $L['name'] ?? 'İsim' ?></span>
                            <span class="info-value" id="infoName">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-envelope"></i>
                                <?= $L['email'] ?? 'E-posta' ?></span>
                            <span class="info-value" id="infoEmail">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-map-marker-alt"></i>
                                <?= $L['ip'] ?? 'IP' ?></span>
                            <span class="info-value" id="infoIP">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-globe"></i> <?= $L['page'] ?? 'Sayfa' ?></span>
                            <span class="info-value" id="infoPage">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-clock"></i>
                                <?= $L['started'] ?? 'Başlangıç' ?></span>
                            <span class="info-value" id="infoStarted">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-desktop"></i>
                                <?= $L['browser'] ?? 'Tarayıcı' ?></span>
                            <span class="info-value" id="infoBrowser">-</span>
                        </div>
                    </div>

                    <!-- Notes Tab -->
                    <div class="sp-content" id="tabNotes">
                        <div class="note-form">
                            <textarea id="noteInput" placeholder="<?= $L['note_placeholder'] ?? 'Not ekleyin...' ?>"
                                rows="3"></textarea>
                            <button class="note-save-btn" onclick="saveNote()">
                                <i class="fas fa-plus"></i> <?= $L['add_note'] ?? 'Not Ekle' ?>
                            </button>
                        </div>
                        <div class="notes-list" id="notesList">
                            <!-- Notes will be rendered here -->
                        </div>
                    </div>

                    <!-- Reminders Tab -->
                    <div class="sp-content" id="tabReminders">
                        <div class="reminder-form">
                            <input type="text" id="reminderTitle"
                                placeholder="<?= $L['reminder_title_placeholder'] ?? 'Hatırlatma başlığı...' ?>">
                            <input type="datetime-local" id="reminderDate">
                            <button class="reminder-save-btn" onclick="saveReminder()">
                                <i class="fas fa-bell"></i> <?= $L['add_reminder'] ?? 'Hatırlatma Ekle' ?>
                            </button>
                        </div>
                        <div class="reminders-list" id="remindersList">
                            <!-- Reminders will be rendered here -->
                        </div>
                    </div>
                </div>

                <!-- Reminder Notification Popup -->
                <div class="reminder-popup" id="reminderPopup" style="display:none">
                    <div class="reminder-popup-header">
                        <i class="fas fa-bell"></i> <?= $L['reminders'] ?? 'Hatırlatmalar' ?>
                        <button onclick="document.getElementById('reminderPopup').style.display='none'">&times;</button>
                    </div>
                    <div class="reminder-popup-list" id="reminderPopupList"></div>
                </div>

                <!-- Messages -->
                <div class="messages-container" id="messagesContainer">
                    <div class="messages" id="messagesList">
                        <!-- Messages will be loaded here -->
                    </div>
                    <div class="typing-indicator" id="typingIndicator" style="display:none">
                        <div class="typing-avatar">
                            <span id="typingInitial">Z</span>
                        </div>
                        <div class="typing-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>

                <!-- Message input -->
                <div class="message-input-area" id="messageInputArea">
                    <div class="canned-responses-bar" id="cannedBar" style="display:none">
                        <div class="canned-list" id="cannedList">
                            <!-- Canned responses will load here -->
                        </div>
                    </div>
                    <div class="input-container">
                        <button class="input-btn" onclick="toggleCannedResponses()"
                            title="<?= $L['canned_responses'] ?? 'Hazır Yanıtlar' ?>">
                            <i class="fas fa-bolt"></i>
                        </button>
                        <textarea id="messageInput"
                            placeholder="<?= $L['message_input_placeholder'] ?? 'Mesajınızı yazın... (Enter ile gönderin)' ?>"
                            rows="1" onkeydown="handleKeyDown(event)" oninput="handleInput()"></textarea>
                        <button class="send-btn" onclick="sendAdminMessage()" id="sendBtn" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Transfer Modal -->
    <div class="modal-overlay" id="transferModal" style="display:none" onclick="hideTransferModal(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-exchange-alt"></i> <?= $L['transfer_to_agent'] ?? 'Temsilciye Aktar' ?></h3>
                <button class="modal-close" onclick="hideTransferModal()">&times;</button>
            </div>
            <div class="modal-body" id="transferAgentList">
                <div class="popup-empty"><?= $L['loading'] ?? 'Yükleniyor...' ?></div>
            </div>
        </div>
    </div>

    <!-- Audio notification -->
    <audio id="notifSound" preload="auto">
        <source
            src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgipGDdWVhcpCkn4ZlSjxYgIaAd3BsdomVmH9mU0hRbXV5dHRyeIWNjHxrX1hVYW52eHl4d3d5foCBfXl1cnF0d3p9fX17enl6fHx9fn5+fn5+fn5+fn5+fn5+fwAA"
            type="audio/wav">
    </audio>

    <script>
        const SITE_URL = '<?= SITE_URL ?>';
        const ADMIN_ID = <?= $_SESSION['admin_id'] ?>;
        const ADMIN_NAME = '<?= addslashes($_SESSION['admin_name']) ?>';
        const LANG = <?= json_encode($L, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="js/app.js"></script>
</body>

</html>