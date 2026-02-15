/**
 * VMDestek Admin Panel - JavaScript
 */

// ============ STATE ============
let currentConversationId = null;
let currentTab = 'active';
let lastMessageId = 0;
let conversations = [];
let prevConversationIds = [];
let cannedResponses = [];
let isAdminOnline = true;
let pollTimeout = null;
let convPollTimeout = null;
let heartbeatInterval = null;
let typingTimeout = null;
let isAdminTyping = false;
let lastCheck = null;
let visitorPollInterval = null;
let activeVisitors = [];

// ============ INIT ============
document.addEventListener('DOMContentLoaded', () => {
    loadConversations(true);
    loadStats();
    loadCannedResponses();
    startConversationPolling();
    startHeartbeat();
    loadVisitors();
    startVisitorPolling();
});

// ============ CONVERSATIONS ============
async function loadConversations(isInitial = false) {
    try {
        let url = `${SITE_URL}/api/admin.php?action=conversations`;
        if (currentTab === 'closed') {
            url += '&status=closed';
        }

        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            const newConvList = data.conversations || [];

            // Detect new conversations for notification
            if (!isInitial && prevConversationIds.length > 0) {
                const newIds = newConvList
                    .filter(c => c.status === 'waiting' || c.status === 'active')
                    .map(c => c.id);
                const brandNew = newIds.filter(id => !prevConversationIds.includes(id));
                if (brandNew.length > 0) {
                    playNotification();
                    document.title = `(${brandNew.length} yeni) VMDestek - Admin`;
                    setTimeout(() => { document.title = 'VMDestek - Admin Panel'; }, 5000);
                }
            }

            // Save current IDs for next comparison
            prevConversationIds = newConvList
                .filter(c => c.status === 'waiting' || c.status === 'active')
                .map(c => c.id);

            conversations = newConvList;
            renderConversations();
            updateBadges();
        }
    } catch (err) {
        console.error('Load conversations error:', err);
    }
}

function renderConversations() {
    if (currentTab === 'visitors') {
        renderVisitors();
        return;
    }

    const list = document.getElementById('conversationList');
    const search = document.getElementById('searchInput').value.toLowerCase();

    let filtered = conversations;

    // Filter by tab
    if (currentTab === 'active') {
        // Show both active and waiting conversations in the main tab
        filtered = filtered.filter(c => c.status === 'active' || c.status === 'waiting');
    } else if (currentTab === 'waiting') {
        filtered = filtered.filter(c => c.status === 'waiting');
    } else if (currentTab === 'closed') {
        filtered = filtered.filter(c => c.status === 'closed');
    }

    // Filter by search
    if (search) {
        filtered = filtered.filter(c =>
            (c.visitor_name || '').toLowerCase().includes(search) ||
            (c.visitor_email || '').toLowerCase().includes(search) ||
            (c.last_message || '').toLowerCase().includes(search)
        );
    }

    if (filtered.length === 0) {
        list.innerHTML = '<div class="empty-state" id="emptyState"><i class="fas fa-inbox"></i><p>Henüz konuşma yok</p></div>';
        return;
    }

    list.innerHTML = filtered.map(conv => {
        const initials = getInitials(conv.visitor_name);
        const time = formatTime(conv.last_message_at || conv.started_at);
        const preview = conv.last_message ? truncate(conv.last_message, 40) : 'Yeni konuşma';
        const isActive = conv.id == currentConversationId;
        const unread = parseInt(conv.unread_count) || 0;
        const statusClass = conv.status === 'waiting' ? 'waiting' : (conv.status === 'closed' ? 'closed' : '');

        return `
            <div class="conv-item ${isActive ? 'active' : ''} ${unread > 0 ? 'has-unread' : ''}" 
                 onclick="selectConversation(${conv.id})" data-id="${conv.id}">
                <div class="conv-avatar">
                    ${initials}
                    <span class="status-indicator ${statusClass}"></span>
                </div>
                <div class="conv-info">
                    <div class="conv-name-row">
                        <span class="conv-name">${escapeHtml(conv.visitor_name)}</span>
                        <span class="conv-time">${time}</span>
                    </div>
                    <div class="conv-preview ${unread > 0 ? 'unread' : ''}">
                        ${conv.last_sender === 'admin' ? '<i class="fas fa-reply" style="font-size:10px;margin-right:4px;opacity:0.5"></i>' : ''}
                        ${escapeHtml(preview)}
                    </div>
                </div>
                ${unread > 0 ? `<div class="conv-meta"><span class="unread-badge">${unread}</span></div>` : ''}
            </div>
        `;
    }).join('');
}

