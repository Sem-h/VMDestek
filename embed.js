/**
 * VMDestek - Pure JS Embed Script (No iframe)
 * 
 * Kullanım:
 * <script src="https://yourdomain.com/LiveSupport/embed.js"></script>
 */
(function () {
    'use strict';

    if (window.__vmdestek_loaded) return;
    window.__vmdestek_loaded = true;

    // ═══════ CONFIG ═══════
    const BASE_URL = (function () {
        const scripts = document.getElementsByTagName('script');
        for (let i = scripts.length - 1; i >= 0; i--) {
            const src = scripts[i].src;
            if (src && src.indexOf('embed.js') !== -1) {
                return src.replace('/embed.js', '');
            }
        }
        return '';
    })();

    const W = 380, H = 560;

    // ═══════ STATE ═══════
    let isOpen = false;
    let sessionId = null;
    let conversationId = null;
    let lastMsgId = 0;
    let isPolling = false;
    let pollTimer = null;
    let typingTimer = null;
    let isVisitorTyping = false;
    let tempIdCounter = -1;
    let greetingTimer = null;
    let config = {};

    // ═══════ CSS ═══════
    const css = document.createElement('style');
    css.textContent = `
        /* ═══════ BUTTON ═══════ */
        #vmd-btn{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;cursor:pointer;box-shadow:0 4px 24px rgba(102,126,234,0.4);z-index:999998;display:flex;align-items:center;justify-content:center;transition:all .3s cubic-bezier(.175,.885,.32,1.275);outline:none}
        #vmd-btn:hover{transform:scale(1.1);box-shadow:0 6px 32px rgba(102,126,234,0.55)}
        #vmd-btn svg{width:28px;height:28px;fill:#fff;transition:all .3s}
        #vmd-btn.open{opacity:0;pointer-events:none;transform:scale(.5)}
        #vmd-btn::before{content:'';position:absolute;width:100%;height:100%;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);animation:vmd-pulse 2s ease-out infinite;z-index:-1}
        @keyframes vmd-pulse{0%{transform:scale(1);opacity:.4}100%{transform:scale(1.7);opacity:0}}
        #vmd-btn .vmd-badge{position:absolute;top:-4px;right:-4px;width:22px;height:22px;background:#ef4444;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:none;align-items:center;justify-content:center;border:2px solid #fff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
        #vmd-btn .vmd-badge.show{display:flex}

        /* ═══════ WIDGET CONTAINER ═══════ */
        #vmd-widget{position:fixed;bottom:96px;right:24px;width:${W}px;height:${H}px;max-height:calc(100vh - 120px);z-index:999999;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.18),0 8px 24px rgba(0,0,0,.08);opacity:0;transform:translateY(20px) scale(.95);transition:all .35s cubic-bezier(.175,.885,.32,1.275);pointer-events:none;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif!important;font-size:14px!important;line-height:1.5!important;color:#1a1a2e!important;display:flex;flex-direction:column;background:#f5f6fa}
        #vmd-widget.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all;bottom:24px}
        #vmd-widget,#vmd-widget *{margin:0;padding:0;box-sizing:border-box!important}
        @media(max-width:480px){#vmd-widget{bottom:0!important;right:0!important;left:0!important;width:100%!important;height:100%!important;max-height:100vh!important;border-radius:0!important}#vmd-btn{bottom:16px;right:16px}}

        /* ═══════ GREETING ═══════ */
        #vmd-greeting{position:fixed;bottom:92px;right:24px;background:#fff;color:#1a1a2e;padding:14px 22px;border-radius:14px 14px 4px 14px;box-shadow:0 8px 32px rgba(0,0,0,.12);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;max-width:260px;z-index:999997;opacity:0;transform:translateY(10px);transition:all .3s ease;pointer-events:none}
        #vmd-greeting.show{opacity:1;transform:translateY(0);pointer-events:all}
        #vmd-greeting .vmd-close-g{position:absolute;top:4px;right:8px;background:none;border:none;color:#999;cursor:pointer;font-size:14px;padding:2px 4px}

        /* ═══════ SCREEN LAYOUT ═══════ */
        #vmd-widget .vmd-screen{display:flex!important;flex-direction:column!important;height:100%!important}

        /* ═══════ HEADER ═══════ */
        #vmd-widget .vmd-header{background:var(--vmd-gradient)!important;padding:20px 20px 32px!important;position:relative!important;flex-shrink:0!important}
        #vmd-widget .vmd-header.compact{padding:12px 16px!important;display:flex!important;align-items:center!important;justify-content:space-between!important}
        #vmd-widget .vmd-hdr-content{display:flex!important;align-items:center!important;gap:12px!important;position:relative;z-index:2}
        #vmd-widget .vmd-hdr-icon{width:44px!important;height:44px!important;background:rgba(255,255,255,.18)!important;backdrop-filter:blur(10px);border-radius:12px!important;display:flex!important;align-items:center!important;justify-content:center!important;font-size:20px!important;color:#fff!important}
        #vmd-widget .vmd-hdr-icon.small{width:34px!important;height:34px!important;border-radius:10px!important;font-size:15px!important}
        #vmd-widget .vmd-hdr-text h2{color:#fff!important;font-size:16px!important;font-weight:700!important;margin-bottom:1px!important;background:none!important;border:none!important;text-transform:none!important;letter-spacing:normal!important}
        #vmd-widget .compact .vmd-hdr-text h2{font-size:14px!important;margin-bottom:0!important}
        #vmd-widget .vmd-status{display:flex!important;align-items:center!important;gap:6px!important;color:rgba(255,255,255,.85)!important;font-size:12px!important}
        #vmd-widget .vmd-dot{width:7px!important;height:7px!important;border-radius:50%!important;background:rgba(255,255,255,.4)!important;display:inline-block!important}
        #vmd-widget .vmd-dot.online{background:#4ade80!important;box-shadow:0 0 8px rgba(74,222,128,.6);animation:vmd-p 2s ease infinite}
        #vmd-widget .vmd-dot.offline{background:#fbbf24!important}
        @keyframes vmd-p{0%,100%{opacity:1}50%{opacity:.5}}
        #vmd-widget .vmd-hdr-wave{position:absolute!important;bottom:-1px!important;left:0!important;right:0!important;z-index:1}
        #vmd-widget .vmd-hdr-wave svg{width:100%;height:24px;display:block}
        #vmd-widget .vmd-hdr-actions{display:flex!important;gap:6px!important;position:absolute!important;top:14px!important;right:14px!important;z-index:3}
        #vmd-widget .compact .vmd-hdr-actions{position:static!important}
        #vmd-widget .vmd-hdr-btn{width:30px!important;height:30px!important;border-radius:8px!important;border:1px solid rgba(255,255,255,.2)!important;background:rgba(255,255,255,.1)!important;color:#fff!important;cursor:pointer;transition:all .2s;display:flex!important;align-items:center!important;justify-content:center!important;font-size:12px!important}
        #vmd-widget .vmd-hdr-btn:hover{background:rgba(255,255,255,.25)!important}

        /* ═══════ BODY / PRE-CHAT ═══════ */
        #vmd-widget .vmd-body{flex:1!important;overflow-y:auto!important;padding:24px 22px 20px!important;background:#fff!important}
        #vmd-widget .vmd-body::-webkit-scrollbar{width:3px}
        #vmd-widget .vmd-body::-webkit-scrollbar-thumb{background:#ddd;border-radius:3px}
        #vmd-widget .vmd-prechat{display:flex!important;flex-direction:column!important}
        #vmd-widget .vmd-welcome{text-align:center!important;margin-bottom:24px!important}
        #vmd-widget .vmd-emoji{font-size:42px!important;margin-bottom:8px!important;display:block!important;animation:vmd-wave 1.5s ease-in-out}
        @keyframes vmd-wave{0%,100%{transform:rotate(0)}25%{transform:rotate(20deg)}50%{transform:rotate(-10deg)}75%{transform:rotate(15deg)}}
        #vmd-widget .vmd-welcome h3{font-size:18px!important;font-weight:700!important;color:#1a1a2e!important;margin-bottom:4px!important;background:none!important;border:none!important;text-transform:none!important;letter-spacing:normal!important}
        #vmd-widget .vmd-welcome p{font-size:13px!important;color:#777!important;line-height:1.5!important;margin:0!important}

        /* ═══════ FORM FIELDS ═══════ */
        #vmd-widget .vmd-field{margin-bottom:14px!important}
        #vmd-widget .vmd-field label{display:block!important;font-size:11px!important;font-weight:600!important;color:#888!important;margin-bottom:5px!important;text-transform:uppercase!important;letter-spacing:.3px!important;background:none!important;border:none!important;padding:0!important}
        #vmd-widget .vmd-field label i{margin-right:4px!important;color:var(--vmd-color)!important;font-size:10px!important}
        #vmd-widget .vmd-field input,#vmd-widget .vmd-field textarea{display:block!important;width:100%!important;padding:11px 14px!important;background:#f8f9fb!important;border:1.5px solid #e2e5eb!important;border-radius:10px!important;color:#1a1a2e!important;font-size:13.5px!important;font-family:inherit!important;outline:none!important;transition:all .2s!important;-webkit-appearance:none!important;appearance:none!important;height:auto!important;min-height:0!important;max-width:100%!important;box-shadow:none!important}
        #vmd-widget .vmd-field input:focus,#vmd-widget .vmd-field textarea:focus{border-color:var(--vmd-color)!important;background:#fff!important;box-shadow:0 0 0 3px rgba(102,126,234,.08)!important}
        #vmd-widget .vmd-field input::placeholder,#vmd-widget .vmd-field textarea::placeholder{color:#b5bac8!important}
        #vmd-widget .vmd-field textarea{resize:none!important}

        /* ═══════ START BUTTON ═══════ */
        #vmd-widget .vmd-start-btn{display:block!important;width:100%!important;padding:13px!important;background:var(--vmd-gradient)!important;color:#fff!important;border:none!important;border-radius:12px!important;font-size:14px!important;font-weight:600!important;font-family:inherit!important;cursor:pointer!important;transition:all .3s!important;margin-top:6px!important;text-align:center!important;text-decoration:none!important;letter-spacing:normal!important;text-transform:none!important;line-height:1.5!important;box-shadow:none!important}
        #vmd-widget .vmd-start-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(102,126,234,.35)!important}
        #vmd-widget .vmd-start-btn:active{transform:translateY(0)}
        #vmd-widget .vmd-start-btn:disabled{opacity:.6;cursor:not-allowed!important;transform:none!important;box-shadow:none!important}
        #vmd-widget .vmd-btn-c{display:flex!important;align-items:center!important;justify-content:center!important;gap:8px!important}

        /* ═══════ CHAT MESSAGES ═══════ */
        #vmd-widget .vmd-msgs{flex:1!important;overflow-y:auto!important;padding:16px!important;display:flex!important;flex-direction:column!important;gap:10px!important;background:#f5f6fa!important}
        #vmd-widget .vmd-msgs::-webkit-scrollbar{width:3px}
        #vmd-widget .vmd-msgs::-webkit-scrollbar-track{background:transparent}
        #vmd-widget .vmd-msgs::-webkit-scrollbar-thumb{background:#d0d3da;border-radius:3px}
        #vmd-widget .vmd-msg{display:flex!important;gap:8px!important;max-width:82%!important;animation:vmd-slide .3s ease}
        @keyframes vmd-slide{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
        #vmd-widget .vmd-msg.visitor{align-self:flex-end!important;flex-direction:row-reverse!important}
        #vmd-widget .vmd-msg.admin,#vmd-widget .vmd-msg.system{align-self:flex-start!important}
        #vmd-widget .vmd-msg-av{width:28px!important;height:28px!important;min-width:28px!important;border-radius:50%!important;display:flex!important;align-items:center!important;justify-content:center!important;font-size:11px!important;font-weight:600!important;color:#fff!important;flex-shrink:0!important}
        #vmd-widget .vmd-msg.admin .vmd-msg-av{background:var(--vmd-gradient)!important}
        #vmd-widget .vmd-msg.visitor .vmd-msg-av{display:none!important}
        #vmd-widget .vmd-msg-c{display:flex!important;flex-direction:column!important;gap:2px!important}
        #vmd-widget .vmd-msg-name{font-size:10px!important;font-weight:600!important;color:#999!important;padding:0 4px!important}
        #vmd-widget .vmd-msg-bbl{padding:10px 14px!important;border-radius:16px!important;font-size:13px!important;line-height:1.5!important;word-break:break-word!important}
        #vmd-widget .vmd-msg.visitor .vmd-msg-bbl{background:var(--vmd-gradient)!important;color:#fff!important;border-bottom-right-radius:4px!important}
        #vmd-widget .vmd-msg.admin .vmd-msg-bbl{background:#fff!important;color:#1a1a2e!important;border:1px solid #e8eaef!important;border-bottom-left-radius:4px!important;box-shadow:0 1px 4px rgba(0,0,0,.04)!important}
        #vmd-widget .vmd-msg.system .vmd-msg-bbl{background:#fef9e7!important;color:#b8860b!important;font-size:12px!important;text-align:center!important;border-radius:8px!important;border:1px solid #fcefc7!important;padding:8px 14px!important}
        #vmd-widget .vmd-msg.system{max-width:90%!important;align-self:center!important}
        #vmd-widget .vmd-msg-time{font-size:10px!important;color:#b0b5c3!important;padding:0 4px!important}
        #vmd-widget .vmd-msg.visitor .vmd-msg-time{text-align:right!important}

        /* ═══════ TYPING ═══════ */
        #vmd-widget .vmd-typing{display:flex!important;align-items:center!important;gap:8px!important;padding:6px 16px 10px!important;background:#f5f6fa!important}
        #vmd-widget .vmd-typ-bbl{display:flex!important;gap:3px!important;padding:8px 12px!important;background:#fff!important;border:1px solid #e8eaef!important;border-radius:16px!important;border-bottom-left-radius:4px!important}
        #vmd-widget .vmd-typ-bbl span{width:6px!important;height:6px!important;border-radius:50%!important;background:#bbb!important;animation:vmd-bounce 1.4s infinite}
        #vmd-widget .vmd-typ-bbl span:nth-child(2){animation-delay:.15s}
        #vmd-widget .vmd-typ-bbl span:nth-child(3){animation-delay:.3s}
        @keyframes vmd-bounce{0%,60%,100%{transform:translateY(0);opacity:.4}30%{transform:translateY(-6px);opacity:1}}
        #vmd-widget .vmd-typ-txt{font-size:11px!important;color:#999!important;font-style:italic!important}

        /* ═══════ INPUT AREA ═══════ */
        #vmd-widget .vmd-input-area{border-top:1px solid #eceef2!important;background:#fff!important;flex-shrink:0!important}
        #vmd-widget .vmd-input-wrap{display:flex!important;align-items:flex-end!important;gap:8px!important;padding:10px 14px 8px!important}
        #vmd-widget .vmd-input{flex:1!important;padding:10px 14px!important;background:#f5f6fa!important;border:1.5px solid #e2e5eb!important;border-radius:20px!important;color:#1a1a2e!important;font-size:13px!important;font-family:inherit!important;resize:none!important;outline:none!important;max-height:100px!important;line-height:1.4!important;transition:border-color .2s!important;-webkit-appearance:none!important;appearance:none!important;box-shadow:none!important}
        #vmd-widget .vmd-input:focus{border-color:var(--vmd-color)!important;background:#fff!important}
        #vmd-widget .vmd-input::placeholder{color:#b5bac8!important}
        #vmd-widget .vmd-send{width:36px!important;height:36px!important;min-width:36px!important;border:none!important;background:var(--vmd-gradient)!important;color:#fff!important;border-radius:50%!important;cursor:pointer!important;transition:all .2s;display:flex!important;align-items:center!important;justify-content:center!important;font-size:13px!important}
        #vmd-widget .vmd-send:hover:not(:disabled){transform:scale(1.1);box-shadow:0 4px 14px rgba(102,126,234,.4)}
        #vmd-widget .vmd-send:disabled{opacity:.35!important;cursor:not-allowed!important}

        /* ═══════ POWERED BY ═══════ */
        #vmd-widget .vmd-powered{text-align:center!important;padding:3px 0 7px!important;font-size:10px!important;color:#ccc!important;background:#fff!important}
        #vmd-widget .vmd-powered strong{background:var(--vmd-gradient)!important;-webkit-background-clip:text!important;-webkit-text-fill-color:transparent!important;background-clip:text!important;font-weight:600!important}

        /* ═══════ END SCREEN / RATING ═══════ */
        #vmd-widget .vmd-rating{margin:20px 0!important;display:flex!important;justify-content:center!important;gap:6px!important}
        #vmd-widget .vmd-star{font-size:34px!important;color:#ddd!important;cursor:pointer!important;transition:all .2s;background:none!important;border:none!important;line-height:1!important}
        #vmd-widget .vmd-star:hover,#vmd-widget .vmd-star.active{color:#fbbf24!important;transform:scale(1.2)}
        #vmd-widget .vmd-star.active{text-shadow:0 2px 12px rgba(251,191,36,.4)}

        /* ═══════ IMAGE MESSAGES ═══════ */
        #vmd-widget .vmd-msg-img{max-width:200px!important;max-height:200px!important;border-radius:12px!important;cursor:pointer!important;transition:opacity .2s!important;display:block!important;object-fit:cover!important}
        #vmd-widget .vmd-msg-img:hover{opacity:.85!important}
        #vmd-widget .vmd-msg.visitor .vmd-msg-bbl.has-img{background:transparent!important;padding:0!important}
        #vmd-widget .vmd-msg.admin .vmd-msg-bbl.has-img{background:transparent!important;border:none!important;box-shadow:none!important;padding:0!important}
        #vmd-widget .vmd-attach{width:36px!important;height:36px!important;min-width:36px!important;border:none!important;background:none!important;color:#b0b5c3!important;cursor:pointer!important;transition:all .2s;display:flex!important;align-items:center!important;justify-content:center!important;font-size:16px!important;border-radius:50%!important}
        #vmd-widget .vmd-attach:hover{color:var(--vmd-color)!important;background:#f0f1f5!important}
        #vmd-widget .vmd-upload-preview{padding:8px 14px!important;background:#f5f6fa!important;border-top:1px solid #eceef2!important;display:flex!important;align-items:center!important;gap:10px!important}
        #vmd-widget .vmd-upload-preview img{width:60px!important;height:60px!important;object-fit:cover!important;border-radius:8px!important;border:1px solid #e2e5eb!important}
        #vmd-widget .vmd-upload-preview .vmd-preview-info{flex:1!important;font-size:12px!important;color:#666!important}
        #vmd-widget .vmd-upload-preview .vmd-preview-remove{width:24px!important;height:24px!important;border-radius:50%!important;border:none!important;background:#ef4444!important;color:#fff!important;cursor:pointer!important;font-size:11px!important;display:flex!important;align-items:center!important;justify-content:center!important}
        #vmd-lightbox{position:fixed!important;top:0!important;left:0!important;width:100%!important;height:100%!important;background:rgba(0,0,0,.85)!important;z-index:9999999!important;display:flex!important;align-items:center!important;justify-content:center!important;cursor:zoom-out!important;animation:vmd-fade .2s ease!important}
        @keyframes vmd-fade{from{opacity:0}to{opacity:1}}
        #vmd-lightbox img{max-width:90%!important;max-height:90%!important;border-radius:8px!important;box-shadow:0 8px 32px rgba(0,0,0,.4)!important;object-fit:contain!important}
    `;
    document.head.appendChild(css);

    // Load Inter font
    if (!document.querySelector('link[href*="fonts.googleapis.com/css2?family=Inter"]')) {
        const font = document.createElement('link');
        font.rel = 'stylesheet';
        font.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap';
        document.head.appendChild(font);
    }

    // Load Font Awesome
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const fa = document.createElement('link');
        fa.rel = 'stylesheet';
        fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';
        document.head.appendChild(fa);
    }

    // ═══════ CREATE ELEMENTS ═══════
    // Button
    const btn = document.createElement('button');
    btn.id = 'vmd-btn';
    btn.innerHTML = `
        <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/><circle cx="8" cy="10" r="1.2"/><circle cx="12" cy="10" r="1.2"/><circle cx="16" cy="10" r="1.2"/></svg>
        <span class="vmd-badge" id="vmd-badge">0</span>
    `;
    document.body.appendChild(btn);

    // Greeting
    const greeting = document.createElement('div');
    greeting.id = 'vmd-greeting';
    greeting.innerHTML = `<button class="vmd-close-g" onclick="this.parentElement.classList.remove('show')">&times;</button>Merhaba! Size yardımcı olabilir miyiz? 💬`;
    document.body.appendChild(greeting);

    // Widget container
    const widget = document.createElement('div');
    widget.id = 'vmd-widget';
    widget.style.setProperty('--vmd-color', '#667eea');
    widget.style.setProperty('--vmd-gradient', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
    document.body.appendChild(widget);

    // ═══════ RENDER FUNCTIONS ═══════
    function renderPreChat() {
        const online = config.is_online;
        const company = escapeHtml(config.company_name || 'VMDestek');
        const welcomeMsg = escapeHtml(config.welcome_message || 'Size nasıl yardımcı olabiliriz?');
        const offlineMsg = escapeHtml(config.offline_message || 'Şu anda çevrimdışıyız. Lütfen mesajınızı bırakın, en kısa sürede dönüş yapacağız.');

        widget.innerHTML = `
        <div class="vmd-screen" id="vmd-prechat">
            <div class="vmd-header">
                <div class="vmd-hdr-content">
                    <div class="vmd-hdr-icon"><i class="fas fa-headset"></i></div>
                    <div class="vmd-hdr-text">
                        <h2>${company}</h2>
                        <div class="vmd-status">
                            <span class="vmd-dot ${online ? 'online' : 'offline'}"></span>
                            <span>${online ? 'Çevrimiçi' : 'Çevrimdışı'}</span>
                        </div>
                    </div>
                </div>
                <div class="vmd-hdr-actions">
                    <button class="vmd-hdr-btn" id="vmd-minimize-btn" title="Küçült"><i class="fas fa-chevron-down"></i></button>
                </div>
                <div class="vmd-hdr-wave"><svg viewBox="0 0 400 30" preserveAspectRatio="none"><path d="M0,15 C100,25 200,5 300,15 C350,20 380,18 400,15 L400,30 L0,30 Z" fill="white"/></svg></div>
            </div>
            <div class="vmd-body vmd-prechat">
                <div class="vmd-welcome">
                    ${online
                ? `<div class="vmd-emoji">👋</div><h3>Merhaba!</h3><p>${welcomeMsg}</p>`
                : `<div class="vmd-emoji">📨</div><h3>Mesaj Bırakın</h3><p>${offlineMsg}</p>`
            }
                </div>
                <form id="vmd-form">
                    <div class="vmd-field">
                        <label><i class="fas fa-user"></i> Adınız</label>
                        <input type="text" id="vmd-name" placeholder="Adınızı girin" required>
                    </div>
                    <div class="vmd-field">
                        <label><i class="fas fa-envelope"></i> E-posta ${online ? '<span style="opacity:.5">(opsiyonel)</span>' : '<span style="opacity:.8;color:var(--vmd-color)">(gerekli)</span>'}</label>
                        <input type="email" id="vmd-email" placeholder="E-posta adresiniz" ${online ? '' : 'required'}>
                    </div>
                    ${!online ? `<div class="vmd-field"><label><i class="fas fa-comment-alt"></i> Mesajınız</label><textarea id="vmd-offline-msg" placeholder="Mesajınızı yazın..." rows="3" required></textarea></div>` : ''}
                    <button type="submit" class="vmd-start-btn" id="vmd-start">
                        <span class="vmd-btn-c">${online ? '<i class="fas fa-comment-dots"></i> Sohbet Başlat' : '<i class="fas fa-paper-plane"></i> Mesaj Gönder'}</span>
                    </button>
                </form>
            </div>
        </div>`;

        // Bind form submit
        document.getElementById('vmd-form').addEventListener('submit', online ? startChat : submitOfflineMessage);
        document.getElementById('vmd-minimize-btn').addEventListener('click', toggleWidget);
    }

    function renderChatScreen() {
        const company = escapeHtml(config.company_name || 'VMDestek');

        widget.innerHTML = `
        <div class="vmd-screen" id="vmd-chat">
            <div class="vmd-header compact">
                <div class="vmd-hdr-content">
                    <div class="vmd-hdr-icon small"><i class="fas fa-headset"></i></div>
                    <div class="vmd-hdr-text">
                        <h2>${company}</h2>
                        <div class="vmd-status"><span class="vmd-dot online"></span><span id="vmd-chat-status">Bağlandı</span></div>
                    </div>
                </div>
                <div class="vmd-hdr-actions">
                    <button class="vmd-hdr-btn" id="vmd-min2" title="Küçült"><i class="fas fa-chevron-down"></i></button>
                    <button class="vmd-hdr-btn" id="vmd-end" title="Sohbeti Bitir"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="vmd-msgs" id="vmd-messages"></div>
            <div class="vmd-typing" id="vmd-typing" style="display:none">
                <div class="vmd-typ-bbl"><span></span><span></span><span></span></div>
                <span class="vmd-typ-txt">Temsilci yazıyor...</span>
            </div>
            <div class="vmd-input-area">
                <div class="vmd-upload-preview" id="vmd-upload-preview" style="display:none"></div>
                <div class="vmd-input-wrap">
                    <button class="vmd-attach" id="vmd-attach-btn" title="Resim Gönder"><i class="fas fa-paperclip"></i></button>
                    <input type="file" id="vmd-file-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                    <textarea class="vmd-input" id="vmd-input" placeholder="Mesajınızı yazın..." rows="1"></textarea>
                    <button class="vmd-send" id="vmd-send-btn" disabled><i class="fas fa-paper-plane"></i></button>
                </div>
                <div class="vmd-powered"><span>Powered by <strong>VMDestek</strong></span></div>
            </div>
        </div>`;

        document.getElementById('vmd-min2').addEventListener('click', toggleWidget);
        document.getElementById('vmd-end').addEventListener('click', endChat);
        document.getElementById('vmd-send-btn').addEventListener('click', sendMessage);
        const input = document.getElementById('vmd-input');
        input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } });
        input.addEventListener('input', onInputChange);

        // Image upload
        document.getElementById('vmd-attach-btn').addEventListener('click', () => document.getElementById('vmd-file-input').click());
        document.getElementById('vmd-file-input').addEventListener('change', handleFileSelect);
    }

    function renderEndScreen() {
        const company = escapeHtml(config.company_name || 'VMDestek');

        widget.innerHTML = `
        <div class="vmd-screen" id="vmd-end-screen">
            <div class="vmd-header">
                <div class="vmd-hdr-content">
                    <div class="vmd-hdr-icon"><i class="fas fa-headset"></i></div>
                    <div class="vmd-hdr-text">
                        <h2>${company}</h2>
                        <div class="vmd-status"><span>Sohbet sona erdi</span></div>
                    </div>
                </div>
                <div class="vmd-hdr-actions">
                    <button class="vmd-hdr-btn" id="vmd-min3" title="Küçült"><i class="fas fa-chevron-down"></i></button>
                </div>
                <div class="vmd-hdr-wave"><svg viewBox="0 0 400 30" preserveAspectRatio="none"><path d="M0,15 C100,25 200,5 300,15 C350,20 380,18 400,15 L400,30 L0,30 Z" fill="white"/></svg></div>
            </div>
            <div class="vmd-body vmd-prechat" style="text-align:center;padding-top:40px">
                <div class="vmd-emoji">🙏</div>
                <h3>Teşekkürler!</h3>
                <p style="margin-bottom:24px">Sohbetiniz sona erdi. Nasıl bir deneyim yaşadınız?</p>
                <div class="vmd-rating" id="vmd-rating">
                    <button class="vmd-star" data-r="1" onclick="window.__vmd_rate(1)">★</button>
                    <button class="vmd-star" data-r="2" onclick="window.__vmd_rate(2)">★</button>
                    <button class="vmd-star" data-r="3" onclick="window.__vmd_rate(3)">★</button>
                    <button class="vmd-star" data-r="4" onclick="window.__vmd_rate(4)">★</button>
                    <button class="vmd-star" data-r="5" onclick="window.__vmd_rate(5)">★</button>
                </div>
                <button class="vmd-start-btn" id="vmd-reset" style="margin-top:24px">
                    <span class="vmd-btn-c"><i class="fas fa-redo"></i> Yeni Sohbet</span>
                </button>
            </div>
        </div>`;

        document.getElementById('vmd-min3').addEventListener('click', toggleWidget);
        document.getElementById('vmd-reset').addEventListener('click', resetWidget);
    }

    // ═══════ TOGGLE ═══════
    function toggleWidget() {
        isOpen = !isOpen;
        btn.classList.toggle('open', isOpen);
        widget.classList.toggle('open', isOpen);

        if (isOpen) {
            greeting.classList.remove('show');
            clearTimeout(greetingTimer);
        } else {
            greetingTimer = setTimeout(() => {
                greeting.classList.add('show');
                setTimeout(() => greeting.classList.remove('show'), 10000);
            }, 2000);
        }
    }

    btn.addEventListener('click', toggleWidget);

    greeting.addEventListener('click', e => {
        if (e.target.className !== 'vmd-close-g') {
            greeting.classList.remove('show');
            if (!isOpen) toggleWidget();
        }
    });

    // ═══════ CHAT LOGIC ═══════
    async function startChat(e) {
        e.preventDefault();
        const name = document.getElementById('vmd-name').value.trim();
        const email = document.getElementById('vmd-email').value.trim();
        const startBtn = document.getElementById('vmd-start');
        if (!name) return;

        startBtn.disabled = true;
        startBtn.innerHTML = '<span class="vmd-btn-c"><i class="fas fa-spinner fa-spin"></i> Bağlanıyor...</span>';

        try {
            const res = await fetch(`${BASE_URL}/api/chat.php?action=start`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, page: location.href, site_url: location.origin })
            });
            const data = await res.json();
            if (data.success) {
                sessionId = data.session_id;
                conversationId = data.conversation_id;
                localStorage.setItem('livesupport_session', JSON.stringify({ session_id: sessionId, conversation_id: conversationId }));
                renderChatScreen();
                await loadMessages();
                startPolling();
            } else throw new Error(data.error || 'Bağlantı hatası');
        } catch (err) {
            startBtn.disabled = false;
            startBtn.innerHTML = '<span class="vmd-btn-c"><i class="fas fa-comment-dots"></i> Sohbet Başlat</span>';
            alert(err.message);
        }
    }

    async function submitOfflineMessage(e) {
        e.preventDefault();
        const name = document.getElementById('vmd-name').value.trim();
        const email = document.getElementById('vmd-email').value.trim();
        const message = document.getElementById('vmd-offline-msg').value.trim();
        const startBtn = document.getElementById('vmd-start');
        if (!name || !email || !message) return;

        startBtn.disabled = true;
        startBtn.innerHTML = '<span class="vmd-btn-c"><i class="fas fa-spinner fa-spin"></i> Gönderiliyor...</span>';

        try {
            const res = await fetch(`${BASE_URL}/api/chat.php?action=start`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, page: location.href, site_url: location.origin })
            });
            const data = await res.json();
            if (data.success) {
                await fetch(`${BASE_URL}/api/chat.php?action=send`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: data.session_id, message })
                });
                renderEndScreen();
            } else throw new Error(data.error || 'Gönderme hatası');
        } catch (err) {
            startBtn.disabled = false;
            startBtn.innerHTML = '<span class="vmd-btn-c"><i class="fas fa-paper-plane"></i> Mesaj Gönder</span>';
            alert(err.message);
        }
    }

    // Messages
    async function loadMessages() {
        if (!sessionId) return;
        try {
            const res = await fetch(`${BASE_URL}/api/chat.php?action=messages&session_id=${sessionId}&after_id=${lastMsgId}`);
            const data = await res.json();
            if (data.success && data.messages) {
                renderMessages(data.messages);
                const typ = document.getElementById('vmd-typing');
                if (typ) typ.style.display = data.admin_typing ? 'flex' : 'none';
            }
        } catch (err) { }
    }

    function renderMessages(messages) {
        const container = document.getElementById('vmd-messages');
        if (!container) return;

        messages.forEach(msg => {
            const msgId = parseInt(msg.id);
            if (msgId > 0 && container.querySelector(`.vmd-msg[data-id="${msgId}"]`)) return;
            if (msgId > 0 && msgId < 999999999 && msgId > lastMsgId) lastMsgId = msgId;

            const div = document.createElement('div');
            div.className = `vmd-msg ${msg.sender_type}`;
            div.dataset.id = msg.id;

            if (msg.sender_type === 'system') {
                div.innerHTML = `<div class="vmd-msg-c"><div class="vmd-msg-bbl">${escapeHtml(msg.message)}</div></div>`;
            } else {
                const showAv = msg.sender_type === 'admin';
                const init = msg.sender_name ? msg.sender_name[0].toUpperCase() : '?';
                const isImage = msg.message_type === 'image';
                let bubbleContent;
                if (isImage) {
                    const imgUrl = msg.message.startsWith('http') ? msg.message : `${BASE_URL}/${msg.message}`;
                    bubbleContent = `<div class="vmd-msg-bbl has-img"><img class="vmd-msg-img" src="${imgUrl}" alt="Resim" onclick="window.__vmd_lightbox('${imgUrl}')" loading="lazy"></div>`;
                } else {
                    bubbleContent = `<div class="vmd-msg-bbl">${formatMsg(msg.message)}</div>`;
                }
                div.innerHTML = `
                    ${showAv ? `<div class="vmd-msg-av">${init}</div>` : ''}
                    <div class="vmd-msg-c">
                        ${msg.sender_type === 'admin' ? `<span class="vmd-msg-name">${escapeHtml(msg.sender_name || 'Temsilci')}</span>` : ''}
                        ${bubbleContent}
                        <span class="vmd-msg-time">${formatTime(msg.created_at)}</span>
                    </div>`;
            }
            container.appendChild(div);
        });
        container.scrollTop = container.scrollHeight;
    }

    // Send
    async function sendMessage() {
        // Check for pending image upload first
        if (pendingFile) {
            await uploadImageFile();
            return;
        }

        const input = document.getElementById('vmd-input');
        const message = input.value.trim();
        if (!message || !sessionId) return;

        input.value = '';
        input.style.height = 'auto';
        document.getElementById('vmd-send-btn').disabled = true;
        setVisitorTyping(false);

        const tempId = tempIdCounter--;
        renderMessages([{ id: tempId, sender_type: 'visitor', sender_name: '', message, message_type: 'text', created_at: new Date().toISOString() }]);

        try {
            const res = await fetch(`${BASE_URL}/api/chat.php?action=send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionId, message })
            });
            const data = await res.json();
            if (data.success && data.message_id) {
                lastMsgId = Math.max(lastMsgId, data.message_id);
                const tempEl = document.querySelector(`.vmd-msg[data-id="${tempId}"]`);
                if (tempEl) tempEl.dataset.id = data.message_id;
            }
        } catch (err) { }
        input.focus();
    }

    // Image upload
    let pendingFile = null;

    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Dosya boyutu çok büyük (max 5MB)');
            e.target.value = '';
            return;
        }

        const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) {
            alert('Sadece resim dosyaları yüklenebilir (jpg, png, gif, webp)');
            e.target.value = '';
            return;
        }

        pendingFile = file;
        showUploadPreview(file);
    }

    function showUploadPreview(file) {
        const preview = document.getElementById('vmd-upload-preview');
        if (!preview) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Önizleme">
                <div class="vmd-preview-info">${escapeHtml(file.name)}<br><small>${(file.size / 1024).toFixed(0)} KB</small></div>
                <button class="vmd-preview-remove" id="vmd-preview-cancel" title="İptal"><i class="fas fa-times"></i></button>
            `;
            preview.style.display = 'flex';
            document.getElementById('vmd-preview-cancel').addEventListener('click', cancelUpload);
            document.getElementById('vmd-send-btn').disabled = false;
        };
        reader.readAsDataURL(file);
    }

    function cancelUpload() {
        pendingFile = null;
        const preview = document.getElementById('vmd-upload-preview');
        if (preview) { preview.style.display = 'none'; preview.innerHTML = ''; }
        const fileInput = document.getElementById('vmd-file-input');
        if (fileInput) fileInput.value = '';
        const input = document.getElementById('vmd-input');
        document.getElementById('vmd-send-btn').disabled = !(input && input.value.trim());
    }


    async function uploadImageFile() {
        if (!pendingFile || !sessionId) return;
        const file = pendingFile;
        cancelUpload();

        const tempId = tempIdCounter--;
        const tempUrl = URL.createObjectURL(file);
        renderMessages([{ id: tempId, sender_type: 'visitor', sender_name: '', message: tempUrl, message_type: 'image', created_at: new Date().toISOString() }]);

        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('image', file);

        try {
            const res = await fetch(`${BASE_URL}/api/chat.php?action=upload`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success && data.message_id) {
                lastMsgId = Math.max(lastMsgId, data.message_id);
                const tempEl = document.querySelector(`.vmd-msg[data-id="${tempId}"]`);
                if (tempEl) {
                    tempEl.dataset.id = data.message_id;
                    const img = tempEl.querySelector('.vmd-msg-img');
                    if (img) {
                        const realUrl = `${BASE_URL}/${data.image_url}`;
                        img.src = realUrl;
                        img.onclick = () => window.__vmd_lightbox(realUrl);
                    }
                }
            }
        } catch (err) { console.error('Upload error:', err); }
    }

    // Lightbox
    window.__vmd_lightbox = function (src) {
        const existing = document.getElementById('vmd-lightbox');
        if (existing) existing.remove();
        const lb = document.createElement('div');
        lb.id = 'vmd-lightbox';
        lb.innerHTML = `<img src="${src}" alt="Resim">`;
        lb.addEventListener('click', () => lb.remove());
        document.body.appendChild(lb);
    }

    function onInputChange() {
        const input = document.getElementById('vmd-input');
        const sendBtn = document.getElementById('vmd-send-btn');
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
        sendBtn.disabled = !input.value.trim();

        if (!isVisitorTyping && sessionId) setVisitorTyping(true);
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => setVisitorTyping(false), 2000);
    }

    async function setVisitorTyping(typing) {
        if (!sessionId) return;
        isVisitorTyping = typing;
        try {
            await fetch(`${BASE_URL}/api/chat.php?action=typing`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionId, typing })
            });
        } catch (err) { }
    }

    // Polling
    function startPolling() {
        if (isPolling) return;
        isPolling = true;
        doPoll();
    }

    function stopPolling() {
        isPolling = false;
        if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
    }

    async function doPoll() {
        if (!isPolling || !sessionId) return;
        try {
            const res = await fetch(`${BASE_URL}/api/chat.php?action=messages&session_id=${sessionId}&after_id=${lastMsgId}`);
            const data = await res.json();
            if (data.success) {
                if (data.messages && data.messages.length > 0) {
                    const newMsgs = data.messages.filter(m => m.id > lastMsgId);
                    if (newMsgs.length > 0) {
                        renderMessages(newMsgs);
                        if (newMsgs.some(m => m.sender_type === 'admin')) playNotif();
                    }
                }
                const typ = document.getElementById('vmd-typing');
                if (typ) typ.style.display = data.admin_typing ? 'flex' : 'none';
            }
        } catch (err) { }
        pollTimer = setTimeout(doPoll, 2000);
    }

    // End chat
    async function endChat() {
        if (!confirm('Sohbeti bitirmek istediğinize emin misiniz?')) return;
        stopPolling();
        try {
            await fetch(`${BASE_URL}/api/chat.php?action=end`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionId })
            });
        } catch (err) { }
        renderEndScreen();
    }

    // Rating
    window.__vmd_rate = async function (rating) {
        document.querySelectorAll('.vmd-star').forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.r) <= rating);
        });
        try {
            await fetch(`${BASE_URL}/api/chat.php?action=rate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionId, rating })
            });
        } catch (err) { }
    };

    // Reset
    function resetWidget() {
        localStorage.removeItem('livesupport_session');
        sessionId = null;
        conversationId = null;
        lastMsgId = 0;
        // Re-fetch config to get fresh online status
        fetchConfig();
    }

    // ═══════ HELPERS ═══════
    function playNotif() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.setValueAtTime(1200, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) { }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatMsg(text) {
        text = escapeHtml(text);
        text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color:inherit;text-decoration:underline">$1</a>');
        text = text.replace(/\n/g, '<br>');
        return text;
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
    }

    // ═══════ INIT ═══════
    function fetchConfig() {
        fetch(`${BASE_URL}/api/chat.php?action=status`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.config) {
                    config = data.config;
                    const c1 = config.widget_color || '#667eea';
                    const c2 = config.widget_gradient_end || '#764ba2';

                    widget.style.setProperty('--vmd-color', c1);
                    widget.style.setProperty('--vmd-gradient', `linear-gradient(135deg, ${c1} 0%, ${c2} 100%)`);
                    btn.style.background = `linear-gradient(135deg, ${c1} 0%, ${c2} 100%)`;
                    btn.style.boxShadow = `0 4px 24px ${c1}66`;

                    // Update greeting
                    if (config.is_online) {
                        greeting.innerHTML = `<button class="vmd-close-g" onclick="this.parentElement.classList.remove('show')">&times;</button>${escapeHtml(config.welcome_message || 'Merhaba! Size yardımcı olabilir miyiz?')} 💬`;
                    } else {
                        greeting.innerHTML = `<button class="vmd-close-g" onclick="this.parentElement.classList.remove('show')">&times;</button>📨 ${escapeHtml(config.offline_message || 'Şu anda çevrimdışıyız. Mesajınızı bırakabilirsiniz.')}`;
                    }

                    // Render widget
                    renderPreChat();

                    // Restore session
                    const saved = localStorage.getItem('livesupport_session');
                    if (saved) {
                        try {
                            const s = JSON.parse(saved);
                            if (s.session_id && s.conversation_id) {
                                sessionId = s.session_id;
                                conversationId = s.conversation_id;
                                renderChatScreen();
                                loadMessages();
                                startPolling();
                            }
                        } catch (e) { }
                    }
                }
            })
            .catch(() => { renderPreChat(); });
    }

    fetchConfig();

    // Show greeting
    greetingTimer = setTimeout(() => {
        if (!isOpen) {
            greeting.classList.add('show');
            setTimeout(() => greeting.classList.remove('show'), 10000);
        }
    }, 3000);

    // ═══════ VISITOR HEARTBEAT ═══════
    (function () {
        let uid = localStorage.getItem('vmdestek_visitor_uid');
        if (!uid) {
            uid = 'v_' + Date.now() + '_' + Math.random().toString(36).substring(2, 10);
            localStorage.setItem('vmdestek_visitor_uid', uid);
        }
        function beat() {
            fetch(`${BASE_URL}/api/visitor.php?action=heartbeat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ visitor_uid: uid, page_url: location.href, page_title: document.title || '', referrer: document.referrer || '' })
            }).catch(() => { });
        }
        beat();
        setInterval(beat, 15000);
    })();
})();
