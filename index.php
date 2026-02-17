<?php
/**
 * VMDestek - Landing Page
 * Sistem tanıtım ve yönlendirme sayfası
 * 
 * © 2026 Semih AKBAŞ - semihakbas.com.tr
 */

// Versiyon bilgisini oku
$version = '1.0.0';
$versionFile = __DIR__ . '/version.json';
if (file_exists($versionFile)) {
    $vData = json_decode(file_get_contents($versionFile), true);
    if ($vData && isset($vData['version'])) {
        $version = $vData['version'];
    }
}

// Kurulum durumunu kontrol et
$installed = file_exists(__DIR__ . '/install.lock');
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMDestek — Canlı Destek Sistemi</title>
    <meta name="description"
        content="Web sitenize kolayca entegre edebileceğiniz, modern ve hafif canlı destek chat sistemi.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #06060f;
            color: #e2e8f0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ═══════ BACKGROUND ═══════ */
        .bg-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .bg-glow::before {
            content: '';
            position: absolute;
            top: -20%;
            left: 20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.12) 0%, transparent 70%);
            animation: glowFloat1 12s ease-in-out infinite;
        }

        .bg-glow::after {
            content: '';
            position: absolute;
            bottom: -10%;
            right: 10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(118, 75, 162, 0.1) 0%, transparent 70%);
            animation: glowFloat2 15s ease-in-out infinite;
        }

        @keyframes glowFloat1 {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(40px, -30px);
            }
        }

        @keyframes glowFloat2 {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(-30px, 20px);
            }
        }

        .grid-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(102, 126, 234, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(102, 126, 234, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        /* ═══════ NAVBAR ═══════ */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 48px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(6, 6, 15, 0.8);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .brand-text {
            font-size: 22px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        .brand-text span {
            background: linear-gradient(135deg, #667eea, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navbar-version {
            font-size: 11px;
            padding: 4px 12px;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            color: #93a4f4;
            font-weight: 600;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            padding: 10px 20px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-btn {
            padding: 10px 22px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.25);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(102, 126, 234, 0.4);
        }

        /* ═══════ HERO ═══════ */
        .hero {
            text-align: center;
            padding: 100px 24px 80px;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            color: #6ee7b7;
            margin-bottom: 32px;
            animation: badgePulse 3s ease-in-out infinite;
        }

        @keyframes badgePulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.05);
            }
        }

        .hero h1 {
            font-size: 56px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -1.5px;
        }

        .hero h1 .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #a78bfa 50%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.45);
            line-height: 1.7;
            max-width: 580px;
            margin: 0 auto 48px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-hero {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 36px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            font-family: inherit;
            cursor: pointer;
            border: none;
        }

        .btn-hero.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }

        .btn-hero.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.45);
        }

        .btn-hero.secondary {
            background: rgba(255, 255, 255, 0.04);
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-hero.secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* ═══════ FEATURES ═══════ */
        .section {
            max-width: 1100px;
            margin: 0 auto;
            padding: 80px 24px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 12px;
        }

        .section-header p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 15px;
            max-width: 500px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .feature-card {
            padding: 32px 28px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--card-accent, #667eea), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .feature-card:hover {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.03);
            transform: translateY(-4px);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }

        .feature-card p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.4);
            line-height: 1.6;
        }

        /* Card color variants */
        .fc-purple .feature-icon {
            background: rgba(102, 126, 234, 0.12);
            color: #667eea;
        }

        .fc-purple {
            --card-accent: #667eea;
        }

        .fc-emerald .feature-icon {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }

        .fc-emerald {
            --card-accent: #10b981;
        }

        .fc-amber .feature-icon {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .fc-amber {
            --card-accent: #f59e0b;
        }

        .fc-rose .feature-icon {
            background: rgba(244, 63, 94, 0.12);
            color: #f43f5e;
        }

        .fc-rose {
            --card-accent: #f43f5e;
        }

        .fc-sky .feature-icon {
            background: rgba(14, 165, 233, 0.12);
            color: #0ea5e9;
        }

        .fc-sky {
            --card-accent: #0ea5e9;
        }

        .fc-violet .feature-icon {
            background: rgba(139, 92, 246, 0.12);
            color: #8b5cf6;
        }

        .fc-violet {
            --card-accent: #8b5cf6;
        }

        /* ═══════ HOW IT WORKS ═══════ */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            counter-reset: step;
        }

        .step-card {
            padding: 32px 28px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            position: relative;
            counter-increment: step;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            color: white;
            margin-bottom: 18px;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.25);
        }

        .step-card h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            color: white;
        }

        .step-card p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.4);
            line-height: 1.6;
        }

        .step-card code {
            display: inline-block;
            padding: 3px 10px;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #93a4f4;
            margin-top: 10px;
        }

        /* ═══════ EMBED CODE ═══════ */
        .embed-section {
            max-width: 700px;
            margin: 0 auto;
            padding: 0 24px 80px;
            text-align: center;
        }

        .embed-block {
            background: rgba(10, 10, 25, 0.8);
            border: 1px solid rgba(102, 126, 234, 0.15);
            border-radius: 16px;
            padding: 24px 28px;
            margin-top: 32px;
            text-align: left;
            position: relative;
        }

        .embed-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .embed-code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #93a4f4;
            word-break: break-all;
            line-height: 1.6;
        }

        .embed-code .tag {
            color: #f43f5e;
        }

        .embed-code .attr {
            color: #f59e0b;
        }

        .embed-code .val {
            color: #10b981;
        }

        .copy-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            transform: scale(1.05);
        }

        /* ═══════ QUICK LINKS ═══════ */
        .links-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            max-width: 700px;
            margin: 0 auto;
            padding: 0 24px 80px;
        }

        .link-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 22px 24px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .link-card:hover {
            border-color: rgba(102, 126, 234, 0.25);
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-3px);
        }

        .link-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .link-icon.purple {
            background: rgba(102, 126, 234, 0.12);
            color: #667eea;
        }

        .link-icon.emerald {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }

        .link-icon.amber {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .link-icon.rose {
            background: rgba(244, 63, 94, 0.12);
            color: #f43f5e;
        }

        .link-info h4 {
            font-size: 14px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
        }

        .link-info p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.35);
        }

        .link-arrow {
            margin-left: auto;
            color: rgba(255, 255, 255, 0.15);
            font-size: 14px;
            transition: all 0.3s;
        }

        .link-card:hover .link-arrow {
            color: #667eea;
            transform: translateX(4px);
        }

        /* ═══════ TECH STACK ═══════ */
        .tech-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 32px;
            padding: 40px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            flex-wrap: wrap;
        }

        .tech-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.35);
            font-weight: 500;
        }

        .tech-item i {
            font-size: 18px;
        }

        .tech-item .php {
            color: #7B7FB5;
        }

        .tech-item .mysql {
            color: #F29111;
        }

        .tech-item .js {
            color: #F7DF1E;
        }

        .tech-item .html {
            color: #E44D26;
        }

        .tech-item .css {
            color: #264DE4;
        }

        /* ═══════ FOOTER ═══════ */
        .footer {
            text-align: center;
            padding: 40px 24px;
            color: rgba(255, 255, 255, 0.2);
            font-size: 13px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* ═══════ INSTALL ALERT ═══════ */
        .install-alert {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 14px 24px;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
            border-bottom: 1px solid rgba(245, 158, 11, 0.15);
            font-size: 13px;
            color: #fbbf24;
        }

        .install-alert a {
            color: white;
            font-weight: 600;
            text-decoration: none;
            padding: 5px 16px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 8px;
            font-size: 12px;
            transition: all 0.2s;
        }

        .install-alert a:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        /* ═══════ RESPONSIVE ═══════ */
        @media (max-width: 768px) {
            .navbar {
                padding: 16px 20px;
            }

            .brand-text {
                font-size: 18px;
            }

            .hero {
                padding: 60px 20px 50px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 15px;
            }

            .features-grid,
            .steps-grid {
                grid-template-columns: 1fr;
            }

            .links-grid {
                grid-template-columns: 1fr;
            }

            .hero-actions {
                flex-direction: column;
            }

            .btn-hero {
                width: 100%;
                justify-content: center;
            }

            .navbar-actions .nav-link {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="bg-glow"></div>
    <div class="grid-pattern"></div>

    <div class="content">
        <?php if (!$installed): ?>
            <div class="install-alert">
                <i class="fas fa-exclamation-triangle"></i>
                Sistem henüz kurulmamış.
                <a href="install.php"><i class="fas fa-magic"></i> Kurulumu Başlat</a>
            </div>
        <?php endif; ?>

        <!-- Navbar -->
        <nav class="navbar">
            <a href="index.php" class="navbar-brand">
                <div class="brand-logo">
                    <i class="fas fa-headset"></i>
                </div>
                <span class="brand-text">VM<span>Destek</span></span>
                <span class="navbar-version">v
                    <?= htmlspecialchars($version) ?>
                </span>
            </a>
            <div class="navbar-actions">
                <a href="https://github.com/Sem-h/VMDestek" target="_blank" class="nav-link">
                    <i class="fab fa-github"></i> GitHub
                </a>
                <?php if ($installed): ?>
                    <a href="admin/login.php" class="nav-btn">
                        <i class="fas fa-sign-in-alt"></i> Admin Panel
                    </a>
                <?php else: ?>
                    <a href="install.php" class="nav-btn">
                        <i class="fas fa-download"></i> Kurulum
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Hero -->
        <section class="hero">
            <div class="hero-badge">
                <i class="fas fa-bolt"></i>
                Ücretsiz & Açık Kaynak — MIT Lisansı
            </div>
            <h1>
                Modern <span class="gradient-text">Canlı Destek</span><br>Sistemi
            </h1>
            <p>
                Web sitenize tek satır kod ile entegre edebileceğiniz, gerçek zamanlı canlı destek chat sistemi.
                PHP & MySQL ile çalışır, Node.js gerektirmez.
            </p>
            <div class="hero-actions">
                <?php if ($installed): ?>
                    <a href="admin/login.php" class="btn-hero primary">
                        <i class="fas fa-rocket"></i> Admin Panele Git
                    </a>
                    <a href="admin/settings.php" class="btn-hero secondary">
                        <i class="fas fa-cog"></i> Ayarlar
                    </a>
                <?php else: ?>
                    <a href="install.php" class="btn-hero primary">
                        <i class="fas fa-magic"></i> Kurulumu Başlat
                    </a>
                    <a href="https://github.com/Sem-h/VMDestek" target="_blank" class="btn-hero secondary">
                        <i class="fab fa-github"></i> GitHub'da İncele
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Tech Stack Bar -->
        <div class="tech-bar">
            <div class="tech-item"><i class="fab fa-php php"></i> PHP 7.4+</div>
            <div class="tech-item"><i class="fas fa-database mysql"></i> MySQL 5.7+</div>
            <div class="tech-item"><i class="fab fa-js-square js"></i> Vanilla JS</div>
            <div class="tech-item"><i class="fab fa-html5 html"></i> HTML5</div>
            <div class="tech-item"><i class="fab fa-css3-alt css"></i> CSS3</div>
        </div>

        <!-- Features -->
        <section class="section">
            <div class="section-header">
                <h2>Neler Yapabilirsiniz?</h2>
                <p>VMDestek ile web sitenize profesyonel bir canlı destek deneyimi sunun</p>
            </div>

            <div class="features-grid">
                <div class="feature-card fc-purple">
                    <div class="feature-icon"><i class="fas fa-comments"></i></div>
                    <h3>Gerçek Zamanlı Chat</h3>
                    <p>Ziyaretçilerinizle anlık mesajlaşın. Long-polling teknolojisi ile WebSocket gerektirmeden
                        çalışır.</p>
                </div>
                <div class="feature-card fc-emerald">
                    <div class="feature-icon"><i class="fas fa-eye"></i></div>
                    <h3>Anlık Ziyaretçi Takibi</h3>
                    <p>Sitenizdeki aktif ziyaretçileri canlı olarak izleyin. Hangi sayfada olduklarını görün.</p>
                </div>
                <div class="feature-card fc-amber">
                    <div class="feature-icon"><i class="fas fa-palette"></i></div>
                    <h3>Özelleştirilebilir Widget</h3>
                    <p>Renk, gradient, şirket adı, logo ve kapak mesajını admin panelden kolayca ayarlayın.</p>
                </div>
                <div class="feature-card fc-rose">
                    <div class="feature-icon"><i class="fas fa-image"></i></div>
                    <h3>Resim Gönderme</h3>
                    <p>Ziyaretçiler sohbet sırasında resim paylaşabilir. Lightbox ile tam ekran görüntüleme.</p>
                </div>
                <div class="feature-card fc-sky">
                    <div class="feature-icon"><i class="fas fa-exchange-alt"></i></div>
                    <h3>Temsilci Aktarma</h3>
                    <p>Konuşmaları başka temsilcilere aktarın. Çoklu temsilci desteği ile iş yükünü dağıtın.</p>
                </div>
                <div class="feature-card fc-violet">
                    <div class="feature-icon"><i class="fas fa-globe"></i></div>
                    <h3>Çoklu Dil Desteği</h3>
                    <p>Türkçe, İngilizce ve Arapça (RTL) desteği. Widget ve admin panel tamamen çevrilebilir.</p>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="section" style="padding-top: 0;">
            <div class="section-header">
                <h2>Nasıl Çalışır?</h2>
                <p>3 adımda canlı destek sisteminizi kurun</p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Kurulumu Yapın</h3>
                    <p>Dosyaları sunucunuza yükleyin ve kurulum sihirbazını çalıştırın. Veritabanı otomatik oluşturulur.
                    </p>
                    <code>siteadresiniz.com/install.php</code>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Embed Kodunu Ekleyin</h3>
                    <p>Tek satır JavaScript kodunu web sitenize ekleyin. Widget otomatik olarak görünecektir.</p>
                    <code>&lt;script src="embed.js"&gt;&lt;/script&gt;</code>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Destek Verin</h3>
                    <p>Admin panelden gelen sohbetleri yönetin. Ziyaretçilerinize anında cevap verin.</p>
                    <code>siteadresiniz.com/admin</code>
                </div>
            </div>
        </section>

        <!-- Embed Code -->
        <section class="embed-section">
            <div class="section-header">
                <h2>Tek Satır Entegrasyon</h2>
                <p>Web sitenize aşağıdaki kodu ekleyin, hepsi bu kadar!</p>
            </div>

            <div class="embed-block">
                <div class="embed-label">HTML — &lt;/body&gt; etiketinden önce</div>
                <div class="embed-code">
                    <span class="tag">&lt;script</span>
                    <span class="attr"> src</span>=<span class="val">"
                        <?= htmlspecialchars(($installed ? (defined('SITE_URL') ? SITE_URL : '') : 'https://siteadresiniz.com')) ?>/embed.js"
                    </span><span class="tag">&gt;&lt;/script&gt;</span>
                </div>
                <button class="copy-btn" onclick="copyEmbed()">
                    <i class="fas fa-copy"></i> Kopyala
                </button>
            </div>
        </section>

        <!-- Quick Links -->
        <div class="links-grid">
            <a href="admin/login.php" class="link-card">
                <div class="link-icon purple"><i class="fas fa-shield-alt"></i></div>
                <div class="link-info">
                    <h4>Admin Panel</h4>
                    <p>Sohbetleri yönetin, ayarları yapın</p>
                </div>
                <i class="fas fa-arrow-right link-arrow"></i>
            </a>
            <?php if (!$installed): ?>
                <a href="install.php" class="link-card">
                    <div class="link-icon emerald"><i class="fas fa-magic"></i></div>
                    <div class="link-info">
                        <h4>Kurulum Sihirbazı</h4>
                        <p>Veritabanı ve sistemi kurun</p>
                    </div>
                    <i class="fas fa-arrow-right link-arrow"></i>
                </a>
            <?php else: ?>
                <a href="admin/settings.php" class="link-card">
                    <div class="link-icon emerald"><i class="fas fa-cog"></i></div>
                    <div class="link-info">
                        <h4>Ayarlar</h4>
                        <p>Widget, mesaj ve sistem ayarları</p>
                    </div>
                    <i class="fas fa-arrow-right link-arrow"></i>
                </a>
            <?php endif; ?>
            <a href="https://github.com/Sem-h/VMDestek" target="_blank" class="link-card">
                <div class="link-icon amber"><i class="fab fa-github"></i></div>
                <div class="link-info">
                    <h4>GitHub</h4>
                    <p>Kaynak kodu ve dökümanlar</p>
                </div>
                <i class="fas fa-arrow-right link-arrow"></i>
            </a>
            <a href="https://semihakbas.com.tr" target="_blank" class="link-card">
                <div class="link-icon rose"><i class="fas fa-globe"></i></div>
                <div class="link-info">
                    <h4>Geliştirici</h4>
                    <p>Semih AKBAŞ — semihakbas.com.tr</p>
                </div>
                <i class="fas fa-arrow-right link-arrow"></i>
            </a>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>
                VMDestek v
                <?= htmlspecialchars($version) ?> &copy;
                <?= date('Y') ?>
                <a href="https://semihakbas.com.tr" target="_blank">Semih AKBAŞ</a>
                — MIT License
            </p>
        </footer>
    </div>

    <script>
        function copyEmbed() {
            const code = document.querySelector('.embed-code').textContent.trim();
            navigator.clipboard.writeText(code).then(() => {
                const btn = document.querySelector('.copy-btn');
                btn.innerHTML = '<i class="fas fa-check"></i> Kopyalandı!';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
                }, 2000);
            });
        }

        // Fade-in animation on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.feature-card, .step-card, .link-card').forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `all 0.5s ease ${i * 0.08}s`;
            observer.observe(el);
        });
    </script>
</body>

</html>