function updateBadges() {
    const waiting = conversations.filter(c => c.status === 'waiting').length;
    const active = conversations.filter(c => c.status === 'active').length;

    const waitingBadge = document.getElementById('waitingBadge');
    const activeBadge = document.getElementById('activeBadge');
    const visitorsBadge = document.getElementById('visitorsBadge');

    if (waiting > 0) {
        waitingBadge.textContent = waiting;
        waitingBadge.classList.add('show');
    } else {
        waitingBadge.classList.remove('show');
    }

    if (active > 0) {
        activeBadge.textContent = active;
        activeBadge.classList.add('show');
    } else {
        activeBadge.classList.remove('show');
    }

    // Visitor badge
    if (visitorsBadge && activeVisitors.length > 0) {
        visitorsBadge.textContent = activeVisitors.length;
        visitorsBadge.classList.add('show');
    } else if (visitorsBadge) {
        visitorsBadge.classList.remove('show');
    }

    // Update topbar stats
    document.getElementById('statWaiting').textContent = waiting;
    document.getElementById('statActive').textContent = active;
    const statVisitors = document.getElementById('statVisitors');
    if (statVisitors) statVisitors.textContent = activeVisitors.length;
}

function switchTab(el) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    currentTab = el.dataset.tab;
    if (currentTab === 'visitors') {
        loadVisitors();
    } else {
        loadConversations();
    }
}

function filterConversations() {
    renderConversations();
}

