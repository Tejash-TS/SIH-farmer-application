<?php
/**
 * Debug Script: Create test announcement
 * Visit this page once: http://localhost/SIH/test_announcement.php
 * Then delete this file
 */

session_start();
include_once('_functions.php');
require_once('ChatAnnouncementDAO.php');

global $conn;

if (!$conn) {
    die('Database connection failed');
}

$chatDAO = new ChatAnnouncementDAO($conn);

// Create a test announcement visible to ALL users
$result = $chatDAO->createAnnouncement(
    'Welcome to CropIntel!',
    'This is a system-wide announcement. It should be visible to farmers, buyers, consultants, and vendors.',
    'all',
    1  // Created by admin user (assuming user_id = 1)
);

if ($result['status']) {
    echo "<h2>✅ Test Announcement Created Successfully!</h2>";
    echo "<p>Announcement ID: " . $result['announcement_id'] . "</p>";
    echo "<p>Target Role: <strong>all</strong></p>";
    echo "<p>Go to your dashboard and click the bell icon to see it.</p>";
    echo "<p><a href='farmer/dashboard.php'>Go to Farmer Dashboard →</a></p>";
} else {
    echo "<h2>❌ Failed to create announcement</h2>";
    echo "<p>Error: " . ($result['error'] ?? 'Unknown error') . "</p>";
}
?>
