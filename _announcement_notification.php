<?php
/**
 * Announcement Notification Helper
 * Include this in headers to show announcement notifications for all user roles
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
    .notification-bell {
        position: relative;
        display: inline-block;
        margin: 0 15px;
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        z-index: 10;
    }

    .notification-dropdown {
        position: fixed;
        top: auto;
        right: 20px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 350px;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        display: none;
        z-index: 9999;
        margin-top: 5px;
    }

    .notification-dropdown.show {
        display: block !important;
        visibility: visible !important;
    }

    .notification-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.2s;
    }

    .notification-item:hover {
        background: #f8f9fa;
    }

    .notification-item.unread {
        background: #e7f3ff;
        font-weight: 600;
    }

    .notification-title {
        font-size: 13px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 4px;
    }

    .notification-desc {
        font-size: 12px;
        color: #495057;
        margin-bottom: 4px;
    }

    .notification-time {
        font-size: 11px;
        color: #6c757d;
    }

    .notification-empty {
        padding: 20px;
        text-align: center;
        color: #6c757d;
        font-size: 13px;
    }

    .notification-header {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        font-weight: 600;
        background: #f8f9fa;
    }
</style>

<div class="notification-bell">
    <a href="#" class="nav-link" id="announcementBell">
        <i class="fas fa-bell"></i>
        <?php if ($unread_announcements > 0): ?>
            <span class="notification-badge"><?php echo $unread_announcements; ?></span>
        <?php endif; ?>
    </a>
    <div class="notification-dropdown" id="announcementDropdown">
        <div class="notification-header">
            📢 Announcements
        </div>
        <div id="announcementList">
            <div class="notification-empty">Loading...</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const user_id = <?php echo $user_id; ?>;
    console.log('✅ Announcement bell initialized for user:', user_id);

    // Toggle handler
    function toggleAnnouncementDropdown(event) {
        event.preventDefault();
        console.log('🔔 Bell clicked, opening/closing dropdown');
        const dropdown = document.getElementById('announcementDropdown');

        if (!dropdown) {
            console.error('❌ Dropdown element not found!');
            return;
        }

        dropdown.classList.toggle('show');

        if (dropdown.classList.contains('show')) {
            console.log('Dropdown is now visible, loading announcements...');
            loadAnnouncements();
        }
    }

    // Load announcements
    function loadAnnouncements() {
        console.log('📥 Fetching announcements...');

        const container = document.getElementById('announcementList');
        if (!container) {
            console.error('❌ Container not found!');
            return;
        }

        container.innerHTML = '<div class="notification-empty">Loading...</div>';

        // Use absolute path to PHP API
        fetch(`/SIH/api_announcements.php?action=get&user_id=${user_id}`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Announcements data:', data);

                if (!data.announcements || data.announcements.length === 0) {
                    console.log('⚠️ No announcements in response');
                    container.innerHTML = '<div class="notification-empty">No announcements</div>';
                    return;
                }

                console.log('✅ Found', data.announcements.length, 'announcements');
                container.innerHTML = '';

                data.announcements.forEach((ann, index) => {
                    console.log(`📝 Processing announcement ${index}:`, ann.title);
                    const isRead = ann.read_on !== null;
                    const item = document.createElement('div');
                    item.className = `notification-item ${isRead ? '' : 'unread'}`;
                    item.onclick = () => markAnnouncementRead(ann.announcement_id);
                    item.innerHTML = `
                        <div class="notification-title">${escapeHtml(ann.title || '')}</div>
                        <div class="notification-desc">${escapeHtml((ann.description || '').substring(0, 50))}...</div>
                        <div class="notification-time">${formatTime(ann.created_on || new Date().toISOString())}</div>
                    `;
                    container.appendChild(item);
                    console.log(`✨ Announcement ${index} added to DOM`);
                });

                console.log('🎉 All announcements loaded successfully');
            })
            .catch(error => {
                console.error('❌ Error loading announcements:', error);
                container.innerHTML = '<div class="notification-empty">Error loading announcements</div>';
            });
    }

    function markAnnouncementRead(announcement_id) {
        console.log('✏️ Marking announcement as read:', announcement_id);
        fetch(`/SIH/api_announcements.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'mark_read',
                announcement_id: announcement_id
            })
        }).catch(err => console.error('Error marking as read:', err));
    }

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

        return date.toLocaleDateString();
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const bell = document.getElementById('announcementBell');
        const dropdown = document.getElementById('announcementDropdown');

        if (bell && dropdown && !bell.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Adjust dropdown position based on scroll
    window.addEventListener('scroll', function() {
        const bell = document.getElementById('announcementBell');
        const dropdown = document.getElementById('announcementDropdown');
        if (dropdown && dropdown.classList.contains('show') && bell) {
            const rect = bell.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + 10) + 'px';
        }
    });

    // Attach click handler to bell
    const bellEl = document.getElementById('announcementBell');
    if (bellEl) {
        console.log('✅ Attaching click handler to bell');
        bellEl.addEventListener('click', toggleAnnouncementDropdown);
    } else {
        console.error('❌ Bell element not found!');
    }
});
</script>