// ============ CONVERSATION DETAIL ============
async function selectConversation(id) {
    currentConversationId = id;
    lastMessageId = 0;

    // Update UI
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatActive').style.display = 'flex';
    document.getElementById('messagesList').innerHTML = '';

    // Highlight in list
    document.querySelectorAll('.conv-item').forEach(el => {
        el.classList.toggle('active', el.dataset.id == id);
    });

    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=conversation&id=${id}`);
        const data = await res.json();

        if (data.success) {
            const conv = data.conversation;

            // Update header
            document.getElementById('visitorAvatar').innerHTML = `<span>${getInitials(conv.visitor_name)}</span>`;
            document.getElementById('visitorName').textContent = conv.visitor_name;
            document.getElementById('visitorEmail').innerHTML = `<i class="fas fa-envelope"></i> ${conv.visitor_email || '-'}`;
            document.getElementById('visitorPage').innerHTML = `<i class="fas fa-globe"></i> ${conv.visitor_page || conv.site_url || '-'}`;
            document.getElementById('typingInitial').textContent = getInitials(conv.visitor_name);

            // Update info panel
            document.getElementById('infoName').textContent = conv.visitor_name;
            document.getElementById('infoEmail').textContent = conv.visitor_email || '-';
            document.getElementById('infoIP').textContent = conv.visitor_ip || '-';
            document.getElementById('infoPage').textContent = conv.visitor_page || '-';
            document.getElementById('infoStarted').textContent = formatDateTime(conv.started_at);
            document.getElementById('infoBrowser').textContent = parseBrowser(conv.visitor_user_agent);

            // Show/hide input based on status
            const inputArea = document.getElementById('messageInputArea');
            if (conv.status === 'closed') {
                inputArea.style.display = 'none';
            } else {
                inputArea.style.display = 'block';
                document.getElementById('messageInput').focus();
            }

            // Render messages
            renderMessages(data.messages);

            // Start polling for new messages
            startMessagePolling();

            // Refresh conversation list to update unread counts
            loadConversations();
        }
    } catch (err) {
        console.error('Select conversation error:', err);
    }
}

function renderMessages(messages) {
    const list = document.getElementById('messagesList');

    messages.forEach(msg => {
        if (msg.id > lastMessageId) {
            lastMessageId = msg.id;
        }

        const div = document.createElement('div');
        div.className = `message ${msg.sender_type}`;
        div.dataset.id = msg.id;

        if (msg.sender_type === 'system') {
            div.innerHTML = `
                <div class="msg-content">
                    <div class="msg-bubble">${escapeHtml(msg.message)}</div>
                </div>
            `;
        } else {
            const initials = getInitials(msg.sender_name || (msg.sender_type === 'admin' ? 'A' : 'Z'));
            div.innerHTML = `
                <div class="msg-avatar">${initials}</div>
                <div class="msg-content">
                    <span class="msg-name">${escapeHtml(msg.sender_name || '')}</span>
                    <div class="msg-bubble">${formatMessage(msg.message)}</div>
                    <span class="msg-time">${formatTime(msg.created_at)}</span>
                </div>
            `;
        }

        list.appendChild(div);
    });

    // Scroll to bottom
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

// ============ MESSAGING ============
async function sendAdminMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message || !currentConversationId) return;

    input.value = '';
    input.style.height = 'auto';
    document.getElementById('sendBtn').disabled = true;

    // Check for canned response shortcut
    // -> handled in handleInput

    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=send`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                message: message
            })
        });

        const data = await res.json();
        if (data.success) {
            // Immediately show the message
            renderMessages([{
                id: data.message_id,
                sender_type: 'admin',
                sender_name: ADMIN_NAME,
                message: message,
                message_type: 'text',
                created_at: new Date().toISOString()
            }]);

            // Stop typing indicator
            setAdminTyping(false);

            // Refresh conversation list
            loadConversations();
        }
    } catch (err) {
        console.error('Send message error:', err);
    }
}

function handleKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendAdminMessage();
    }
}

function handleInput() {
    const input = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');

    // Auto-resize
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';

    // Enable/disable send button
    sendBtn.disabled = !input.value.trim();

    // Check canned response shortcut
    const text = input.value;
    if (text.startsWith('/') && text.length > 1) {
        const shortcut = text.toLowerCase();
        const match = cannedResponses.find(r => r.shortcut && r.shortcut.toLowerCase() === shortcut);
        if (match) {
            input.value = match.message;
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
            sendBtn.disabled = false;
        }
    }

    // Typing indicator
    if (!isAdminTyping && currentConversationId) {
        setAdminTyping(true);
    }
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        setAdminTyping(false);
    }, 2000);
}

async function setAdminTyping(typing) {
    if (!currentConversationId) return;
    isAdminTyping = typing;

    try {
        await fetch(`${SITE_URL}/api/admin.php?action=typing`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                typing: typing
            })
        });
    } catch (err) { }
}

