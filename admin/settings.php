<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lang.php';

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
    <title><?= $L['settings_page_title'] ?? 'Ayarlar - VMDestek Admin' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* ═══════ SETTINGS LAYOUT ═══════ */
        .settings-layout {
            display: flex;
            height: 100vh;
            padding-top: 56px;
        }

        /* ═══════ LEFT SIDEBAR NAV ═══════ */
        .settings-nav {
            width: 260px;
            min-width: 260px;
            background: rgba(15, 15, 35, 0.6);
            border-right: 1px solid var(--border);
            padding: 28px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            overflow-y: auto;
        }

        .nav-header {
            padding: 0 12px 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }

        .nav-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .nav-header .version-tag {
            font-size: 11px;
            padding: 3px 10px;
            background: rgba(102, 126, 234, 0.15);
            border: 1px solid rgba(102, 126, 234, 0.25);
            border-radius: 20px;
            color: #93a4f4;
            font-weight: 500;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            border: 1px solid transparent;
            user-select: none;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: rgba(102, 126, 234, 0.1);
            border-color: rgba(102, 126, 234, 0.25);
            color: #93a4f4;
        }

        .nav-item .nav-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            transition: all 0.2s;
        }

        .nav-item.active .nav-icon {
            color: white;
        }

        .nav-icon.c-purple {
            background: rgba(102, 126, 234, 0.15);
            color: #667eea;
        }

        .nav-item.active .nav-icon.c-purple {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .nav-icon.c-emerald {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }

        .nav-item.active .nav-icon.c-emerald {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .nav-icon.c-amber {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .nav-item.active .nav-icon.c-amber {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .nav-icon.c-rose {
            background: rgba(244, 63, 94, 0.12);
            color: #f43f5e;
        }

        .nav-item.active .nav-icon.c-rose {
            background: linear-gradient(135deg, #f43f5e, #e11d48);
        }

        .nav-icon.c-sky {
            background: rgba(14, 165, 233, 0.12);
            color: #0ea5e9;
        }

        .nav-item.active .nav-icon.c-sky {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }

        .nav-item .nav-label {
            flex: 1;
        }

        .nav-spacer {
            flex: 1;
        }

        .nav-footer {
            padding: 16px 12px 0;
            border-top: 1px solid var(--border);
        }

        .btn-save-nav {
            width: 100%;
            padding: 12px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-save-nav:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
        }

        .btn-back-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            color: var(--text-muted);
            font-size: 12px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            margin-top: 10px;
        }

        .btn-back-nav:hover {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
        }

        /* ═══════ RIGHT CONTENT ═══════ */
        .settings-content {
            flex: 1;
            overflow-y: auto;
            padding: 36px 48px;
        }

        .tab-panel {
            display: none;
            animation: panelFade 0.3s ease;
        }

        .tab-panel.active {
            display: block;
        }

        @keyframes panelFade {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .panel-header {
            margin-bottom: 32px;
        }

        .panel-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .panel-header p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* ═══════ FORM CARDS ═══════ */
        .form-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px 28px;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }

        .form-card:hover {
            border-color: rgba(102, 126, 234, 0.15);
        }

        .form-card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 18px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .field-row.single {
            grid-template-columns: 1fr;
        }

        .field-group {
            margin-bottom: 0;
        }

        .field-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .field-group input,
        .field-group textarea,
        .field-group select {
            width: 100%;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 13px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .field-group input:focus,
        .field-group textarea:focus,
        .field-group select:focus {
            border-color: var(--accent);
            background: rgba(102, 126, 234, 0.06);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.08);
        }

        .field-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .field-group select option {
            background: var(--bg-secondary);
        }

        .color-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .color-row input[type="color"] {
            width: 44px;
            height: 38px;
            padding: 2px;
            cursor: pointer;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .color-row input[type="text"] {
            flex: 1;
        }

        .toggle-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .toggle-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        .toggle-row span {
            font-size: 13px;
            color: var(--text-primary);
        }

        /* ═══════ EMBED CODE ═══════ */
        .embed-block {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 18px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #93a4f4;
            word-break: break-all;
            position: relative;
            margin-bottom: 14px;
        }

        .embed-block .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 12px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            transition: all 0.2s;
        }

        .embed-block .copy-btn:hover {
            transform: scale(1.05);
        }

        /* ═══════ CANNED RESPONSES ═══════ */
        .canned-list-manager {
            max-height: 360px;
            overflow-y: auto;
        }

        .canned-manager-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.2s;
        }

        .canned-manager-item:hover {
            border-color: rgba(102, 126, 234, 0.2);
        }

        .canned-manager-item .canned-info {
            flex: 1;
            min-width: 0;
        }

        .canned-manager-item .canned-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .canned-manager-item .canned-preview {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .canned-shortcut-tag {
            font-size: 10px;
            color: #93a4f4;
            background: rgba(102, 126, 234, 0.1);
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 500;
        }

        .delete-canned,
        .delete-admin {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 13px;
            padding: 6px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .delete-canned:hover {
            color: #f43f5e;
            background: rgba(244, 63, 94, 0.1);
        }

        .add-form-box {
            padding: 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed var(--border);
            border-radius: 10px;
            margin-top: 14px;
        }

        .add-form-box .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .add-form-box input,
        .add-form-box textarea,
        .add-form-box select {
            flex: 1;
            padding: 9px 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 12px;
            font-family: inherit;
            outline: none;
        }

        .add-form-box textarea {
            resize: vertical;
            min-height: 60px;
        }

        .btn-primary-sm {
            padding: 9px 18px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .btn-primary-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-ghost-sm {
            padding: 9px 18px;
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            font-family: inherit;
        }

        /* ═══════ ADMIN USERS ═══════ */
        .admin-list-manager {
            max-height: 400px;
            overflow-y: auto;
        }

        .admin-manager-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.2s;
        }

        .admin-manager-item:hover {
            border-color: rgba(102, 126, 234, 0.2);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .admin-info {
            flex: 1;
            min-width: 0;
        }

        .admin-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .admin-meta {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 3px;
        }

        .role-badge {
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .role-badge.admin {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .role-badge.operator {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .online-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 4px;
        }

        .online-dot.online {
            background: #10b981;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
        }

        .online-dot.offline {
            background: #6b7280;
        }

        .admin-actions {
            display: flex;
            gap: 6px;
        }

        .admin-actions button {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .admin-actions button:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-primary);
        }

        .admin-actions button.delete-admin:hover {
            color: #f43f5e;
            border-color: rgba(244, 63, 94, 0.3);
        }

        /* ═══════ UPDATE SECTION ═══════ */
        .update-version-info {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            background: rgba(102, 126, 234, 0.04);
            border: 1px solid rgba(102, 126, 234, 0.15);
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .update-version-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .update-status {
            flex: 1;
            font-size: 13px;
            color: var(--text-muted);
        }

        .update-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-check-update {
            padding: 9px 18px;
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .btn-check-update:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: #667eea;
        }

        .btn-apply-update {
            padding: 9px 18px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            display: none;
        }

        .btn-apply-update:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .update-result {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13px;
            margin-top: 14px;
            display: none;
        }

        .update-result.has-update {
            display: block;
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        .update-result.no-update {
            display: block;
            background: rgba(102, 126, 234, 0.06);
            border: 1px solid rgba(102, 126, 234, 0.2);
            color: #93a4f4;
        }

        .update-result.update-error {
            display: block;
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .update-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 6px;
            vertical-align: middle;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ═══════ TOAST ═══════ */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>

<body>
    <header class="topbar">
        <div class="topbar-left">
            <div class="brand">
                <div class="brand-icon"><i class="fas fa-headset"></i></div>
                <span class="brand-name">VMDestek</span>
            </div>
        </div>
        <div class="topbar-right">
            <a href="index.php" class="topbar-btn" title="Panele Dön">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </header>

    <div class="settings-layout">
        <!-- ═══════ LEFT SIDEBAR ═══════ -->
        <nav class="settings-nav">
            <div class="nav-header">
                <h2><?= $L['settings'] ?? 'Ayarlar' ?></h2>
                <span class="version-tag" id="versionBadge">v...</span>
            </div>

            <div class="nav-item active" onclick="switchTab('appearance', this)">
                <div class="nav-icon c-purple"><i class="fas fa-palette"></i></div>
                <span class="nav-label"><?= $L['appearance'] ?? 'Görünüm' ?></span>
            </div>
            <div class="nav-item" onclick="switchTab('messages', this)">
                <div class="nav-icon c-emerald"><i class="fas fa-comment-dots"></i></div>
                <span class="nav-label"><?= $L['messages_tab'] ?? 'Mesajlar' ?></span>
            </div>
            <div class="nav-item" onclick="switchTab('canned', this)">
                <div class="nav-icon c-amber"><i class="fas fa-bolt"></i></div>
                <span class="nav-label"><?= $L['canned_tab'] ?? 'Hazır Yanıtlar' ?></span>
            </div>
            <div class="nav-item" onclick="switchTab('embed', this)">
                <div class="nav-icon c-sky"><i class="fas fa-code"></i></div>
                <span class="nav-label"><?= $L['integrations_tab'] ?? 'Entegrasyon' ?></span>
            </div>
            <div class="nav-item" onclick="switchTab('users', this)">
                <div class="nav-icon c-rose"><i class="fas fa-users-cog"></i></div>
                <span class="nav-label"><?= $L['users_tab'] ?? 'Kullanıcılar' ?></span>
            </div>
            <div class="nav-item" onclick="switchTab('update', this)">
                <div class="nav-icon c-sky"><i class="fas fa-cloud-download-alt"></i></div>
                <span class="nav-label"><?= $L['updates_tab'] ?? 'Güncelleme' ?></span>
            </div>

            <div class="nav-spacer"></div>

            <div class="nav-footer">
                <button class="btn-save-nav" onclick="saveAllSettings()">
                    <i class="fas fa-save"></i> <?= $L['save_settings'] ?? 'Ayarları Kaydet' ?>
                </button>
                <a href="index.php" class="btn-back-nav">
                    <i class="fas fa-arrow-left"></i> Panele Dön
                </a>
            </div>
        </nav>

        <!-- ═══════ CONTENT AREA ═══════ -->
        <div class="settings-content">

            <!-- TAB: Görünüm -->
            <div class="tab-panel active" id="tab-appearance">
                <div class="panel-header">
                    <h1><?= $L['appearance'] ?? 'Görünüm' ?></h1>
                    <p><?= $L['widget_colors'] ?? 'Widget renkleri' ?></p>
                </div>

                <div class="form-card">
                    <div class="form-card-title"><?= $L['widget_colors'] ?? 'Widget Renkleri' ?></div>
                    <div class="field-row">
                        <div class="field-group">
                            <label><?= $L['primary_color'] ?? 'Ana Renk' ?></label>
                            <div class="color-row">
                                <input type="color" id="widgetColor" value="#667eea"
                                    onchange="document.getElementById('widgetColorText').value=this.value">
                                <input type="text" id="widgetColorText" value="#667eea"
                                    oninput="document.getElementById('widgetColor').value=this.value">
                            </div>
                        </div>
                        <div class="field-group">
                            <label><?= $L['gradient_end'] ?? 'Gradyan Bitiş' ?></label>
                            <div class="color-row">
                                <input type="color" id="widgetGradientEnd" value="#764ba2"
                                    onchange="document.getElementById('widgetGradientEndText').value=this.value">
                                <input type="text" id="widgetGradientEndText" value="#764ba2"
                                    oninput="document.getElementById('widgetGradientEnd').value=this.value">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-title"><?= $L['general_info'] ?? 'Genel Bilgiler' ?></div>
                    <div class="field-row">
                        <div class="field-group">
                            <label><?= $L['company_name'] ?? 'Şirket Adı' ?></label>
                            <input type="text" id="companyName" value="VMDestek">
                        </div>
                        <div class="field-group">
                            <label><?= $L['widget_position'] ?? 'Widget Konumu' ?></label>
                            <select id="widgetPosition">
                                <option value="right"><?= $L['bottom_right'] ?? 'Sağ Alt' ?></option>
                                <option value="left"><?= $L['bottom_left'] ?? 'Sol Alt' ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="field-row single">
                        <div class="field-group">
                            <label><i class="fas fa-globe" style="margin-right:6px;color:#667eea"></i>
                                <?= $L['widget_language'] ?? 'Widget Dili' ?></label>
                            <select id="widgetLanguage">
                                <option value="tr">🇹🇷 Türkçe</option>
                                <option value="en">🇬🇧 English</option>
                                <option value="ar">🇸🇦 العربية (RTL)</option>
                            </select>
                            <small
                                style="color:var(--text-muted);margin-top:6px;display:block"><?= $L['lang_files_note'] ?? 'Dil dosyaları: lang/ klasöründen düzenlenebilir' ?></small>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-title"><?= $L['working_hours'] ?? 'Çalışma Saatleri' ?></div>
                    <div class="field-row">
                        <div class="field-group">
                            <label><?= $L['start_time'] ?? 'Başlangıç' ?></label>
                            <input type="time" id="workingHoursStart" value="09:00">
                        </div>
                        <div class="field-group">
                            <label><?= $L['end_time'] ?? 'Bitiş' ?></label>
                            <input type="time" id="workingHoursEnd" value="18:00">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Mesajlar -->
            <div class="tab-panel" id="tab-messages">
                <div class="panel-header">
                    <h1><?= $L['messages_tab'] ?? 'Mesajlar' ?></h1>
                    <p><?= $L['greeting_settings'] ?? 'Karşılama' ?></p>
                </div>

                <div class="form-card">
                    <div class="form-card-title"><?= $L['greeting_settings'] ?? 'Karşılama' ?></div>
                    <div class="field-row single">
                        <div class="field-group">
                            <label><?= $L['welcome_msg'] ?? 'Karşılama Mesajı' ?></label>
                            <textarea id="welcomeMessage" rows="2">Merhaba! Size nasıl yardımcı olabiliriz?</textarea>
                        </div>
                    </div>
                    <div class="field-row single">
                        <div class="field-group">
                            <label><?= $L['offline_msg'] ?? 'Çevrimdışı Mesajı' ?></label>
                            <textarea id="offlineMessage"
                                rows="2">Şu anda çevrimdışıyız. Lütfen mesajınızı bırakın.</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-title"><?= $L['auto_reply'] ?? 'Otomatik Yanıt' ?></div>
                    <div class="field-row single">
                        <div class="field-group">
                            <label><?= $L['auto_reply_msg'] ?? 'Otomatik Yanıt Mesajı' ?></label>
                            <textarea id="autoReplyMessage"
                                rows="2">Mesajınız alındı. Bir temsilci en kısa sürede size bağlanacak.</textarea>
                        </div>
                    </div>
                    <div class="toggle-row">
                        <input type="checkbox" id="autoReplyEnabled" checked>
                        <span><?= $L['auto_reply_label'] ?? 'Otomatik Yanıt Aktif' ?></span>
                    </div>
                </div>
            </div>

            <!-- TAB: Hazır Yanıtlar -->
            <div class="tab-panel" id="tab-canned">
                <div class="panel-header">
                    <h1><?= $L['canned_tab'] ?? 'Hazır Yanıtlar' ?></h1>
                    <p><?= $L['canned_management'] ?? 'Hazır Yanıt Yönetimi' ?></p>
                </div>

                <div class="form-card">
                    <div class="canned-list-manager" id="cannedListManager"></div>

                    <div class="add-form-box">
                        <div class="form-row">
                            <input type="text" id="newCannedTitle"
                                placeholder="<?= $L['canned_title_placeholder'] ?? 'Başlık' ?>">
                            <input type="text" id="newCannedShortcut"
                                placeholder="<?= $L['canned_shortcut_placeholder'] ?? '/kısayol' ?>"
                                style="max-width:100px">
                        </div>
                        <textarea id="newCannedMessage"
                            placeholder="<?= $L['canned_message_placeholder'] ?? 'Mesaj içeriği...' ?>"></textarea>
                        <div style="margin-top:10px;text-align:right">
                            <button class="btn-primary-sm" onclick="addCannedResponse()">
                                <i class="fas fa-plus"></i> <?= $L['add_canned'] ?? 'Ekle' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Entegrasyon -->
            <div class="tab-panel" id="tab-embed">
                <div class="panel-header">
                    <h1><?= $L['integrations_tab'] ?? 'Entegrasyon' ?></h1>
                    <p><?= $L['embed_code'] ?? 'Entegrasyon Kodu' ?></p>
                </div>

                <div class="form-card">
                    <div class="form-card-title"><?= $L['embed_script'] ?? 'Script Kodu' ?></div>
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
                        <code>&lt;/body&gt;</code> etiketinden önce yapıştırın:
                    </p>
                    <div class="embed-block" id="embedCode">
                        <button class="copy-btn" onclick="copyEmbedCode()"><i class="fas fa-copy"></i>
                            <?= $L['copy'] ?? 'Kopyala' ?></button>
                        <span id="embedCodeText"></span>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-title"><?= $L['embed_iframe'] ?? 'iFrame Kodu' ?></div>
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
                        Alternatif olarak iframe ile doğrudan gömebilirsiniz:
                    </p>
                    <div class="embed-block" id="iframeCode">
                        <button class="copy-btn" onclick="copyIframeCode()"><i class="fas fa-copy"></i>
                            <?= $L['copy'] ?? 'Kopyala' ?></button>
                        <span id="iframeCodeText"></span>
                    </div>
                </div>
            </div>

            <!-- TAB: Kullanıcılar -->
            <div class="tab-panel" id="tab-users">
                <div class="panel-header">
                    <h1><?= $L['user_management'] ?? 'Kullanıcı Yönetimi' ?></h1>
                    <p><?= $L['users_tab'] ?? 'Kullanıcılar' ?></p>
                </div>

                <div class="form-card">
                    <div class="admin-list-manager" id="adminListManager"></div>

                    <div class="add-form-box" id="addAdminForm">
                        <input type="hidden" id="editAdminId" value="0">
                        <div class="form-row">
                            <input type="text" id="adminName" placeholder="<?= $L['fullname'] ?? 'Ad Soyad' ?>">
                            <input type="text" id="adminUsername"
                                placeholder="<?= $L['username_label'] ?? 'Kullanıcı adı' ?>">
                        </div>
                        <div class="form-row">
                            <input type="email" id="adminEmail" placeholder="<?= $L['email'] ?? 'E-posta' ?>">
                            <input type="password" id="adminPassword"
                                placeholder="<?= $L['password_label'] ?? 'Şifre' ?>">
                            <select id="adminRole" style="max-width:120px">
                                <option value="admin"><?= $L['admin_role'] ?? 'Admin' ?></option>
                                <option value="operator"><?= $L['operator_role'] ?? 'Operatör' ?></option>
                            </select>
                        </div>
                        <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end">
                            <button class="btn-ghost-sm" id="btnCancelAdmin" onclick="cancelAdminEdit()"
                                style="display:none"><?= $L['cancel_edit'] ?? 'İptal' ?></button>
                            <button class="btn-primary-sm" onclick="saveAdminUser()">
                                <i class="fas fa-plus" id="adminFormIcon"></i>
                                <span id="adminFormBtnText"><?= $L['add_user'] ?? 'Kullanıcı Ekle' ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Güncelleme -->
            <div class="tab-panel" id="tab-update">
                <div class="panel-header">
                    <h1><?= $L['auto_update'] ?? 'Otomatik Güncelleme' ?></h1>
                    <p><?= $L['current_version'] ?? 'Mevcut Sürüm' ?></p>
                </div>

                <div class="form-card">
                    <div class="update-version-info">
                        <div class="update-version-badge">
                            <i class="fas fa-tag"></i>
                            <span id="currentVersion">v...</span>
                        </div>
                        <div class="update-status" id="updateStatus">
                            Güncel sürüm bilgisi yükleniyor...
                        </div>
                        <div class="update-actions">
                            <button class="btn-check-update" onclick="checkForUpdate()" id="btnCheckUpdate">
                                <i class="fas fa-sync-alt"></i> <?= $L['check_update'] ?? 'Güncelleme Kontrol Et' ?>
                            </button>
                            <button class="btn-apply-update" onclick="applyUpdate()" id="btnApplyUpdate">
                                <i class="fas fa-download"></i> <?= $L['update_apply'] ?? 'Güncelle' ?>
                            </button>
                        </div>
                    </div>
                    <div class="update-result" id="updateResult"></div>
                </div>
            </div>

        </div>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle" style="margin-right:8px"></i>
        <?= $L['settings_saved'] ?? 'Ayarlar kaydedildi!' ?>
    </div>

    <script>
        const SITE_URL = '<?= SITE_URL ?>';
        const LANG = <?= json_encode($L, JSON_UNESCAPED_UNICODE) ?>;

        // ═══════ TAB SWITCHING ═══════
        function switchTab(tabId, navEl) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById('tab-' + tabId).classList.add('active');
            navEl.classList.add('active');
        }

        // ═══════ LOAD SETTINGS ═══════
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=settings`);
                const data = await res.json();
                if (data.success) {
                    const s = data.settings;
                    if (s.widget_color) {
                        document.getElementById('widgetColor').value = s.widget_color;
                        document.getElementById('widgetColorText').value = s.widget_color;
                    }
                    if (s.widget_gradient_end) {
                        document.getElementById('widgetGradientEnd').value = s.widget_gradient_end;
                        document.getElementById('widgetGradientEndText').value = s.widget_gradient_end;
                    }
                    if (s.company_name) document.getElementById('companyName').value = s.company_name;
                    if (s.widget_position) document.getElementById('widgetPosition').value = s.widget_position;
                    if (s.language) document.getElementById('widgetLanguage').value = s.language;
                    if (s.welcome_message) document.getElementById('welcomeMessage').value = s.welcome_message;
                    if (s.offline_message) document.getElementById('offlineMessage').value = s.offline_message;
                    if (s.auto_reply_message) document.getElementById('autoReplyMessage').value = s.auto_reply_message;
                    document.getElementById('autoReplyEnabled').checked = s.auto_reply_enabled === '1';
                    if (s.working_hours_start) document.getElementById('workingHoursStart').value = s.working_hours_start;
                    if (s.working_hours_end) document.getElementById('workingHoursEnd').value = s.working_hours_end;
                }
            } catch (err) {
                console.error('Load settings error:', err);
            }
            updateEmbedCode();
            loadCannedResponses();
            loadAdminUsers();
        });

        function updateEmbedCode() {
            document.getElementById('embedCodeText').innerHTML = `&lt;script src="${SITE_URL}/embed.js"&gt;&lt;/script&gt;`;
            document.getElementById('iframeCodeText').innerHTML = `&lt;iframe src="${SITE_URL}/widget/" style="position:fixed;bottom:0;right:0;width:400px;height:600px;border:none;z-index:9999"&gt;&lt;/iframe&gt;`;
        }

        async function saveAllSettings() {
            const settings = {
                widget_color: document.getElementById('widgetColor').value,
                widget_gradient_end: document.getElementById('widgetGradientEnd').value,
                company_name: document.getElementById('companyName').value,
                widget_position: document.getElementById('widgetPosition').value,
                language: document.getElementById('widgetLanguage').value,
                welcome_message: document.getElementById('welcomeMessage').value,
                offline_message: document.getElementById('offlineMessage').value,
                auto_reply_message: document.getElementById('autoReplyMessage').value,
                auto_reply_enabled: document.getElementById('autoReplyEnabled').checked ? '1' : '0',
                working_hours_start: document.getElementById('workingHoursStart').value,
                working_hours_end: document.getElementById('workingHoursEnd').value
            };

            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(settings)
                });
                const data = await res.json();
                if (data.success) showToast();
            } catch (err) {
                console.error('Save error:', err);
            }
        }

        // ═══════ CANNED RESPONSES ═══════
        async function loadCannedResponses() {
            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=canned`);
                const data = await res.json();
                if (data.success) {
                    const list = document.getElementById('cannedListManager');
                    list.innerHTML = data.responses.map(r => `
                        <div class="canned-manager-item">
                            <div class="canned-info">
                                <div class="canned-title">${escapeHtml(r.title)}</div>
                                <div class="canned-preview">${escapeHtml(r.message)}</div>
                            </div>
                            ${r.shortcut ? `<span class="canned-shortcut-tag">${escapeHtml(r.shortcut)}</span>` : ''}
                            <button class="delete-canned" onclick="deleteCanned(${r.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `).join('');
                }
            } catch (err) {
                console.error('Load canned error:', err);
            }
        }

        async function addCannedResponse() {
            const title = document.getElementById('newCannedTitle').value.trim();
            const message = document.getElementById('newCannedMessage').value.trim();
            const shortcut = document.getElementById('newCannedShortcut').value.trim();
            if (!title || !message) return alert(LANG.title_date_required || 'Başlık ve mesaj gerekli');
            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=canned_save`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title, message, shortcut })
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('newCannedTitle').value = '';
                    document.getElementById('newCannedMessage').value = '';
                    document.getElementById('newCannedShortcut').value = '';
                    loadCannedResponses();
                    showToast();
                }
            } catch (err) {
                console.error('Add canned error:', err);
            }
        }

        async function deleteCanned(id) {
            if (!confirm(LANG.confirm_delete_note || 'Bu hazır yanıtı silmek istediğinize emin misiniz?')) return;
            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=canned_delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) loadCannedResponses();
            } catch (err) { }
        }

        function copyEmbedCode() {
            const text = `<script src="${SITE_URL}/embed.js"><\/script>`;
            navigator.clipboard.writeText(text);
            showToast('Embed kodu kopyalandı!');
        }

        function copyIframeCode() {
            const text = `<iframe src="${SITE_URL}/widget/" style="position:fixed;bottom:0;right:0;width:400px;height:600px;border:none;z-index:9999"></iframe>`;
            navigator.clipboard.writeText(text);
            showToast('Iframe kodu kopyalandı!');
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            if (msg) toast.innerHTML = `<i class="fas fa-check-circle" style="margin-right:8px"></i>${msg}`;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // ═══════ ADMIN USERS ═══════
        async function loadAdminUsers() {
            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=admin_list`);
                const data = await res.json();
                if (data.success) {
                    const list = document.getElementById('adminListManager');
                    if (data.admins.length === 0) {
                        list.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:20px">Henüz kullanıcı yok</p>';
                        return;
                    }
                    list.innerHTML = data.admins.map(a => {
                        const initials = a.name ? a.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
                        const isOnline = a.is_online == 1;
                        return `
                            <div class="admin-manager-item">
                                <div class="admin-avatar">${initials}</div>
                                <div class="admin-info">
                                    <div class="admin-name">
                                        ${escapeHtml(a.name)}
                                        <span class="online-dot ${isOnline ? 'online' : 'offline'}"></span>
                                    </div>
                                    <div class="admin-meta">
                                        <span>@${escapeHtml(a.username)}</span>
                                        ${a.email ? `<span>• ${escapeHtml(a.email)}</span>` : ''}
                                    </div>
                                </div>
                                <span class="role-badge ${a.role}">${a.role === 'admin' ? (LANG.admin_role || 'Admin') : (LANG.operator_role || 'Operatör')}</span>
                                <div class="admin-actions">
                                    <button onclick='editAdmin(${JSON.stringify(a)})' title="${LANG.edit || 'Düzenle'}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="delete-admin" onclick="deleteAdminUser(${a.id})" title="${LANG.delete || 'Sil'}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            } catch (err) {
                console.error('Load admins error:', err);
            }
        }

        function editAdmin(admin) {
            document.getElementById('editAdminId').value = admin.id;
            document.getElementById('adminName').value = admin.name || '';
            document.getElementById('adminUsername').value = admin.username || '';
            document.getElementById('adminEmail').value = admin.email || '';
            document.getElementById('adminPassword').value = '';
            document.getElementById('adminPassword').placeholder = LANG.change_password_placeholder || 'Değiştirmek için yeni şifre girin';
            document.getElementById('adminRole').value = admin.role || 'operator';
            document.getElementById('adminFormIcon').className = 'fas fa-save';
            document.getElementById('adminFormBtnText').textContent = LANG.update || 'Güncelle';
            document.getElementById('btnCancelAdmin').style.display = '';
        }

        function cancelAdminEdit() {
            document.getElementById('editAdminId').value = '0';
            document.getElementById('adminName').value = '';
            document.getElementById('adminUsername').value = '';
            document.getElementById('adminEmail').value = '';
            document.getElementById('adminPassword').value = '';
            document.getElementById('adminPassword').placeholder = LANG.password_label || 'Şifre';
            document.getElementById('adminRole').value = 'operator';
            document.getElementById('adminFormIcon').className = 'fas fa-plus';
            document.getElementById('adminFormBtnText').textContent = LANG.add_user || 'Kullanıcı Ekle';
            document.getElementById('btnCancelAdmin').style.display = 'none';
        }

        async function saveAdminUser() {
            const id = parseInt(document.getElementById('editAdminId').value) || 0;
            const name = document.getElementById('adminName').value.trim();
            const username = document.getElementById('adminUsername').value.trim();
            const email = document.getElementById('adminEmail').value.trim();
            const password = document.getElementById('adminPassword').value;
            const role = document.getElementById('adminRole').value;

            if (!name || !username) return alert(LANG.name_username_required || 'Ad Soyad ve Kullanıcı adı gerekli');
            if (id === 0 && !password) return alert(LANG.password_required_new || 'Yeni kullanıcı için şifre gerekli');

            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=admin_save`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, name, username, email, password, role })
                });
                const data = await res.json();
                if (data.success) {
                    cancelAdminEdit();
                    loadAdminUsers();
                    showToast(id > 0 ? (LANG.user_updated || 'Kullanıcı güncellendi!') : (LANG.user_added || 'Kullanıcı eklendi!'));
                } else {
                    alert(data.error || LANG.error_occurred || 'Bir hata oluştu');
                }
            } catch (err) {
                console.error('Save admin error:', err);
            }
        }

        async function deleteAdminUser(id) {
            if (!confirm(LANG.confirm_delete_user || 'Bu kullanıcıyı silmek istediğinize emin misiniz?')) return;
            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=admin_delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    loadAdminUsers();
                    showToast(LANG.user_deleted || 'Kullanıcı silindi!');
                } else {
                    alert(data.error || LANG.error_occurred || 'Bir hata oluştu');
                }
            } catch (err) {
                console.error('Delete admin error:', err);
            }
        }

        // ═══════ AUTO UPDATE ═══════
        async function loadVersionInfo() {
            try {
                const res = await fetch(`${SITE_URL}/version.json?t=${Date.now()}`);
                const data = await res.json();
                document.getElementById('currentVersion').textContent = 'v' + data.version;
                document.getElementById('updateStatus').textContent = `${LANG.current_version || 'Sürüm'}: ${data.version} | Build: ${data.build}`;
                document.getElementById('versionBadge').textContent = 'v' + data.version;
            } catch (err) {
                document.getElementById('currentVersion').textContent = 'v?';
                document.getElementById('updateStatus').textContent = LANG.version_read_error || 'Sürüm bilgisi okunamadı';
            }
        }

        async function checkForUpdate() {
            const btn = document.getElementById('btnCheckUpdate');
            const result = document.getElementById('updateResult');
            const applyBtn = document.getElementById('btnApplyUpdate');

            btn.disabled = true;
            btn.innerHTML = '<span class="update-spinner"></span> ' + (LANG.checking || 'Kontrol ediliyor...');
            result.className = 'update-result';
            result.style.display = 'none';
            applyBtn.style.display = 'none';

            try {
                const res = await fetch(`${SITE_URL}/api/update.php?action=check`);
                const data = await res.json();

                if (data.error) {
                    result.className = 'update-result update-error';
                    result.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.error}`;
                    result.style.display = 'block';
                } else if (data.has_update) {
                    result.className = 'update-result has-update';
                    let html = `<i class="fas fa-arrow-circle-up"></i> <strong>${LANG.update_available || 'Yeni sürüm mevcut'}: v${data.remote_version.version}</strong> (Build: ${data.remote_version.build})`;
                    if (data.commit) {
                        html += `<br><small style="opacity:0.7">${LANG.last_commit || 'Son commit'}: ${escapeHtml(data.commit.message)} (${data.commit.sha})</small>`;
                    }
                    result.innerHTML = html;
                    result.style.display = 'block';
                    applyBtn.style.display = 'inline-flex';
                } else {
                    result.className = 'update-result no-update';
                    let html = '<i class="fas fa-check-circle"></i> ' + (LANG.up_to_date || 'Sisteminiz güncel!');
                    if (data.commit) {
                        html += `<br><small style="opacity:0.7">${LANG.last_commit || 'Son commit'}: ${escapeHtml(data.commit.message)} (${data.commit.sha})</small>`;
                    }
                    result.innerHTML = html;
                    result.style.display = 'block';
                }
            } catch (err) {
                result.className = 'update-result update-error';
                result.innerHTML = '<i class="fas fa-times-circle"></i> ' + (LANG.update_connect_error || 'Güncelleme sunucusuna bağlanılamadı.');
                result.style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> ' + (LANG.check_update || 'Güncelleme Kontrol Et');
        }

        async function applyUpdate() {
            if (!confirm(LANG.update_confirm || 'Güncelleme uygulanacak. Devam etmek istiyor musunuz?\n\nNot: config.php ve uploads klasörünüz korunacaktır.')) return;

            const btn = document.getElementById('btnApplyUpdate');
            const result = document.getElementById('updateResult');

            btn.disabled = true;
            btn.innerHTML = '<span class="update-spinner"></span> ' + (LANG.updating || 'Güncelleniyor...');

            try {
                const res = await fetch(`${SITE_URL}/api/update.php?action=apply`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();

                if (data.success) {
                    result.className = 'update-result has-update';
                    result.innerHTML = `<i class="fas fa-check-circle"></i> <strong>${data.message || (LANG.update_success || 'Güncelleme başarılı!')}</strong><br><small>${data.files_updated} ${LANG.files_updated || 'dosya güncellendi. Sayfa yeniden yükleniyor...'}</small>`;
                    result.style.display = 'block';
                    btn.style.display = 'none';
                    setTimeout(() => { window.location.reload(); }, 2000);
                } else {
                    result.className = 'update-result update-error';
                    result.innerHTML = `<i class="fas fa-times-circle"></i> ${data.error || LANG.update_error || 'Güncelleme sırasında bir hata oluştu.'}`;
                    result.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-download"></i> ' + (LANG.update_apply || 'Güncelle');
                }
            } catch (err) {
                result.className = 'update-result update-error';
                result.innerHTML = '<i class="fas fa-times-circle"></i> ' + (LANG.update_error || 'Güncelleme uygulanırken bir hata oluştu.');
                result.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download"></i> ' + (LANG.update_apply || 'Güncelle');
            }
        }

        loadVersionInfo();
    </script>
</body>

</html>