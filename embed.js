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
        #vmd-btn{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;cursor:pointer;box-shadow:0 4px 24px rgba(102,126,234,0.4);z-index:999998;display:flex;align-items:center;justify-content:center;transition:all .3s cubic-bezier(.175,.885,.32,1.275);outline:none}
        #vmd-btn:hover{transform:scale(1.08);box-shadow:0 6px 32px rgba(102,126,234,0.5)}
        #vmd-btn svg{width:28px;height:28px;fill:#fff;transition:all .3s}
        #vmd-btn.open{opacity:0;pointer-events:none;transform:scale(.5)}
        #vmd-btn::before{content:'';position:absolute;width:100%;height:100%;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);animation:vmd-pulse 2s ease-out infinite;z-index:-1}
        @keyframes vmd-pulse{0%{transform:scale(1);opacity:.5}100%{transform:scale(1.6);opacity:0}}
        #vmd-btn .vmd-badge{position:absolute;top:-4px;right:-4px;width:22px;height:22px;background:#ef4444;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:none;align-items:center;justify-content:center;border:2px solid #fff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
        #vmd-btn .vmd-badge.show{display:flex}
        #vmd-widget{position:fixed;bottom:96px;right:24px;width:${W}px;height:${H}px;max-height:calc(100vh - 120px);z-index:999999;border-radius:16px;overflow:hidden;box-shadow:0 12px 48px rgba(0,0,0,.15),0 4px 16px rgba(0,0,0,.1);opacity:0;transform:translateY(20px) scale(.95);transition:all .3s cubic-bezier(.175,.885,.32,1.275);pointer-events:none;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;line-height:1.5;color:#1a1a2e;display:flex;flex-direction:column;background:#fff}
        #vmd-widget.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all;bottom:24px}
        #vmd-widget *{margin:0;padding:0;box-sizing:border-box}
        @media(max-width:480px){#vmd-widget{bottom:0!important;right:0;left:0;width:100%;height:100%;max-height:100vh;border-radius:0}#vmd-btn{bottom:16px;right:16px}}
        #vmd-greeting{position:fixed;bottom:92px;right:24px;background:#fff;color:#1a1a2e;padding:12px 20px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.12);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;max-width:260px;z-index:999997;opacity:0;transform:translateY(10px);transition:all .3s ease;pointer-events:none}
        #vmd-greeting.show{opacity:1;transform:translateY(0);pointer-events:all}
        #vmd-greeting::after{content:'';position:absolute;bottom:-6px;right:24px;width:12px;height:12px;background:#fff;transform:rotate(45deg);box-shadow:2px 2px 4px rgba(0,0,0,.05)}
        #vmd-greeting .vmd-close-g{position:absolute;top:4px;right:8px;background:none;border:none;color:#999;cursor:pointer;font-size:14px;padding:2px 4px}
        .vmd-screen{display:flex;flex-direction:column;height:100%}
        .vmd-header{background:var(--vmd-gradient);padding:24px 20px 40px;position:relative;flex-shrink:0}
        .vmd-header.compact{padding:14px 16px;display:flex;align-items:center;justify-content:space-between}
        .vmd-hdr-content{display:flex;align-items:center;gap:14px;position:relative;z-index:2}
        .vmd-hdr-icon{width:48px;height:48px;background:rgba(255,255,255,.2);backdrop-filter:blur(10px);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff}
        .vmd-hdr-icon.small{width:36px;height:36px;border-radius:10px;font-size:16px}
        .vmd-hdr-text h2{color:#fff;font-size:17px;font-weight:700;margin-bottom:2px}
        .compact .vmd-hdr-text h2{font-size:14px}
        .vmd-status{display:flex;align-items:center;gap:6px;color:rgba(255,255,255,.85);font-size:12px}
        .vmd-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.4)}
        .vmd-dot.online{background:#4ade80;box-shadow:0 0 6px rgba(74,222,128,.6);animation:vmd-p 2s ease infinite}
        .vmd-dot.offline{background:#fbbf24}
        @keyframes vmd-p{0%,100%{opacity:1}50%{opacity:.5}}
        .vmd-hdr-wave{position:absolute;bottom:-1px;left:0;right:0;z-index:1}
        .vmd-hdr-wave svg{width:100%;height:30px;display:block}
        .vmd-hdr-actions{display:flex;gap:8px;position:absolute;top:16px;right:16px;z-index:3}
        .compact .vmd-hdr-actions{position:static}
        .vmd-hdr-btn{width:32px;height:32px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.1);color:#fff;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;font-size:13px}
        .vmd-hdr-btn:hover{background:rgba(255,255,255,.2)}
        .vmd-body{flex:1;overflow-y:auto;padding:20px}
        .vmd-prechat{display:flex;flex-direction:column}
        .vmd-welcome{text-align:center;margin-bottom:28px}
        .vmd-emoji{font-size:48px;margin-bottom:12px;animation:vmd-wave 1.5s ease-in-out}
        @keyframes vmd-wave{0%,100%{transform:rotate(0)}25%{transform:rotate(20deg)}50%{transform:rotate(-10deg)}75%{transform:rotate(15deg)}}
        .vmd-welcome h3{font-size:20px;font-weight:700;color:#1a1a2e;margin-bottom:6px}
        .vmd-welcome p{font-size:14px;color:#666;line-height:1.5}
        .vmd-field{margin-bottom:16px}
        .vmd-field label{display:block;font-size:12px;font-weight:500;color:#555;margin-bottom:6px}
        .vmd-field label i{margin-right:4px;color:var(--vmd-color);font-size:11px}
        .vmd-field input,.vmd-field textarea{width:100%;padding:12px 14px;background:#f8f9fb;border:2px solid #e8eaef;border-radius:10px;color:#1a1a2e;font-size:14px;font-family:inherit;outline:none;transition:all .2s}
        .vmd-field input:focus,.vmd-field textarea:focus{border-color:var(--vmd-color);background:#fff;box-shadow:0 0 0 3px rgba(102,126,234,.1)}
        .vmd-field input::placeholder,.vmd-field textarea::placeholder{color:#aab0c0}
        .vmd-field textarea{resize:none}
        .vmd-start-btn{width:100%;padding:14px;background:var(--vmd-gradient);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:600;font-family:inherit;cursor:pointer;transition:all .3s;margin-top:8px}
        .vmd-start-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(102,126,234,.35)}
        .vmd-start-btn:active{transform:translateY(0)}
        .vmd-btn-c{display:flex;align-items:center;justify-content:center;gap:8px}
        .vmd-msgs{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#f8f9fb}
        .vmd-msgs::-webkit-scrollbar{width:4px}
        .vmd-msgs::-webkit-scrollbar-track{background:transparent}
        .vmd-msgs::-webkit-scrollbar-thumb{background:#ddd;border-radius:4px}
        .vmd-msg{display:flex;gap:8px;max-width:85%;animation:vmd-slide .3s ease}
        @keyframes vmd-slide{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        .vmd-msg.visitor{align-self:flex-end;flex-direction:row-reverse}
        .vmd-msg.admin,.vmd-msg.system{align-self:flex-start}
        .vmd-msg-av{width:28px;height:28px;min-width:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;color:#fff;flex-shrink:0}
        .vmd-msg.admin .vmd-msg-av{background:var(--vmd-gradient)}
        .vmd-msg.visitor .vmd-msg-av{display:none}
        .vmd-msg-c{display:flex;flex-direction:column;gap:3px}
        .vmd-msg-name{font-size:10px;font-weight:600;color:#999;padding:0 4px}
        .vmd-msg-bbl{padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word}
        .vmd-msg.visitor .vmd-msg-bbl{background:var(--vmd-gradient);color:#fff;border-bottom-right-radius:4px}
        .vmd-msg.admin .vmd-msg-bbl{background:#fff;color:#1a1a2e;border:1px solid #e8eaef;border-bottom-left-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        .vmd-msg.system .vmd-msg-bbl{background:#fff9e6;color:#b8860b;font-size:12px;text-align:center;border-radius:8px;border:1px solid #ffeeba;padding:8px 12px;max-width:100%;align-self:center}
        .vmd-msg.system{max-width:90%;align-self:center}
        .vmd-msg-time{font-size:10px;color:#aaa;padding:0 4px}
        .vmd-msg.visitor .vmd-msg-time{text-align:right}
        .vmd-typing{display:flex;align-items:center;gap:8px;padding:6px 16px 10px;background:#f8f9fb}
        .vmd-typ-bbl{display:flex;gap:3px;padding:8px 12px;background:#fff;border:1px solid #e8eaef;border-radius:16px;border-bottom-left-radius:4px}
        .vmd-typ-bbl span{width:6px;height:6px;border-radius:50%;background:#bbb;animation:vmd-bounce 1.4s infinite}
        .vmd-typ-bbl span:nth-child(2){animation-delay:.15s}
        .vmd-typ-bbl span:nth-child(3){animation-delay:.3s}
        @keyframes vmd-bounce{0%,60%,100%{transform:translateY(0);opacity:.4}30%{transform:translateY(-6px);opacity:1}}
        .vmd-typ-txt{font-size:11px;color:#999;font-style:italic}
        .vmd-input-area{border-top:1px solid #e8eaef;background:#fff;flex-shrink:0}
        .vmd-input-wrap{display:flex;align-items:flex-end;gap:8px;padding:10px 12px 6px}
        .vmd-input{flex:1;padding:10px 14px;background:#f8f9fb;border:2px solid #e8eaef;border-radius:20px;color:#1a1a2e;font-size:13px;font-family:inherit;resize:none;outline:none;max-height:100px;line-height:1.4;transition:border-color .2s}
        .vmd-input:focus{border-color:var(--vmd-color)}
        .vmd-input::placeholder{color:#aab0c0}
        .vmd-send{width:38px;height:38px;min-width:38px;border:none;background:var(--vmd-gradient);color:#fff;border-radius:50%;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;font-size:14px}
        .vmd-send:hover:not(:disabled){transform:scale(1.08);box-shadow:0 4px 12px rgba(102,126,234,.4)}
        .vmd-send:disabled{opacity:.4;cursor:not-allowed}
        .vmd-powered{text-align:center;padding:4px 0 8px;font-size:10px;color:#ccc}
        .vmd-powered strong{background:var(--vmd-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-weight:600}
        .vmd-rating{margin:16px 0;display:flex;justify-content:center;gap:8px}
        .vmd-star{font-size:36px;color:#ddd;cursor:pointer;transition:all .2s;background:none;border:none}
        .vmd-star:hover,.vmd-star.active{color:#fbbf24;transform:scale(1.15)}
        .vmd-star.active{text-shadow:0 2px 12px rgba(251,191,36,.4)}
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
                <div class="vmd-input-wrap">
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
                div.innerHTML = `
                    ${showAv ? `<div class="vmd-msg-av">${init}</div>` : ''}
                    <div class="vmd-msg-c">
                        ${msg.sender_type === 'admin' ? `<span class="vmd-msg-name">${escapeHtml(msg.sender_name || 'Temsilci')}</span>` : ''}
                        <div class="vmd-msg-bbl">${formatMsg(msg.message)}</div>
                        <span class="vmd-msg-time">${formatTime(msg.created_at)}</span>
                    </div>`;
            }
            container.appendChild(div);
        });
        container.scrollTop = container.scrollHeight;
    }

    // Send
    async function sendMessage() {
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