// ============ ACTIONS ============
async function closeConversation() {
    if (!currentConversationId) return;
    if (!confirm('Bu konuşmayı kapatmak istediğinize emin misiniz?')) return;

    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=close`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: currentConversationId })
        });

        const data = await res.json();
        if (data.success) {
            currentConversationId = null;
            document.getElementById('chatActive').style.display = 'none';
            document.getElementById('chatEmpty').style.display = 'flex';
            loadConversations();
        }
    } catch (err) {
        console.error('Close conversation error:', err);
    }
}

// ============ SIDE PANEL (Info/Notes/Reminders) ============
function togglePanel(tab) {
    const panel = document.getElementById('sidePanel');
    const isVisible = panel.style.display !== 'none';

    if (isVisible) {
        // If clicking same tab, close. If different tab, switch.
        const activeTab = panel.querySelector('.sp-tab.active');
        if (activeTab && activeTab.dataset.tab === tab) {
            panel.style.display = 'none';
            return;
        }
    }

    panel.style.display = 'block';
    switchPanelTab(tab);
}

function switchPanelTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.sp-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.tab === tab);
    });
    // Update content
    document.querySelectorAll('.sp-content').forEach(c => c.classList.remove('active'));
    const targetId = 'tab' + tab.charAt(0).toUpperCase() + tab.slice(1);
    const target = document.getElementById(targetId);
    if (target) target.classList.add('active');

    // Load data
    if (tab === 'notes' && currentConversationId) loadNotes();
    if (tab === 'reminders' && currentConversationId) loadReminders();
}

// ============ NOTES ============
async function loadNotes() {
    if (!currentConversationId) return;
    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=notes&conversation_id=${currentConversationId}`);
        const data = await res.json();
        if (data.success) renderNotes(data.notes);
    } catch (err) { console.error('Load notes error:', err); }
}

function renderNotes(notes) {
    const container = document.getElementById('notesList');
    if (!notes || notes.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-sticky-note"></i><p>Henüz not eklenmemiş</p></div>';
        return;
    }
    container.innerHTML = notes.map(n => `
        <div class="note-item" data-id="${n.id}">
            <div class="note-text">${escapeHtml(n.note)}</div>
            <div class="note-meta">
                <span><i class="fas fa-user"></i> ${escapeHtml(n.admin_name)} · ${formatDateTime(n.created_at)}</span>
                <button class="note-delete" onclick="deleteNote(${n.id})" title="Sil"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');
}

async function saveNote() {
    const input = document.getElementById('noteInput');
    const note = input.value.trim();
    if (!note || !currentConversationId) return;

    input.disabled = true;
    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=note_save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: currentConversationId, note })
        });
        const data = await res.json();
        if (data.success) {
            input.value = '';
            loadNotes();
        }
    } catch (err) { console.error('Save note error:', err); }
    input.disabled = false;
}

async function deleteNote(id) {
    if (!confirm('Bu notu silmek istediğinize emin misiniz?')) return;
    try {
        await fetch(`${SITE_URL}/api/admin.php?action=note_delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        loadNotes();
    } catch (err) { console.error('Delete note error:', err); }
}

// ============ REMINDERS ============
async function loadReminders() {
    if (!currentConversationId) return;
    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=reminders&conversation_id=${currentConversationId}`);
        const data = await res.json();
        if (data.success) renderReminders(data.reminders);
    } catch (err) { console.error('Load reminders error:', err); }
}

function renderReminders(reminders) {
    const container = document.getElementById('remindersList');
    if (!reminders || reminders.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-bell"></i><p>Henüz hatırlatma eklenmemiş</p></div>';
        return;
    }

    const now = new Date();
    container.innerHTML = reminders.map(r => {
        const remindAt = new Date(r.remind_at);
        const isOverdue = remindAt <= now && !r.is_completed;
        const isUpcoming = remindAt > now && (remindAt - now) < 3600000; // 1 hour
        const timeClass = r.is_completed ? '' : (isOverdue ? 'overdue' : (isUpcoming ? 'upcoming' : ''));

        return `
        <div class="reminder-item ${r.is_completed ? 'completed' : ''}" data-id="${r.id}">
            <div class="reminder-info">
                <div class="reminder-title">${escapeHtml(r.title)}</div>
                <div class="reminder-time ${timeClass}">
                    <i class="fas fa-${isOverdue ? 'exclamation-triangle' : 'clock'}"></i>
                    ${formatDateTime(r.remind_at)}
                    ${isOverdue ? ' (gecikmiş!)' : ''}
                </div>
            </div>
            <div class="reminder-actions">
                ${!r.is_completed ? `<button class="complete" onclick="completeReminder(${r.id})" title="Tamamlandı"><i class="fas fa-check"></i></button>` : ''}
                <button class="delete" onclick="deleteReminder(${r.id})" title="Sil"><i class="fas fa-trash"></i></button>
            </div>
        </div>`;
    }).join('');
}

async function saveReminder() {
    const titleInput = document.getElementById('reminderTitle');
    const dateInput = document.getElementById('reminderDate');
    const title = titleInput.value.trim();
    const remindAt = dateInput.value;

    if (!title || !remindAt || !currentConversationId) {
        alert('Başlık ve tarih gerekli');
        return;
    }

    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=reminder_save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                title,
                remind_at: remindAt.replace('T', ' ') + ':00'
            })
        });
        const data = await res.json();
        if (data.success) {
            titleInput.value = '';
            dateInput.value = '';
            loadReminders();
        }
    } catch (err) { console.error('Save reminder error:', err); }
}

async function completeReminder(id) {
    try {
        await fetch(`${SITE_URL}/api/admin.php?action=reminder_complete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        loadReminders();
    } catch (err) { console.error('Complete reminder error:', err); }
}

