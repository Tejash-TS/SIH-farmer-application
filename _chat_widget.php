<?php
/**
 * Chat Widget - Include this in any page to enable chat functionality
 * Add this line to your page: <?php include_once('_chat_widget.php'); ?>
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    return;
}

$user_id = intval($_SESSION['user']['user_id']);
$user_name = $_SESSION['user']['name'] ?? 'User';
$user_role = $_SESSION['user']['role'] ?? 'unknown';

// Get chat conversations
global $conn;
require_once('ChatAnnouncementDAO.php');
$chatDAO = new ChatAnnouncementDAO($conn);
$conversations = $chatDAO->getChatConversations($user_id);
$chat_users = $chatDAO->getChatUsers($user_id);
$unread_count = $chatDAO->getUnreadMessageCount($user_id);
?>

<!-- Chat Widget Styles -->
<style>
    .chat-widget-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 380px;
        height: 600px;
        display: flex;
        flex-direction: column;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .chat-widget-container.hidden {
        display: none;
    }

    .chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .chat-header h3 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
    }

    .chat-header-actions {
        display: flex;
        gap: 10px;
    }

    .chat-header-btn {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 16px;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .chat-header-btn:hover {
        opacity: 1;
    }

    .unread-badge {
        background: #ff4757;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 12px;
        font-weight: bold;
    }

    .chat-tab-buttons {
        display: flex;
        gap: 5px;
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
    }

    .chat-tab-btn {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }

    .chat-tab-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .chat-content {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }

    .chat-section-title {
        font-size: 12px;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 4px 0 10px;
    }

    .chat-search {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .chat-search:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    }

    .chat-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .chat-item {
        padding: 12px;
        background: #f8f9fa;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s;
        border-left: 4px solid transparent;
    }

    .chat-item:hover {
        background: #e9ecef;
    }

    .chat-item.active {
        background: #e7f3ff;
        border-left-color: #667eea;
    }

    .chat-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-item-name {
        font-weight: 600;
        font-size: 13px;
        color: #212529;
    }

    .chat-item-time {
        font-size: 11px;
        color: #6c757d;
    }

    .chat-item-preview {
        font-size: 12px;
        color: #495057;
        margin-top: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-user-item {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .chat-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        background: #e9ecef;
        flex: 0 0 36px;
    }

    .chat-user-meta {
        flex: 1;
        min-width: 0;
    }

    .chat-user-role {
        font-size: 11px;
        color: #6c757d;
        margin-top: 2px;
        text-transform: capitalize;
    }

    .chat-item-badge {
        display: inline-block;
        background: #667eea;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 10px;
        font-weight: bold;
    }

    .messages-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .message {
        display: flex;
        width: 100%;
        margin-bottom: 10px;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.sent {
        justify-content: flex-end;
    }

    .message > div {
        display: flex;
        flex-direction: column;
        max-width: 75%;
    }

    .message.sent > div {
        align-items: flex-end;
    }

    .message.received > div {
        align-items: flex-start;
    }

    .message-bubble {
        display: inline-block;
        width: fit-content;
        max-width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 13px;
        line-height: 1.4;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .message.sent .message-bubble {
        border-bottom-right-radius: 2px;
    }

    .message.received .message-bubble {
        border-bottom-left-radius: 2px;
    }

    .message.received .message-bubble {
        background: #e9ecef;
        color: #212529;
    }

    .message.sent .message-bubble {
        background: #667eea;
        color: white;
    }

    .message-time {
        font-size: 11px;
        color: #6c757d;
        margin-top: 4px;
        padding: 0 5px;
    }

    .chat-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6c757d;
        font-size: 13px;
        text-align: center;
        padding: 20px;
    }

    .chat-empty.compact {
        height: auto;
        min-height: 120px;
        border: 1px dashed #dee2e6;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .chat-back-btn {
        border: none;
        background: transparent;
        color: #667eea;
        font-size: 12px;
        padding: 0;
        cursor: pointer;
        margin-bottom: 8px;
    }

    .chat-detail-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        background: #f8f9fa;
    }

    .chat-detail-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        background: #e9ecef;
        flex: 0 0 42px;
    }

    .chat-detail-meta {
        min-width: 0;
        flex: 1;
    }

    .chat-detail-name {
        font-size: 14px;
        font-weight: 700;
        color: #212529;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .chat-detail-subtitle {
        font-size: 12px;
        color: #6c757d;
        margin-top: 2px;
        text-transform: capitalize;
    }

    .chat-detail-status {
        font-size: 11px;
        color: #28a745;
        margin-top: 2px;
    }

    .typing-indicator {
        display: flex;
        gap: 4px;
        align-items: center;
        padding: 10px;
    }

    .typing-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #6c757d;
        animation: bounce 1.4s infinite;
    }

    .typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes bounce {
        0%, 80%, 100% {
            opacity: 0.5;
            transform: translateY(0);
        }
        40% {
            opacity: 1;
            transform: translateY(-10px);
        }
    }

    .chat-input-area {
        display: none;
        padding: 10px;
        border-top: 1px solid #e9ecef;
        flex-direction: column;
        gap: 8px;
    }

    .chat-input-area.active {
        display: flex;
    }

    .input-group {
        display: flex;
        gap: 8px;
    }

    .chat-input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        resize: none;
        max-height: 60px;
    }

    .chat-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    }

    .btn-send {
        padding: 8px 12px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.2s;
    }

    .btn-send:hover {
        background: #5568d3;
    }

    .btn-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .announcement-item {
        padding: 12px;
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .announcement-item.read {
        background: #e9ecef;
        border-left-color: #6c757d;
    }

    .announcement-title {
        font-weight: 600;
        font-size: 13px;
        color: #212529;
        margin-bottom: 4px;
    }

    .announcement-desc {
        font-size: 12px;
        color: #495057;
        margin-bottom: 4px;
    }

    .announcement-meta {
        font-size: 11px;
        color: #6c757d;
    }

    .online-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #28a745;
        margin-left: 4px;
    }

    .online-indicator.offline {
        background: #6c757d;
    }
</style>

<!-- Chat Widget HTML -->
<div class="chat-widget-container" id="chatWidget">
    <!-- Header -->
    <div class="chat-header" onclick="toggleChatWidget()">
        <div>
            <h3>
                💬 Messages
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="chat-header-actions">
            <button class="chat-header-btn" onclick="toggleChatWidget(event); return false;" title="Toggle">▼</button>
            <button class="chat-header-btn" onclick="minimizeChatWidget(event); return false;" title="Minimize">—</button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="chat-tab-buttons">
        <button class="chat-tab-btn active" onclick="switchTab('messages')">Messages</button>
        <button class="chat-tab-btn" onclick="switchTab('users')">Users</button>
        <button class="chat-tab-btn" onclick="switchTab('announcements')">Announcements</button>
    </div>

    <!-- Content Area -->
    <div class="chat-content">
        <!-- Messages Tab -->
        <div id="messagesTab" class="chat-tab-content active">
            <div class="chat-list" id="conversationList">
                <?php if (empty($conversations)): ?>
                    <div class="chat-empty">No conversations yet</div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <div class="chat-item" onclick="selectConversation(<?php echo $conv['other_user_id']; ?>, '<?php echo htmlspecialchars($conv['user_name']); ?>', '<?php echo htmlspecialchars(!empty($conv['image']) ? $conv['image'] : 'assets/dist/img/logos/avatar5.png'); ?>', '<?php echo htmlspecialchars($conv['role'] ?? 'user'); ?>')">
                            <div class="chat-item-header">
                                <span class="chat-item-name">
                                    <?php echo htmlspecialchars($conv['user_name']); ?>
                                    <span class="online-indicator" id="online_<?php echo $conv['other_user_id']; ?>"></span>
                                </span>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="chat-item-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-item-time"><?php echo date('H:i', strtotime($conv['last_message_time'])); ?></div>
                            <div class="chat-item-preview"><?php echo htmlspecialchars(substr($conv['last_message'], 0, 40)); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="messageDetail" class="chat-tab-content" style="display: none;">
            <button class="chat-back-btn" onclick="backToConversationList(event)">← Back to conversations</button>
            <div class="chat-detail-header" id="chatDetailHeader" style="display: none;">
                <img class="chat-detail-avatar" id="chatDetailAvatar" src="assets/dist/img/logos/avatar5.png" alt="User">
                <div class="chat-detail-meta">
                    <div class="chat-detail-name" id="chatDetailName">User</div>
                    <div class="chat-detail-subtitle" id="chatDetailRole">user</div>
                    <div class="chat-detail-status" id="chatDetailStatus">Open chat history and continue</div>
                </div>
            </div>
            <div class="messages-container" id="messagesContainer"></div>
        </div>

        <!-- Users Tab -->
        <div id="usersTab" class="chat-tab-content" style="display: none;">
            <input type="text" class="chat-search" id="chatUserSearch" placeholder="Search users..." oninput="filterChatUsers()">
            <div class="chat-section-title">Start a new chat</div>
            <div class="chat-list" id="userDirectoryList">
                <?php if (empty($chat_users)): ?>
                    <div class="chat-empty compact">No other active users found</div>
                <?php else: ?>
                    <?php foreach ($chat_users as $chat_user): ?>
                        <?php
                            $avatar = !empty($chat_user['image']) ? $chat_user['image'] : 'assets/dist/img/logos/avatar5.png';
                            $last_message = trim((string)($chat_user['last_message'] ?? ''));
                            $preview = $last_message !== '' ? $last_message : 'Tap to start chatting';
                        ?>
                        <div class="chat-item chat-user-item" data-user-name="<?php echo htmlspecialchars(strtolower($chat_user['user_name'] ?? '')); ?>" onclick="selectConversation(<?php echo (int)$chat_user['user_id']; ?>, '<?php echo htmlspecialchars($chat_user['user_name'] ?? 'User'); ?>', '<?php echo htmlspecialchars($avatar); ?>', '<?php echo htmlspecialchars($chat_user['role'] ?? 'user'); ?>')">
                            <img class="chat-user-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($chat_user['user_name'] ?? 'User'); ?>">
                            <div class="chat-user-meta">
                                <div class="chat-item-header">
                                    <span class="chat-item-name">
                                        <?php echo htmlspecialchars($chat_user['user_name'] ?? 'User'); ?>
                                        <span class="online-indicator offline" id="online_<?php echo (int)$chat_user['user_id']; ?>"></span>
                                    </span>
                                    <?php if (!empty($chat_user['unread_count'])): ?>
                                        <span class="chat-item-badge"><?php echo (int)$chat_user['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="chat-user-role"><?php echo htmlspecialchars($chat_user['role'] ?? 'user'); ?></div>
                                <div class="chat-item-preview"><?php echo htmlspecialchars(substr($preview, 0, 40)); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Announcements Tab -->
        <div id="announcementsTab" class="chat-tab-content" style="display: none;">
            <div id="announcementsList" class="chat-list"></div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="chat-input-area" id="chatInputArea">
        <div class="input-group">
            <textarea class="chat-input" id="messageInput" placeholder="Type a message..." rows="2" onkeypress="handleMessageKeypress(event)"></textarea>
            <button class="btn-send" onclick="sendMessage()">Send</button>
        </div>
    </div>
</div>

<!-- Chat Widget Button (Minimized) -->
<button class="btn btn-primary" id="chatToggleBtn" style="position: fixed; bottom: 20px; right: 20px; z-index: 999; display: none; border-radius: 50%; width: 50px; height: 50px; font-size: 20px;" onclick="toggleChatWidget()">💬</button>

<!-- Chat Widget JavaScript -->
<script>
    // Configuration
    const WS_URL = `ws://${window.location.hostname}:8000/ws/<?php echo $user_id; ?>`;
    const API_BASE = `http://${window.location.hostname}:8000/api`;
    const USER_ID = <?php echo $user_id; ?>;
    const USER_NAME = '<?php echo htmlspecialchars($user_name); ?>';
    const USER_ROLE = '<?php echo htmlspecialchars($user_role); ?>';

    // State
    let ws = null;
    let currentConversationId = null;
    let currentConversationName = '';
    let currentConversationAvatar = 'assets/dist/img/logos/avatar5.png';
    let currentConversationRole = 'user';
    let onlineUsers = new Set();
    const CHAT_WIDGET_STORAGE_KEY = `chat_widget_hidden_${USER_ID}`;

    function isChatWidgetHidden() {
        return localStorage.getItem(CHAT_WIDGET_STORAGE_KEY) === '1';
    }

    function setChatWidgetHidden(hidden) {
        if (hidden) {
            localStorage.setItem(CHAT_WIDGET_STORAGE_KEY, '1');
        } else {
            localStorage.removeItem(CHAT_WIDGET_STORAGE_KEY);
        }
    }

    function applyChatWidgetState() {
        const widget = document.getElementById('chatWidget');
        const btn = document.getElementById('chatToggleBtn');

        if (!widget || !btn) {
            return;
        }

        if (isChatWidgetHidden()) {
            widget.classList.add('hidden');
            btn.style.display = 'block';
        } else {
            widget.classList.remove('hidden');
            btn.style.display = 'none';
        }
    }

    // Initialize WebSocket connection
    function initWebSocket() {
        try {
            ws = new WebSocket(WS_URL);

            ws.onopen = () => {
                console.log('WebSocket connected');
                // Send online status
                ws.send(JSON.stringify({
                    type: 'online_status',
                    sender_id: USER_ID
                }));
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                handleWebSocketMessage(data);
            };

            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

            ws.onclose = () => {
                console.log('WebSocket disconnected');
                // Attempt to reconnect after 3 seconds
                setTimeout(initWebSocket, 3000);
            };
        } catch (error) {
            console.error('WebSocket connection error:', error);
        }
    }

    // Handle incoming WebSocket messages
    function handleWebSocketMessage(data) {
        if (data.type === 'chat') {
            if (currentConversationId === data.sender_id || currentConversationId === data.receiver_id) {
                addMessageToChat(data);
            }
            if (!data.echo) {
                updateConversationList();
            }
        } else if (data.type === 'typing') {
            if (currentConversationId === data.sender_id) {
                showTypingIndicator(data.is_typing);
            }
        } else if (data.type === 'online_status') {
            updateOnlineStatus(data);
        } else if (data.type === 'announcement') {
            loadAnnouncements();
        }
    }

    // Switch between tabs
    function switchTab(tab) {
        document.querySelectorAll('.chat-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.chat-tab-content').forEach(content => content.style.display = 'none');

        if (tab === 'messages') {
            document.querySelector('.chat-tab-btn:nth-child(1)').classList.add('active');
            document.getElementById('messagesTab').style.display = 'block';
            document.getElementById('conversationList').style.display = 'flex';
            document.getElementById('messageDetail').style.display = 'none';
            document.getElementById('chatInputArea').classList.remove('active');
        } else if (tab === 'users') {
            document.querySelector('.chat-tab-btn:nth-child(2)').classList.add('active');
            document.getElementById('usersTab').style.display = 'block';
            document.getElementById('messageDetail').style.display = 'none';
            document.getElementById('chatInputArea').classList.remove('active');
        } else if (tab === 'announcements') {
            document.querySelector('.chat-tab-btn:nth-child(3)').classList.add('active');
            document.getElementById('announcementsTab').style.display = 'block';
            document.getElementById('messageDetail').style.display = 'none';
            loadAnnouncements();
        }
    }

    // Select conversation
    function selectConversation(userId, userName, userAvatar = 'assets/dist/img/logos/avatar5.png', userRole = 'user') {
        currentConversationId = userId;
        currentConversationName = userName;
        currentConversationAvatar = userAvatar || 'assets/dist/img/logos/avatar5.png';
        currentConversationRole = userRole || 'user';
        document.getElementById('conversationList').style.display = 'none';
        document.getElementById('messageDetail').style.display = 'block';
        document.getElementById('chatInputArea').classList.add('active');
        const search = document.getElementById('chatUserSearch');
        if (search) search.value = '';

        document.getElementById('chatDetailHeader').style.display = 'flex';
        document.getElementById('chatDetailAvatar').src = currentConversationAvatar;
        document.getElementById('chatDetailAvatar').alt = currentConversationName;
        document.getElementById('chatDetailName').textContent = currentConversationName;
        document.getElementById('chatDetailRole').textContent = currentConversationRole;
        document.getElementById('chatDetailStatus').textContent = 'Open chat history and continue';

        // Load chat history
        loadChatHistory(userId);

        // Keep the top widget label stable like a messaging app
        document.querySelector('.chat-header h3').innerHTML = '💬 Messages<?php if ($unread_count > 0): ?> <span class="unread-badge"><?php echo $unread_count; ?></span><?php endif; ?>';
    }

    function backToConversationList(event) {
        if (event) event.preventDefault();
        currentConversationId = null;
        currentConversationName = '';
        currentConversationAvatar = 'assets/dist/img/logos/avatar5.png';
        currentConversationRole = 'user';
        document.getElementById('messageDetail').style.display = 'none';
        document.getElementById('chatDetailHeader').style.display = 'none';
        document.getElementById('conversationList').style.display = 'flex';
        document.getElementById('chatInputArea').classList.remove('active');
        document.querySelector('.chat-header h3').innerHTML = '💬 Messages<?php if ($unread_count > 0): ?> <span class="unread-badge"><?php echo $unread_count; ?></span><?php endif; ?>';
    }

    function filterChatUsers() {
        const query = (document.getElementById('chatUserSearch').value || '').trim().toLowerCase();
        document.querySelectorAll('#userDirectoryList .chat-item').forEach(item => {
            const name = item.getAttribute('data-user-name') || '';
            item.style.display = name.includes(query) ? 'flex' : 'none';
        });
    }

    // Load chat history
    function loadChatHistory(userId) {
        const container = document.getElementById('messagesContainer');
        container.innerHTML = '<div class="chat-empty compact">Loading chat history...</div>';

        fetch(`${API_BASE}/chat-history/${USER_ID}/${userId}`)
            .then(response => response.json())
            .then(data => {
                container.innerHTML = '';

                if (!data.messages || data.messages.length === 0) {
                    container.innerHTML = '<div class="chat-empty compact">No messages yet. Send the first message.</div>';
                    return;
                }

                data.messages.forEach(msg => {
                    addMessageToChat(msg);
                });

                // Scroll to bottom
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100);

                // Mark as read
                if (ws && ws.readyState === WebSocket.OPEN) {
                    data.messages.forEach(msg => {
                        if (msg.receiver_id === USER_ID && !msg.is_read) {
                            // Message will be marked as read by server
                        }
                    });
                }
            })
            .catch(error => console.error('Error loading chat history:', error));
    }

    // Add message to chat display
    function addMessageToChat(msg) {
        const container = document.getElementById('messagesContainer');
        const isSent = msg.sender_id === USER_ID;
        
        const messageEl = document.createElement('div');
        messageEl.className = `message ${isSent ? 'sent' : 'received'}`;
        messageEl.innerHTML = `
            <div>
                <div class="message-bubble">${escapeHtml(msg.message_text)}</div>
                <div class="message-time">${formatTime(msg.created_on)}</div>
            </div>
        `;

        container.appendChild(messageEl);
        container.scrollTop = container.scrollHeight;
    }

    // Send message
    function sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();

        if (!message || !currentConversationId) return;

        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'chat',
                sender_id: USER_ID,
                receiver_id: currentConversationId,
                message_text: message
            }));

            input.value = '';
            input.focus();
        } else {
            alert('Connection lost. Please refresh the page.');
        }
    }

    // Handle message input keypress
    function handleMessageKeypress(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    }

    // Show typing indicator
    function showTypingIndicator(isTyping) {
        const container = document.getElementById('messagesContainer');
        const existingIndicator = container.querySelector('.typing-indicator');

        if (isTyping && !existingIndicator) {
            const indicator = document.createElement('div');
            indicator.className = 'typing-indicator';
            indicator.innerHTML = `
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            `;
            container.appendChild(indicator);
            container.scrollTop = container.scrollHeight;
        } else if (!isTyping && existingIndicator) {
            existingIndicator.remove();
        }
    }

    // Load announcements
    function loadAnnouncements() {
        fetch(`${API_BASE}/announcements/${USER_ID}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('announcementsList');
                container.innerHTML = '';

                if (data.announcements.length === 0) {
                    container.innerHTML = '<div class="chat-empty">No announcements</div>';
                    return;
                }

                data.announcements.forEach(ann => {
                    const isRead = ann.read_on !== null;
                    const el = document.createElement('div');
                    el.className = `announcement-item ${isRead ? 'read' : ''}`;
                    el.innerHTML = `
                        <div class="announcement-title">${escapeHtml(ann.title || '')}</div>
                        <div class="announcement-desc">${escapeHtml(ann.description || '')}</div>
                        <div class="announcement-meta">
                            By ${escapeHtml(ann.sender_name || 'Admin')} - ${formatTime(ann.created_on)}
                        </div>
                    `;

                    // Mark as read when viewed
                    if (!isRead) {
                        fetch(`${API_BASE}/announcements/${ann.announcement_id}/read/${USER_ID}`, {
                            method: 'POST'
                        }).catch(err => console.error('Error marking announcement as read:', err));
                    }

                    container.appendChild(el);
                });
            })
            .catch(error => console.error('Error loading announcements:', error));
    }

    // Update online status
    function updateOnlineStatus(data) {
        data.online_users.forEach(userId => {
            onlineUsers.add(userId);
            const indicator = document.getElementById(`online_${userId}`);
            if (indicator) {
                indicator.classList.remove('offline');
            }
        });
    }

    // Update conversation list
    function updateConversationList() {
        // Reload conversations
        location.reload();
    }

    // Toggle chat widget visibility
    function toggleChatWidget(event) {
        if (event) event.stopPropagation();
        const widget = document.getElementById('chatWidget');
        const btn = document.getElementById('chatToggleBtn');

        if (widget.classList.contains('hidden')) {
            widget.classList.remove('hidden');
            btn.style.display = 'none';
            setChatWidgetHidden(false);
        } else {
            widget.classList.add('hidden');
            btn.style.display = 'block';
            setChatWidgetHidden(true);
        }
    }

    // Minimize chat widget
    function minimizeChatWidget(event) {
        event.stopPropagation();
        toggleChatWidget();
    }

    // Utility functions
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        if (date.toDateString() === now.toDateString()) return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

        return date.toLocaleDateString();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        applyChatWidgetState();
        initWebSocket();
        loadAnnouncements();
    });
</script>
