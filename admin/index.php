<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
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

    <!-- Update Notification Popup -->
    <div class="update-overlay" id="updateOverlay" style="display:none" onclick="closeUpdatePopup(event)">
        <div class="update-popup">
            <button class="update-popup-close" onclick="closeUpdatePopup()">&times;</button>
            <div class="update-popup-icon">
                <i class="fas fa-arrow-up-from-bracket"></i>
            </div>
            <h2 class="update-popup-title"><?= $L['update_available'] ?? 'Güncelleme Mevcut!' ?></h2>
            <p class="update-popup-desc">
                <?= $L['update_available_desc'] ?? 'VMDestek için yeni bir sürüm yayınlandı.' ?></p>
            <div class="update-popup-versions">
                <div class="update-popup-ver current">
                    <span class="ver-label"><?= $L['current_version'] ?? 'Mevcut' ?></span>
                    <span class="ver-number" id="updateCurrentVer">-</span>
                </div>
                <div class="update-popup-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="update-popup-ver new">
                    <span class="ver-label"><?= $L['new_version'] ?? 'Yeni' ?></span>
                    <span class="ver-number" id="updateNewVer">-</span>
                </div>
            </div>
            <div class="update-popup-commit" id="updateCommitInfo" style="display:none">
                <i class="fas fa-code-commit"></i>
                <span id="updateCommitMsg"></span>
            </div>
            <a href="settings.php" class="update-popup-btn">
                <i class="fas fa-download"></i>
                <?= $L['go_to_update_page'] ?? 'Güncelleme Sayfasına Git' ?>
            </a>
        </div>
    </div>

    <style>
        /* ═══════ UPDATE POPUP ═══════ */
        .update-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: overlayFadeIn 0.3s ease;
        }

        @keyframes overlayFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .update-popup {
            background: rgba(20, 20, 45, 0.95);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 24px;
            padding: 40px 44px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            position: relative;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.5), 0 0 80px rgba(102, 126, 234, 0.1);
            animation: popupSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popupSlideIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .update-popup-close {
            position: absolute;
            top: 16px;
            right: 18px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .update-popup-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .update-popup-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.35);
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {

            0%,
            100% {
                box-shadow: 0 8px 32px rgba(16, 185, 129, 0.35);
            }

            50% {
                box-shadow: 0 8px 48px rgba(16, 185, 129, 0.55);
            }
        }

        .update-popup-title {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .update-popup-desc {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            margin-bottom: 28px;
        }

        .update-popup-versions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .update-popup-ver {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 14px 24px;
            border-radius: 14px;
            min-width: 100px;
        }

        .update-popup-ver.current {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .update-popup-ver.new {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .ver-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.4);
        }

        .update-popup-ver.new .ver-label {
            color: #6ee7b7;
        }

        .ver-number {
            font-size: 18px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.8);
        }

        .update-popup-ver.new .ver-number {
            color: #10b981;
        }

        .update-popup-arrow {
            color: rgba(255, 255, 255, 0.2);
            font-size: 18px;
        }

        .update-popup-commit {
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.45);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .update-popup-commit i {
            color: #667eea;
            font-size: 13px;
        }

        .update-popup-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        }

        .update-popup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(16, 185, 129, 0.4);
        }

        .update-popup-btn:active {
            transform: translateY(0);
        }
    </style>

    <script>
        const SITE_URL = '<?= SITE_URL ?>';
        const ADMIN_ID = <?= $_SESSION['admin_id'] ?>;
        const ADMIN_NAME = '<?= addslashes($_SESSION['admin_name']) ?>';
        const LANG = <?= json_encode($L, JSON_UNESCAPED_UNICODE) ?>;

        // GitHub güncelleme kontrolü - Sayfa yüklendiğinde çalışır
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(checkForUpdate, 2000); // 2sn sonra kontrol et (sayfa tam yüklensin)
        });

        async function checkForUpdate() {
            try {
                const res = await fetch(SITE_URL + '/api/update.php?action=check');
                const data = await res.json();

                if (data.success && data.has_update) {
                    // Versiyonları doldur
                    document.getElementById('updateCurrentVer').textContent = 'v' + data.local_version.version;
                    document.getElementById('updateNewVer').textContent = 'v' + data.remote_version.version;

                    // Commit bilgisi varsa göster
                    if (data.commit && data.commit.message) {
                        document.getElementById('updateCommitMsg').textContent = data.commit.message;
                        document.getElementById('updateCommitInfo').style.display = 'flex';
                    }

                    // Popup'ı göster
                    document.getElementById('updateOverlay').style.display = 'flex';
                }
            } catch (e) {
                // Sessizce hata yut - güncelleme kontrolü critical değil
                console.log('Güncelleme kontrolü başarısız:', e.message);
            }
        }

        function closeUpdatePopup(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('updateOverlay').style.display = 'none';
        }
    </script>
    <script src="js/app.js"></script>
</body>

</html>