async function deleteReminder(id) {
    if (!confirm('Bu hatırlatmayı silmek istediğinize emin misiniz?')) return;
    try {
        await fetch(`${SITE_URL}/api/admin.php?action=reminder_delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        loadReminders();
    } catch (err) { console.error('Delete reminder error:', err); }
}

function checkReminders(reminders) {
    if (!reminders || reminders.length === 0) {
        document.getElementById('reminderBadge').style.display = 'none';
        return;
    }

    // Show badge
    const badge = document.getElementById('reminderBadge');
    badge.textContent = reminders.length;
    badge.style.display = 'flex';

    // Show popup
    const popup = document.getElementById('reminderPopup');
    const list = document.getElementById('reminderPopupList');
    list.innerHTML = reminders.map(r => `
        <div class="reminder-item" data-id="${r.id}">
            <div class="reminder-info">
                <div class="reminder-title">${escapeHtml(r.title)}</div>
                <div class="reminder-time overdue">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${r.visitor_name ? escapeHtml(r.visitor_name) + ' · ' : ''}${formatDateTime(r.remind_at)}
                </div>
            </div>
            <div class="reminder-actions">
                <button class="complete" onclick="completeReminder(${r.id});this.closest('.reminder-item').remove();" title="Tamamlandı"><i class="fas fa-check"></i></button>
            </div>
        </div>
    `).join('');
    popup.style.display = 'block';
    playNotification();
}

// ============ CANNED RESPONSES ============
async function loadCannedResponses() {
    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=canned`);
        const data = await res.json();
        if (data.success) {
            cannedResponses = data.responses || [];
            renderCannedResponses();
        }
    } catch (err) {
        console.error('Load canned error:', err);
    }
}

function renderCannedResponses() {
    const list = document.getElementById('cannedList');
    list.innerHTML = cannedResponses.map(r => `
        <div class="canned-item" onclick="useCannedResponse('${escapeHtml(r.message).replace(/'/g, "\\'")}')">
            ${escapeHtml(r.title)}
            ${r.shortcut ? `<span class="canned-shortcut">${escapeHtml(r.shortcut)}</span>` : ''}
        </div>
    `).join('');
}

function toggleCannedResponses() {
    const bar = document.getElementById('cannedBar');
    const btn = document.querySelector('.input-btn');
    const isVisible = bar.style.display !== 'none';
    bar.style.display = isVisible ? 'none' : 'block';
    btn.classList.toggle('active', !isVisible);
}

function useCannedResponse(message) {
    const input = document.getElementById('messageInput');
    input.value = message;
    input.focus();
    handleInput();
    document.getElementById('cannedBar').style.display = 'none';
    document.querySelector('.input-btn').classList.remove('active');
}

// ============ POLLING ============
function startMessagePolling() {
    stopMessagePolling();
    pollMessages();
}

