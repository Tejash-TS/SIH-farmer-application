<?php
/**
 * Debug: Check announcements for vendor
 * Visit: http://localhost/SIH/debug_announcements.php
 */

session_start();
include_once('_functions.php');
require_once('ChatAnnouncementDAO.php');

global $conn;

// For this debug, let's get user 9 (the vendor that was having issues)
$vendor_user_id = 9;

echo "<h2>🔍 Debugging Announcements for Vendor User (ID: $vendor_user_id)</h2>";

// Get vendor role
$role_query = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($role_query);
$stmt->bind_param('i', $vendor_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$vendor_role = $user['role'] ?? 'unknown';
echo "<p><strong>User Role:</strong> $vendor_role</p>";

// Get all announcements in database
echo "<h3>All Announcements in Database:</h3>";
$all_query = "SELECT announcement_id, title, description, target_role, is_active FROM announcements ORDER BY created_on DESC";
$all_result = $conn->query($all_query);

if ($all_result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Target Role</th><th>Active</th></tr>";
    while ($ann = $all_result->fetch_assoc()) {
        $active = $ann['is_active'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>" . $ann['announcement_id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($ann['title'], 0, 30)) . "</td>";
        echo "<td>" . htmlspecialchars($ann['target_role']) . "</td>";
        echo "<td>" . $active . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No announcements found in database</p>";
}

// Get announcements visible to vendor
echo "<h3>Announcements Visible to Vendor:</h3>";
$chatDAO = new ChatAnnouncementDAO($conn);
$visible_announcements = $chatDAO->getAnnouncements($vendor_user_id, 50);

if (!empty($visible_announcements)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Read Status</th></tr>";
    foreach ($visible_announcements as $ann) {
        $read_status = $ann['read_on'] ? 'Read' : 'Unread';
        echo "<tr>";
        echo "<td>" . $ann['announcement_id'] . "</td>";
        echo "<td>" . htmlspecialchars($ann['title']) . "</td>";
        echo "<td>" . $read_status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No announcements visible to vendor</p>";
}

// Get unread count
$unread = $chatDAO->getUnreadAnnouncementCount($vendor_user_id);
echo "<h3>Unread Count: <strong>$unread</strong></h3>";

// Test the API directly
echo "<h3>Test API Response:</h3>";
echo "<pre>";
$_SESSION['user']['user_id'] = $vendor_user_id;
ob_start();
include('api_announcements.php');
$output = ob_get_clean();
echo htmlspecialchars($output);
echo "</pre>";

?>
