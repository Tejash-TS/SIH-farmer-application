<?php
/**
 * PHP API Endpoint for Announcements
 * Used by notification bell when FastAPI is not available
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include_once('_functions.php');
require_once('ChatAnnouncementDAO.php');

global $conn;

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$user_id = intval($_SESSION['user']['user_id']);
$chatDAO = new ChatAnnouncementDAO($conn);

// GET /api_announcements.php?action=get&user_id=X
// POST /api_announcements.php with action=mark_read

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get';
    
    if ($action === 'get') {
        $announcements = $chatDAO->getAnnouncements($user_id, 50);
        
        // Sanitize announcements - ensure all fields have values
        $announcements = array_map(function($ann) {
            return [
                'announcement_id' => intval($ann['announcement_id'] ?? 0),
                'title' => $ann['title'] ?? 'Untitled',
                'description' => $ann['description'] ?? '',
                'target_role' => $ann['target_role'] ?? 'all',
                'sender_id' => intval($ann['sender_id'] ?? 0),
                'created_on' => $ann['created_on'] ?? date('Y-m-d H:i:s'),
                'sender_name' => $ann['sender_name'] ?? 'Admin',
                'read_on' => $ann['read_on'] ?? null
            ];
        }, $announcements);
        
        echo json_encode(['announcements' => $announcements]);
    } elseif ($action === 'unread_count') {
        $count = $chatDAO->getUnreadAnnouncementCount($user_id);
        echo json_encode(['unread_count' => $count]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'mark_read') {
        $announcement_id = intval($data['announcement_id'] ?? 0);
        if ($announcement_id > 0) {
            $result = $chatDAO->markAnnouncementAsRead($announcement_id, $user_id);
            echo json_encode(['status' => $result ? 'success' : 'error']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid announcement_id']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