function stopMessagePolling() {
    if (pollTimeout) {
        clearTimeout(pollTimeout);
        pollTimeout = null;
    }
}

async function pollMessages() {
    if (!currentConversationId) return;

    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=conversation&id=${currentConversationId}&after_id=${lastMessageId}`);
        const data = await res.json();

        if (data.success && data.messages && data.messages.length > 0) {
            // Filter out already displayed messages
            const newMessages = data.messages.filter(m => m.id > lastMessageId);

            if (newMessages.length > 0) {
                // Check if any are from visitors (need notification)
                const hasVisitorMsg = newMessages.some(m => m.sender_type === 'visitor');

                renderMessages(newMessages);

                if (hasVisitorMsg) {
                    playNotification();
                }
            }
        }

        // Update typing indicator
        if (data.conversation) {
            const typing = document.getElementById('typingIndicator');
            typing.style.display = data.conversation.is_visitor_typing == 1 ? 'flex' : 'none';
        }

    } catch (err) {
        console.error('Poll error:', err);
    }

    // Continue polling
    pollTimeout = setTimeout(pollMessages, 2000);
}

function startConversationPolling() {
    setInterval(async () => {
        await loadConversations();
    }, 3000);
}

// ============ VISITORS ============
async function loadVisitors() {
    try {
        const res = await fetch(`${SITE_URL}/api/visitor.php?action=list`);
        const data = await res.json();
        if (data.success) {
            activeVisitors = data.visitors || [];
            if (currentTab === 'visitors') {
                renderVisitors();
            }
            updateBadges();
        }
    } catch (err) {
        console.error('Load visitors error:', err);
    }
}

function renderVisitors() {
    const list = document.getElementById('conversationList');
    const search = document.getElementById('searchInput').value.toLowerCase();

    let filtered = activeVisitors;
    if (search) {
        filtered = filtered.filter(v =>
            (v.page_url || '').toLowerCase().includes(search) ||
            (v.page_title || '').toLowerCase().includes(search) ||
            (v.ip_address || '').toLowerCase().includes(search)
        );
    }

    if (filtered.length === 0) {
        list.innerHTML = '<div class="empty-state" id="emptyState"><i class="fas fa-eye"></i><p>Aktif ziyaretçi yok</p></div>';
        return;
    }

    list.innerHTML = filtered.map(v => {
        const browser = parseBrowser(v.user_agent || '');
        const duration = formatDuration(v.duration_seconds || 0);
        const pageUrl = v.page_url || '';
        const pagePath = (() => {
            try { return new URL(pageUrl).pathname; } catch (e) { return pageUrl; }
        })();
        const pageTitle = v.page_title || pagePath || 'Bilinmiyor';
        const uid = (v.visitor_uid || '').substring(0, 6).toUpperCase();

        return `
            <div class="conv-item visitor-item">
                <div class="conv-avatar visitor-avatar-live">
                    <i class="fas fa-user"></i>
                    <span class="live-pulse"></span>
                </div>
                <div class="conv-info">
                    <div class="conv-name-row">
                        <span class="conv-name">Ziyaretçi #${uid}</span>
                        <span class="conv-time visitor-duration"><i class="fas fa-clock"></i> ${duration}</span>
                    </div>
                    <div class="conv-preview visitor-page">
                        <i class="fas fa-globe" style="font-size:10px;margin-right:4px;opacity:0.5"></i>
                        ${escapeHtml(truncate(pageTitle, 38))}
                    </div>
                    <div class="visitor-meta-row">
                        <span class="visitor-meta-tag"><i class="fas fa-desktop"></i> ${escapeHtml(browser)}</span>
                        <span class="visitor-meta-tag"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(v.ip_address || '?')}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function formatDuration(seconds) {
    if (seconds < 60) return seconds + 'sn';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'dk';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return h + 'sa ' + m + 'dk';
}

function startVisitorPolling() {
    visitorPollInterval = setInterval(() => {
        loadVisitors();
    }, 10000);
}

