<?php
/**
 * Announcement Notification Helper - Modal Version
 * Include this in headers to show announcement notifications
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    return;
}

global $conn;
require_once(__DIR__ . '/ChatAnnouncementDAO.php');

$user_id = intval($_SESSION['user']['user_id']);
$chatDAO = new ChatAnnouncementDAO($conn);
$unread_announcements = $chatDAO->getUnreadAnnouncementCount($user_id);
?>

<style>
    .announcement-bell-btn {
        position: relative;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 20px;
        padding: 5px 10px;
        color: inherit;
    }

    .announcement-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
        z-index: 10;
    }

    /* Modal Styles */
    #announcementModal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
        align-items: center;
        justify-content: center;
    }

    #announcementModal.show {
        display: flex;
    }

    .announcement-modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        max-height: 70vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .announcement-modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }

    .announcement-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .announcement-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .announcement-modal-close:hover {
        color: #000;
    }

    .announcement-modal-body {
        padding: 20px;
    }

    .announcement-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.2s;
    }

    .announcement-item:last-child {
        border-bottom: none;
    }

    .announcement-item:hover {
        background: #f8f9fa;
    }

    .announcement-item.unread {
        background: #e7f3ff;
        border-left: 4px solid #0066cc;
    }

    .announcement-item-title {
        font-size: 14px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 5px;
    }

    .announcement-item-desc {
        font-size: 13px;
        color: #495057;
        margin-bottom: 5px;
    }

    .announcement-item-time {
        font-size: 12px;
        color: #999;
    }

    .announcement-empty {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }

    .announcement-loading {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }
</style>

<!-- Bell Icon in Navbar -->
<button class="announcement-bell-btn" id="announcementBell" title="Announcements">
    <i class="fas fa-bell"></i>
    <?php if ($unread_announcements > 0): ?>
        <span class="announcement-badge"><?php echo $unread_announcements; ?></span>
    <?php endif; ?>
</button>

<!-- Announcement Modal -->
<div id="announcementModal">
    <div class="announcement-modal-content">
        <div class="announcement-modal-header">
            <h3>📢 Announcements</h3>
            <button class="announcement-modal-close" id="announcementClose">&times;</button>
        </div>
        <div class="announcement-modal-body">
            <div id="announcementList">
                <div class="announcement-loading">Loading announcements...</div>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables
    const user_id = <?php echo $user_id; ?>;
    const announcementBell = document.getElementById('announcementBell');
    const announcementModal = document.getElementById('announcementModal');
    const announcementClose = document.getElementById('announcementClose');
    const announcementList = document.getElementById('announcementList');

    console.log('✅ Announcement bell initialized for user:', user_id);

    // Open modal
    announcementBell.addEventListener('click', function() {
        console.log('🔔 Bell clicked, opening modal');
        announcementModal.classList.add('show');
        loadAnnouncements();
    });

    // Close modal
    announcementClose.addEventListener('click', function() {
        console.log('✖️ Closing modal');
        announcementModal.classList.remove('show');
    });

    // Close when clicking outside
    announcementModal.addEventListener('click', function(e) {
        if (e.target === announcementModal) {
            announcementModal.classList.remove('show');
        }
    });

    // Load announcements
    function loadAnnouncements() {
        console.log('📥 Fetching announcements...');
        announcementList.innerHTML = '<div class="announcement-loading">Loading...</div>';

        fetch(`/SIH/api_announcements.php?action=get&user_id=${user_id}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('✅ Announcements data:', data);

                if (!data.announcements || data.announcements.length === 0) {
                    announcementList.innerHTML = '<div class="announcement-empty">No announcements</div>';
                    return;
                }

                announcementList.innerHTML = '';

                data.announcements.forEach((ann) => {
                    console.log('📝 Adding announcement:', ann.title);
                    const isRead = ann.read_on !== null;
                    const item = document.createElement('div');
                    item.className = `announcement-item ${isRead ? '' : 'unread'}`;
                    item.innerHTML = `
                        <div class="announcement-item-title">${escapeHtml(ann.title || '')}</div>
                        <div class="announcement-item-desc">${escapeHtml((ann.description || '').substring(0, 80))}</div>
                        <div class="announcement-item-time">${formatTime(ann.created_on || new Date().toISOString())}</div>
                    `;
                    item.addEventListener('click', () => markRead(ann.announcement_id));
                    announcementList.appendChild(item);
                });

                console.log('🎉 All announcements loaded');
            })
            .catch(error => {
                console.error('❌ Error:', error);
                announcementList.innerHTML = '<div class="announcement-empty">Error loading announcements</div>';
            });
    }

    // Mark as read
    function markRead(id) {
        console.log('✏️ Marking announcement as read:', id);
        fetch(`/SIH/api_announcements.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', announcement_id: id })
        }).catch(err => console.error('Error:', err));
    }

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        if (diff < 60000) return 'now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        return date.toLocaleDateString();
    }
</script>
