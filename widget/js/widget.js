/**
 * VMDestek Chat Widget - JavaScript
 */

// ============ STATE ============
let sessionId = null;
let conversationId = null;
let lastMsgId = 0;
let isPolling = false;
let pollTimer = null;
let typingTimer = null;
let isVisitorTyping = false;

// ============ MINIMIZE ============
function minimizeWidget() {
    // Send message to parent (embed.js) to close the widget
    if (window.parent !== window) {
        window.parent.postMessage({ type: 'vmdestek-minimize' }, '*');
    }
}

// ============ INIT ============
document.addEventListener('DOMContentLoaded', () => {
    // Try to restore session
    const saved = localStorage.getItem('livesupport_session');
    if (saved) {
        try {
            const data = JSON.parse(saved);
            if (data.session_id && data.conversation_id) {
                sessionId = data.session_id;
                conversationId = data.conversation_id;
                // Switch to chat screen
                document.getElementById('screenPreChat').style.display = 'none';
                document.getElementById('screenChat').style.display = 'flex';
                // Load existing messages
                loadMessages();
                startPolling();
            }
        } catch (e) { }
    }
});

// ============ START CHAT ============
async function startChat(e) {
    e.preventDefault();

    const name = document.getElementById('visitorNameInput').value.trim();
    const email = document.getElementById('visitorEmailInput').value.trim();
    const btn = document.getElementById('startBtn');

    if (!name) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="btn-content"><i class="fas fa-spinner fa-spin"></i> Bağlanıyor...</span>';

    try {
        const res = await fetch(`${WIDGET_URL}/api/chat.php?action=start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                email: email,
                page: getParentUrl(),
                site_url: getParentOrigin()
            })
        });

        const data = await res.json();

        if (data.success) {
            sessionId = data.session_id;
            conversationId = data.conversation_id;

            // Save session
            localStorage.setItem('livesupport_session', JSON.stringify({
                session_id: sessionId,
                conversation_id: conversationId
            }));

            // Switch to chat screen
            document.getElementById('screenPreChat').style.display = 'none';
            document.getElementById('screenChat').style.display = 'flex';

            // Load initial messages (welcome, auto-reply)
            await loadMessages();
            startPolling();
        } else {
            throw new Error(data.error || 'Bağlantı hatası');
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<span class="btn-content"><i class="fas fa-comment-dots"></i> Sohbet Başlat</span>';
        alert(err.message);
    }
}

// ============ MESSAGES ============
async function loadMessages() {
    if (!sessionId) return;

    try {
        const res = await fetch(`${WIDGET_URL}/api/chat.php?action=messages&session_id=${sessionId}&after_id=${lastMsgId}`);
        const data = await res.json();

        if (data.success && data.messages) {
            renderWidgetMessages(data.messages);

            // Typing indicator
            const typing = document.getElementById('widgetTyping');
            typing.style.display = data.admin_typing ? 'flex' : 'none';
        }
    } catch (err) {
        console.error('Load messages error:', err);
    }
}

function renderWidgetMessages(messages) {
    const container = document.getElementById('widgetMessages');

    messages.forEach(msg => {
        const msgId = parseInt(msg.id);

        // Skip already rendered (only for real DB IDs, not temp)
        if (msgId > 0 && document.querySelector(`.w-message[data-id="${msgId}"]`)) return;

        // Only update lastMsgId with real DB IDs (positive, reasonable numbers)
        if (msgId > 0 && msgId < 999999999 && msgId > lastMsgId) {
            lastMsgId = msgId;
        }

        const div = document.createElement('div');
        div.className = `w-message ${msg.sender_type}`;
        div.dataset.id = msg.id;

        if (msg.sender_type === 'system') {
            div.innerHTML = `
                <div class="w-msg-content">
                    <div class="w-msg-bubble">${escapeHtml(msg.message)}</div>
                </div>
            `;
        } else {
            const showAvatar = msg.sender_type === 'admin';
            const initial = msg.sender_name ? msg.sender_name[0].toUpperCase() : '?';

            div.innerHTML = `
                ${showAvatar ? `<div class="w-msg-avatar">${initial}</div>` : ''}
                <div class="w-msg-content">
                    ${msg.sender_type === 'admin' ? `<span class="w-msg-name">${escapeHtml(msg.sender_name || 'Temsilci')}</span>` : ''}
                    <div class="w-msg-bubble">${formatMsg(msg.message)}</div>
                    <span class="w-msg-time">${formatTime(msg.created_at)}</span>
                </div>
            `;
        }

        container.appendChild(div);
    });

    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
}

// ============ SEND MESSAGE ============
let tempIdCounter = -1;

async function sendWidgetMessage() {
    const input = document.getElementById('widgetInput');
    const message = input.value.trim();

    if (!message || !sessionId) return;

    input.value = '';
    input.style.height = 'auto';
    document.getElementById('widgetSendBtn').disabled = true;

    // Stop typing indicator
    setVisitorTyping(false);

    // Immediately render with negative temp ID (won't affect lastMsgId)
    const tempId = tempIdCounter--;
    renderWidgetMessages([{
        id: tempId,
        sender_type: 'visitor',
        sender_name: '',
        message: message,
        message_type: 'text',
        created_at: new Date().toISOString()
    }]);

    try {
        const res = await fetch(`${WIDGET_URL}/api/chat.php?action=send`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionId,
                message: message
            })
        });

        const data = await res.json();
        if (data.success && data.message_id) {
            // Update lastMsgId with real DB ID
            lastMsgId = Math.max(lastMsgId, data.message_id);
            // Update the temp element's data-id to real ID so it won't be duplicated
            const tempEl = document.querySelector(`.w-message[data-id="${tempId}"]`);
            if (tempEl) tempEl.dataset.id = data.message_id;
        }
    } catch (err) {
        console.error('Send error:', err);
    }

    input.focus();
}

function widgetKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendWidgetMessage();
    }
}

function widgetInputChange() {
    const input = document.getElementById('widgetInput');
    const btn = document.getElementById('widgetSendBtn');

    // Auto-resize
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 100) + 'px';

    // Enable/disable send
    btn.disabled = !input.value.trim();

    // Typing indicator
    if (!isVisitorTyping && sessionId) {
        setVisitorTyping(true);
    }
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        setVisitorTyping(false);
    }, 2000);
}

async function setVisitorTyping(typing) {
    if (!sessionId) return;
    isVisitorTyping = typing;

    try {
        await fetch(`${WIDGET_URL}/api/chat.php?action=typing`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionId,
                typing: typing
            })
        });
    } catch (err) { }
}

// ============ POLLING ============
function startPolling() {
    if (isPolling) return;
    isPolling = true;
    poll();
}

function stopPolling() {
    isPolling = false;
    if (pollTimer) {
        clearTimeout(pollTimer);
        pollTimer = null;
    }
}

async function poll() {
    if (!isPolling || !sessionId) return;

    try {
        const res = await fetch(`${WIDGET_URL}/api/chat.php?action=messages&session_id=${sessionId}&after_id=${lastMsgId}`);
        const data = await res.json();

        if (data.success) {
            if (data.messages && data.messages.length > 0) {
                const newMsgs = data.messages.filter(m => m.id > lastMsgId);
                if (newMsgs.length > 0) {
                    renderWidgetMessages(newMsgs);

                    // Play notification sound for admin messages
                    const hasAdminMsg = newMsgs.some(m => m.sender_type === 'admin');
                    if (hasAdminMsg) {
                        playNotif();
                    }
                }
            }

            // Typing indicator
            const typing = document.getElementById('widgetTyping');
            typing.style.display = data.admin_typing ? 'flex' : 'none';
        }
    } catch (err) {
        console.error('Poll error:', err);
    }

    // Continue polling
    pollTimer = setTimeout(poll, 2000);
}

// ============ END CHAT ============
async function endChat() {
    if (!confirm('Sohbeti bitirmek istediğinize emin misiniz?')) return;

    stopPolling();

    try {
        await fetch(`${WIDGET_URL}/api/chat.php?action=end`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId })
        });
    } catch (err) { }

    // Show end screen
    document.getElementById('screenChat').style.display = 'none';
    document.getElementById('screenEnd').style.display = 'flex';
}

async function setRating(rating) {
    // Highlight stars
    document.querySelectorAll('.star').forEach(s => {
        s.classList.toggle('active', parseInt(s.dataset.rating) <= rating);
    });

    try {
        await fetch(`${WIDGET_URL}/api/chat.php?action=rate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionId,
                rating: rating
            })
        });
    } catch (err) { }
}