function startHeartbeat() {
    // Initial heartbeat
    sendHeartbeatNow();
    heartbeatInterval = setInterval(sendHeartbeatNow, 15000);
}

async function sendHeartbeatNow() {
    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=heartbeat`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ is_online: isAdminOnline ? 1 : 0 })
        });
        const data = await res.json();
        if (data.success) {
            if (data.waiting_count > 0 && !currentConversationId) {
                document.title = `(${data.waiting_count}) VMDestek - Admin`;
            } else {
                document.title = 'VMDestek - Admin Panel';
            }
            // Check reminders
            if (data.reminders) {
                checkReminders(data.reminders);
            }
        }
    } catch (err) { }
}

// ============ ONLINE/OFFLINE TOGGLE ============
async function toggleOnlineStatus() {
    const newStatus = !isAdminOnline;
    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=toggle_status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ is_online: newStatus ? 1 : 0 })
        });
        const data = await res.json();
        if (data.success) {
            isAdminOnline = newStatus;
            updateAdminStatusUI();
        }
    } catch (err) {
        console.error('Status toggle error:', err);
    }
}

function updateAdminStatusUI() {
    const toggle = document.getElementById('adminStatusToggle');
    const dot = document.getElementById('adminStatusDot');
    if (isAdminOnline) {
        toggle.classList.remove('offline');
        dot.classList.add('online');
        dot.classList.remove('offline');
    } else {
        toggle.classList.add('offline');
        dot.classList.remove('online');
        dot.classList.add('offline');
    }
}

// ============ STATS ============
async function loadStats() {
    try {
        const res = await fetch(`${SITE_URL}/api/admin.php?action=stats`);
        const data = await res.json();
        if (data.success) {
            document.getElementById('statToday').textContent = data.stats.today_conversations;
            document.getElementById('emptyStatTotal').textContent = data.stats.total_conversations;
            document.getElementById('emptyStatToday').textContent = data.stats.today_conversations;
            document.getElementById('emptyStatRating').textContent = data.stats.avg_rating || '-';
        }
    } catch (err) {
        console.error('Load stats error:', err);
    }
}

// ============ AUTH ============
async function handleLogout() {
    try {
        await fetch(`${SITE_URL}/api/auth.php?action=logout`);
    } catch (err) { }
    window.location.href = 'login.php';
}

// ============ HELPERS ============
function playNotification() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc1 = ctx.createOscillator();
        const osc2 = ctx.createOscillator();
        const gain = ctx.createGain();

        osc1.connect(gain);
        osc2.connect(gain);
        gain.connect(ctx.destination);

        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587, ctx.currentTime);
        osc1.frequency.setValueAtTime(784, ctx.currentTime + 0.15);
        osc1.frequency.setValueAtTime(880, ctx.currentTime + 0.3);

        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(440, ctx.currentTime);
        osc2.frequency.setValueAtTime(587, ctx.currentTime + 0.15);
        osc2.frequency.setValueAtTime(659, ctx.currentTime + 0.3);

        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);

        osc1.start(ctx.currentTime);
        osc1.stop(ctx.currentTime + 0.5);
        osc2.start(ctx.currentTime);
        osc2.stop(ctx.currentTime + 0.5);
    } catch (err) {
        console.log('Audio not available');
    }
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return parts[0][0].toUpperCase();
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) return 'Şimdi';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'dk';
    if (diff < 86400000) return date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
    return date.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit' });
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('tr-TR');
}

function formatMessage(text) {
    // Convert URLs to links
    text = escapeHtml(text);
    text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color:inherit;text-decoration:underline">$1</a>');
    // Convert newlines
    text = text.replace(/\n/g, '<br>');
    return text;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function truncate(str, len) {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

function parseBrowser(ua) {
    if (!ua) return '-';
    if (ua.includes('Chrome')) return 'Chrome';
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Safari')) return 'Safari';
    if (ua.includes('Edge')) return 'Edge';
    return 'Diğer';
}
