<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - VMDestek Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .settings-page {
            height: 100vh;
            overflow-y: auto;
            padding: 80px 40px 60px;
        }

        .settings-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 40px;
        }

        .settings-header .back-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--bg-glass);
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 16px;
        }

        .settings-header .back-btn:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
            border-color: var(--accent);
        }

        .settings-header h1 {
            font-size: 26px;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .settings-header .header-badge {
            font-size: 11px;
            padding: 4px 12px;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            color: var(--accent);
            font-weight: 500;
        }

        /* Section Headers */
        .section-divider {
            max-width: 1200px;
            margin: 40px 0 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .section-divider:first-of-type {
            margin-top: 0;
        }

        .section-divider .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }

        .section-divider .section-icon.purple {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .section-divider .section-icon.emerald {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .section-divider .section-icon.amber {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .section-divider .section-icon.rose {
            background: linear-gradient(135deg, #f43f5e, #e11d48);
        }

        .section-divider .section-icon.sky {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }

        .section-divider .section-text h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .section-divider .section-text p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--border), transparent);
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
            gap: 20px;
            max-width: 1200px;
        }

        .settings-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .settings-card:hover {
            border-color: rgba(102, 126, 234, 0.15);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
        }

        .settings-card h2 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-card h2 i {
            width: 32px;
            height: 32px;
            background: var(--gradient);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
        }

        /* Save Bar */
        .save-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 40px;
            background: rgba(15, 15, 30, 0.85);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            z-index: 100;
        }

        .save-bar .save-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-right: auto;
        }

        .save-bar .save-hint i {
            margin-right: 6px;
            color: var(--accent);
        }

        .field-group {
            margin-bottom: 16px;
        }

        .field-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field-group input,
        .field-group textarea,
        .field-group select {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 13px;
            font-family: inherit;
            outline: none;
            transition: var(--transition);
        }

        .field-group input:focus,
        .field-group textarea:focus,
        .field-group select:focus {
            border-color: var(--accent);
            background: rgba(102, 126, 234, 0.06);
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
            gap: 12px;
            align-items: center;
        }

        .color-row input[type="color"] {
            width: 44px;
            height: 36px;
            padding: 2px;
            cursor: pointer;
            border-radius: 6px;
        }

        .color-row input[type="text"] {
            flex: 1;
        }

        .btn-save {
            padding: 12px 32px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
        }

        .embed-code {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: var(--accent);
            word-break: break-all;
            margin-top: 8px;
            position: relative;
        }

        .embed-code .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 4px 10px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            font-family: inherit;
        }

        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 24px;
            background: var(--success);
            color: white;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Canned Responses Manager */
        .canned-list-manager {
            max-height: 400px;
            overflow-y: auto;
        }

        .canned-manager-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: var(--bg-glass);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            margin-bottom: 6px;
        }

        .canned-manager-item .canned-info {
            flex: 1;
            min-width: 0;
        }

        .canned-manager-item .canned-title {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .canned-manager-item .canned-preview {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .canned-manager-item .canned-shortcut-tag {
            font-size: 10px;
            color: var(--accent);
            background: rgba(102, 126, 234, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .canned-manager-item .delete-canned {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 13px;
            padding: 4px;
            transition: var(--transition);
        }

        .canned-manager-item .delete-canned:hover {
            color: var(--danger);
        }

        .add-canned-form {
            padding: 12px;
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            margin-top: 12px;
        }

        .add-canned-form .form-row {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }

        .add-canned-form input,
        .add-canned-form textarea {
            flex: 1;
            padding: 8px 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 12px;
            font-family: inherit;
            outline: none;
        }

        .add-canned-form textarea {
            resize: vertical;
            min-height: 60px;
        }

        .btn-add-canned {
            padding: 8px 16px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
        }

        /* Auto Update */
        .update-card {
            border: 1px solid rgba(102, 126, 234, 0.2);
            background: rgba(102, 126, 234, 0.04);
        }

        .update-version-info {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--bg-glass);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
        }

        .update-version-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
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
            padding: 8px 18px;
            background: var(--bg-glass);
            color: var(--text-primary);
            border: 1px solid var(--border-light);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .btn-check-update:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: #667eea;
        }

        .btn-apply-update {
            padding: 8px 18px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 6px;
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
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-top: 12px;
            display: none;
        }

        .update-result.has-update {
            display: block;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        .update-result.no-update {
            display: block;
            background: rgba(102, 126, 234, 0.08);
            border: 1px solid rgba(102, 126, 234, 0.2);
            color: #93a4f4;
        }

        .update-result.update-error {
            display: block;
            background: rgba(239, 68, 68, 0.08);
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

        /* Admin Users Manager */
        .admin-list-manager {
            max-height: 400px;
            overflow-y: auto;
        }

        .admin-manager-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: var(--bg-glass);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            transition: var(--transition);
        }

        .admin-manager-item:hover {
            border-color: var(--accent);
            background: rgba(102, 126, 234, 0.04);
        }

        .admin-manager-item .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }

        .admin-manager-item .admin-info {
            flex: 1;
            min-width: 0;
        }

        .admin-manager-item .admin-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .admin-manager-item .admin-meta {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 2px;
        }

        .admin-manager-item .role-badge {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 500;
        }

        .role-badge.admin {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .role-badge.operator {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .admin-manager-item .online-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .online-dot.online {
            background: #10b981;
            box-shadow: 0 0 4px rgba(16, 185, 129, 0.5);
        }

        .online-dot.offline {
            background: #9ca3af;
        }

        .admin-actions {
            display: flex;
            gap: 6px;
        }

        .admin-actions button {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg-glass);
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .admin-actions button:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
        }

        .admin-actions button.delete-admin:hover {
            color: var(--danger);
            border-color: var(--danger);
        }

        .add-admin-form {
            padding: 16px;
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            margin-top: 12px;
        }

        .add-admin-form .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .add-admin-form input,
        .add-admin-form select {
            flex: 1;
            padding: 9px 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 12px;
            font-family: inherit;
            outline: none;
        }

        .add-admin-form select {
            max-width: 130px;
        }

        .btn-add-admin {
            padding: 9px 20px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-cancel-admin {
            padding: 9px 20px;
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-family: inherit;
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

    <div class="settings-page">
        <div class="settings-header">
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1>Ayarlar</h1>
            <span class="header-badge" id="versionBadge">v...</span>
        </div>

        <!-- ═══════ SECTION: Widget & Görünüm ═══════ -->
        <div class="section-divider">
            <div class="section-icon purple"><i class="fas fa-paint-brush"></i></div>
            <div class="section-text">
                <h3>Widget & Görünüm</h3>
                <p>Widget renkleri, şirket adı ve konum ayarları</p>
            </div>
        </div>
        <div class="settings-grid">
            <!-- Widget Appearance -->
            <div class="settings-card">
                <h2><i class="fas fa-palette"></i> Widget Görünüm</h2>

                <div class="field-group">
                    <label>Ana Renk</label>
                    <div class="color-row">
                        <input type="color" id="widgetColor" value="#667eea"
                            onchange="document.getElementById('widgetColorText').value=this.value">
                        <input type="text" id="widgetColorText" value="#667eea"
                            oninput="document.getElementById('widgetColor').value=this.value">
                    </div>
                </div>

                <div class="field-group">
                    <label>Gradient Bitiş Rengi</label>
                    <div class="color-row">
                        <input type="color" id="widgetGradientEnd" value="#764ba2"
                            onchange="document.getElementById('widgetGradientEndText').value=this.value">
                        <input type="text" id="widgetGradientEndText" value="#764ba2"
                            oninput="document.getElementById('widgetGradientEnd').value=this.value">
                    </div>
                </div>

                <div class="field-group">
                    <label>Şirket Adı</label>
                    <input type="text" id="companyName" value="VMDestek">
                </div>

                <div class="field-group">
                    <label>Widget Konumu</label>
                    <select id="widgetPosition">
                        <option value="right">Sağ Alt</option>
                        <option value="left">Sol Alt</option>
                    </select>
                </div>
            </div>

            <!-- Working Hours -->
            <div class="settings-card">
                <h2><i class="fas fa-clock"></i> Çalışma Saatleri</h2>

                <div class="field-group">
                    <label>Başlangıç Saati</label>
                    <input type="time" id="workingHoursStart" value="09:00">
                </div>

                <div class="field-group">
                    <label>Bitiş Saati</label>
                    <input type="time" id="workingHoursEnd" value="18:00">
                </div>
            </div>
        </div>

        <!-- ═══════ SECTION: Mesajlar & İçerik ═══════ -->
        <div class="section-divider">
            <div class="section-icon emerald"><i class="fas fa-comment-dots"></i></div>
            <div class="section-text">
                <h3>Mesajlar & İçerik</h3>
                <p>Hoşgeldin mesajı, otomatik yanıtlar ve hazır cevaplar</p>
            </div>
        </div>
        <div class="settings-grid">
            <!-- Messages -->
            <div class="settings-card">
                <h2><i class="fas fa-comment-dots"></i> Mesajlar</h2>

                <div class="field-group">
                    <label>Hoşgeldin Mesajı</label>
                    <textarea id="welcomeMessage" rows="2">Merhaba! Size nasıl yardımcı olabiliriz?</textarea>
                </div>

                <div class="field-group">
                    <label>Çevrimdışı Mesajı</label>
                    <textarea id="offlineMessage" rows="2">Şu anda çevrimdışıyız. Lütfen mesajınızı bırakın.</textarea>
                </div>

                <div class="field-group">
                    <label>Otomatik Yanıt</label>
                    <textarea id="autoReplyMessage"
                        rows="2">Mesajınız alındı. Bir temsilci en kısa sürede size bağlanacak.</textarea>
                </div>

                <div class="field-group">
                    <label style="display:flex;align-items:center;gap:8px">
                        <input type="checkbox" id="autoReplyEnabled" checked style="width:auto;padding:0">
                        Otomatik yanıt aktif
                    </label>
                </div>
            </div>

            <!-- Canned Responses -->
            <div class="settings-card">
                <h2><i class="fas fa-bolt"></i> Hazır Yanıtlar</h2>

                <div class="canned-list-manager" id="cannedListManager">
                    <!-- Loaded dynamically -->
                </div>

                <div class="add-canned-form">
                    <div class="form-row">
                        <input type="text" id="newCannedTitle" placeholder="Başlık">
                        <input type="text" id="newCannedShortcut" placeholder="/kısayol" style="max-width:100px">
                    </div>
                    <textarea id="newCannedMessage" placeholder="Mesaj içeriği..."></textarea>
                    <div style="margin-top:8px;text-align:right">
                        <button class="btn-add-canned" onclick="addCannedResponse()">
                            <i class="fas fa-plus"></i> Ekle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ SECTION: Entegrasyon ═══════ -->
        <div class="section-divider">
            <div class="section-icon amber"><i class="fas fa-code"></i></div>
            <div class="section-text">
                <h3>Entegrasyon</h3>
                <p>Widget embed kodları ve kurulum bilgileri</p>
            </div>
        </div>
        <div class="settings-grid">
            <!-- Embed Code -->
            <div class="settings-card" style="grid-column: 1 / -1">
                <h2><i class="fas fa-code"></i> Embed Kodu</h2>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
                    Aşağıdaki kodu web sitenizin <code>&lt;/body&gt;</code> etiketinden önce yapıştırın:
                </p>
                <div class="embed-code" id="embedCode">
                    <button class="copy-btn" onclick="copyEmbedCode()"><i class="fas fa-copy"></i> Kopyala</button>
                    <span id="embedCodeText"></span>
                </div>

                <p style="font-size:12px;color:var(--text-muted);margin-top:12px">
                    <i class="fas fa-info-circle" style="margin-right:4px"></i>
                    Veya iframe ile doğrudan gömebilirsiniz:
                </p>
                <div class="embed-code" id="iframeCode" style="margin-top:8px">
                    <button class="copy-btn" onclick="copyIframeCode()"><i class="fas fa-copy"></i> Kopyala</button>
                    <span id="iframeCodeText"></span>
                </div>
            </div>
        </div>

        <!-- ═══════ SECTION: Yönetim ═══════ -->
        <div class="section-divider">
            <div class="section-icon rose"><i class="fas fa-shield-alt"></i></div>
            <div class="section-text">
                <h3>Yönetim</h3>
                <p>Kullanıcı rolleri, erişim ve sistem yönetimi</p>
            </div>
        </div>
        <div class="settings-grid">
            <!-- Admin Users -->
            <div class="settings-card" style="grid-column: 1 / -1">
                <h2><i class="fas fa-users-cog"></i> Kullanıcı Yönetimi</h2>

                <div class="admin-list-manager" id="adminListManager">
                    <!-- Loaded dynamically -->
                </div>

                <div class="add-admin-form" id="addAdminForm">
                    <input type="hidden" id="editAdminId" value="0">
                    <div class="form-row">
                        <input type="text" id="adminName" placeholder="Ad Soyad">
                        <input type="text" id="adminUsername" placeholder="Kullanıcı adı">
                    </div>
                    <div class="form-row">
                        <input type="email" id="adminEmail" placeholder="E-posta (opsiyonel)">
                        <input type="password" id="adminPassword" placeholder="Şifre">
                        <select id="adminRole">
                            <option value="admin">Admin</option>
                            <option value="operator">Operatör</option>
                        </select>
                    </div>
                    <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end">
                        <button class="btn-cancel-admin" id="btnCancelAdmin" onclick="cancelAdminEdit()"
                            style="display:none">
                            İptal
                        </button>
                        <button class="btn-add-admin" onclick="saveAdminUser()">
                            <i class="fas fa-plus" id="adminFormIcon"></i>
                            <span id="adminFormBtnText">Kullanıcı Ekle</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ SECTION: Sistem ═══════ -->
        <div class="section-divider">
            <div class="section-icon sky"><i class="fas fa-cloud-download-alt"></i></div>
            <div class="section-text">
                <h3>Sistem</h3>
                <p>Otomatik güncelleme ve sürüm kontrolü</p>
            </div>
        </div>
        <div class="settings-grid" style="margin-bottom:80px">
            <!-- Auto Update -->
            <div class="settings-card update-card" style="grid-column: 1 / -1">
                <h2><i class="fas fa-cloud-download-alt"></i> Sistem Güncellemesi</h2>

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
                            <i class="fas fa-sync-alt"></i> Güncelleme Kontrol Et
                        </button>
                        <button class="btn-apply-update" onclick="applyUpdate()" id="btnApplyUpdate">
                            <i class="fas fa-download"></i> Güncelle
                        </button>
                    </div>
                </div>

                <div class="update-result" id="updateResult"></div>
            </div>
        </div>
    </div>

    <!-- Fixed Save Bar -->
    <div class="save-bar">
        <span class="save-hint"><i class="fas fa-info-circle"></i> Değişikliklerinizi kaydetmeyi unutmayın</span>
        <button class="btn-save" onclick="saveAllSettings()">
            <i class="fas fa-save" style="margin-right:8px"></i>
            Ayarları Kaydet
        </button>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle" style="margin-right:8px"></i>
        Ayarlar kaydedildi!
    </div>

    <script>
        const SITE_URL = '<?= SITE_URL ?>';

        // Load settings
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

            // Generate embed code
            updateEmbedCode();

            // Load canned responses
            loadCannedResponses();

            // Load admin users
            loadAdminUsers();
        });

        function updateEmbedCode() {
            const embedText = `&lt;script src="${SITE_URL}/embed.js"&gt;&lt;/script&gt;`;
            document.getElementById('embedCodeText').innerHTML = embedText;

            const iframeText = `&lt;iframe src="${SITE_URL}/widget/" style="position:fixed;bottom:0;right:0;width:400px;height:600px;border:none;z-index:9999"&gt;&lt;/iframe&gt;`;
            document.getElementById('iframeCodeText').innerHTML = iframeText;
        }

        async function saveAllSettings() {
            const settings = {
                widget_color: document.getElementById('widgetColor').value,
                widget_gradient_end: document.getElementById('widgetGradientEnd').value,
                company_name: document.getElementById('companyName').value,
                widget_position: document.getElementById('widgetPosition').value,
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
                if (data.success) {
                    showToast();
                }
            } catch (err) {
                console.error('Save error:', err);
            }
        }

        // Canned Responses
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

            if (!title || !message) return alert('Başlık ve mesaj gerekli');

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
            if (!confirm('Bu hazır yanıtı silmek istediğinize emin misiniz?')) return;

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

        // =========== ADMIN USERS ===========
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
                                <span class="role-badge ${a.role}">${a.role === 'admin' ? 'Admin' : 'Operatör'}</span>
                                <div class="admin-actions">
                                    <button onclick='editAdmin(${JSON.stringify(a)})' title="Düzenle">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="delete-admin" onclick="deleteAdminUser(${a.id})" title="Sil">
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
            document.getElementById('adminPassword').placeholder = 'Değiştirmek için yeni şifre girin';
            document.getElementById('adminRole').value = admin.role || 'operator';
            document.getElementById('adminFormIcon').className = 'fas fa-save';
            document.getElementById('adminFormBtnText').textContent = 'Güncelle';
            document.getElementById('btnCancelAdmin').style.display = '';
        }

        function cancelAdminEdit() {
            document.getElementById('editAdminId').value = '0';
            document.getElementById('adminName').value = '';
            document.getElementById('adminUsername').value = '';
            document.getElementById('adminEmail').value = '';
            document.getElementById('adminPassword').value = '';
            document.getElementById('adminPassword').placeholder = 'Şifre';
            document.getElementById('adminRole').value = 'operator';
            document.getElementById('adminFormIcon').className = 'fas fa-plus';
            document.getElementById('adminFormBtnText').textContent = 'Kullanıcı Ekle';
            document.getElementById('btnCancelAdmin').style.display = 'none';
        }

        async function saveAdminUser() {
            const id = parseInt(document.getElementById('editAdminId').value) || 0;
            const name = document.getElementById('adminName').value.trim();
            const username = document.getElementById('adminUsername').value.trim();
            const email = document.getElementById('adminEmail').value.trim();
            const password = document.getElementById('adminPassword').value;
            const role = document.getElementById('adminRole').value;

            if (!name || !username) return alert('Ad Soyad ve Kullanıcı adı gerekli');
            if (id === 0 && !password) return alert('Yeni kullanıcı için şifre gerekli');

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
                    showToast(id > 0 ? 'Kullanıcı güncellendi!' : 'Kullanıcı eklendi!');
                } else {
                    alert(data.error || 'Bir hata oluştu');
                }
            } catch (err) {
                console.error('Save admin error:', err);
            }
        }

        async function deleteAdminUser(id) {
            if (!confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')) return;

            try {
                const res = await fetch(`${SITE_URL}/api/admin.php?action=admin_delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                const data = await res.json();
                if (data.success) {
                    loadAdminUsers();
                    showToast('Kullanıcı silindi!');
                } else {
                    alert(data.error || 'Bir hata oluştu');
                }
            } catch (err) {
                console.error('Delete admin error:', err);
            }
        }

        // === AUTO UPDATE ===
        async function loadVersionInfo() {
            try {
                const res = await fetch(`${SITE_URL}/version.json?t=${Date.now()}`);
                const data = await res.json();
                document.getElementById('currentVersion').textContent = 'v' + data.version;
                document.getElementById('updateStatus').textContent = `Sürüm: ${data.version} | Build: ${data.build}`;
                document.getElementById('versionBadge').textContent = 'v' + data.version;
            } catch (err) {
                document.getElementById('currentVersion').textContent = 'v?';
                document.getElementById('updateStatus').textContent = 'Sürüm bilgisi okunamadı';
            }
        }

        async function checkForUpdate() {
            const btn = document.getElementById('btnCheckUpdate');
            const result = document.getElementById('updateResult');
            const applyBtn = document.getElementById('btnApplyUpdate');

            btn.disabled = true;
            btn.innerHTML = '<span class="update-spinner"></span> Kontrol ediliyor...';
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
                    let html = `<i class="fas fa-arrow-circle-up"></i> <strong>Yeni sürüm mevcut: v${data.remote_version.version}</strong> (Build: ${data.remote_version.build})`;
                    if (data.commit) {
                        html += `<br><small style="opacity:0.7">Son commit: ${escapeHtml(data.commit.message)} (${data.commit.sha})</small>`;
                    }
                    result.innerHTML = html;
                    result.style.display = 'block';
                    applyBtn.style.display = 'inline-flex';
                } else {
                    result.className = 'update-result no-update';
                    let html = '<i class="fas fa-check-circle"></i> Sisteminiz güncel!';
                    if (data.commit) {
                        html += `<br><small style="opacity:0.7">Son commit: ${escapeHtml(data.commit.message)} (${data.commit.sha})</small>`;
                    }
                    result.innerHTML = html;
                    result.style.display = 'block';
                }
            } catch (err) {
                result.className = 'update-result update-error';
                result.innerHTML = '<i class="fas fa-times-circle"></i> Güncelleme sunucusuna bağlanılamadı.';
                result.style.display = 'block';
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Güncelleme Kontrol Et';
        }

        async function applyUpdate() {
            if (!confirm('Güncelleme uygulanacak. Devam etmek istiyor musunuz?\n\nNot: config.php ve uploads klasörünüz korunacaktır.')) return;

            const btn = document.getElementById('btnApplyUpdate');
            const result = document.getElementById('updateResult');

            btn.disabled = true;
            btn.innerHTML = '<span class="update-spinner"></span> Güncelleniyor...';

            try {
                const res = await fetch(`${SITE_URL}/api/update.php?action=apply`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();

                if (data.success) {
                    result.className = 'update-result has-update';
                    result.innerHTML = `<i class="fas fa-check-circle"></i> <strong>${data.message}</strong><br><small>${data.files_updated} dosya güncellendi. Sayfa yeniden yükleniyor...</small>`;
                    result.style.display = 'block';
                    btn.style.display = 'none';

                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    result.className = 'update-result update-error';
                    result.innerHTML = `<i class="fas fa-times-circle"></i> ${data.error || 'Güncelleme sırasında bir hata oluştu.'}`;
                    result.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-download"></i> Güncelle';
                }
            } catch (err) {
                result.className = 'update-result update-error';
                result.innerHTML = '<i class="fas fa-times-circle"></i> Güncelleme uygulanırken bir hata oluştu.';
                result.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download"></i> Güncelle';
            }
        }

        // Sayfa yüklendiğinde sürüm bilgisini yükle
        loadVersionInfo();
    </script>
</body>

</html>