function resetWidget() {
    localStorage.removeItem('livesupport_session');
    sessionId = null;
    conversationId = null;
    lastMsgId = 0;

    // Reset UI
    document.getElementById('widgetMessages').innerHTML = '';
    document.getElementById('visitorNameInput').value = '';
    document.getElementById('visitorEmailInput').value = '';
    document.querySelectorAll('.star').forEach(s => s.classList.remove('active'));

    document.getElementById('startBtn').disabled = false;
    document.getElementById('startBtn').innerHTML = '<span class="btn-content"><i class="fas fa-comment-dots"></i> Sohbet Başlat</span>';

    // Show pre-chat screen
    document.getElementById('screenEnd').style.display = 'none';
    document.getElementById('screenChat').style.display = 'none';
    document.getElementById('screenPreChat').style.display = 'flex';
}

// ============ HELPERS ============
function playNotif() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, ctx.currentTime);
        oscillator.frequency.setValueAtTime(1200, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.3);
    } catch (e) { }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatMsg(text) {
    text = escapeHtml(text);
    text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color:inherit;text-decoration:underline">$1</a>');
    text = text.replace(/\n/g, '<br>');
    return text;
}

function getParentUrl() {
    try {
        if (window.parent && window.parent !== window) {
            return window.parent.location.href;
        }
    } catch (e) {
        // Cross-origin, can't access parent
    }
    try {
        if (document.referrer) return document.referrer;
    } catch (e) { }
    return window.location.href;
}

function getParentOrigin() {
    try {
        if (window.parent && window.parent !== window) {
            return window.parent.location.origin;
        }
    } catch (e) { }
    try {
        if (document.referrer) {
            const url = new URL(document.referrer);
            return url.origin;
        }
    } catch (e) { }
    return window.location.origin;
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
}
