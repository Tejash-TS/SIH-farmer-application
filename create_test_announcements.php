<?php
/**
 * Create Test Announcements for All Roles
 * Visit: http://localhost/SIH/create_test_announcements.php
 * Then delete this file
 */

session_start();
include_once('_functions.php');
require_once('ChatAnnouncementDAO.php');

global $conn;

if (!$conn) {
    die('❌ Database connection failed');
}

$chatDAO = new ChatAnnouncementDAO($conn);

echo "<h2>✨ Creating Test Announcements</h2>";

$test_announcements = [
    [
        'title' => 'Welcome to CropIntel - All Users',
        'description' => 'This announcement is visible to ALL users including farmers, buyers, consultants, and vendors.',
        'target_role' => 'all'
    ],
    [
        'title' => 'Farmer News',
        'description' => 'This announcement is only visible to farmers.',
        'target_role' => 'farmer'
    ],
    [
        'title' => 'Buyer Notification',
        'description' => 'This announcement is only visible to buyers.',
        'target_role' => 'buyer'
    ],
    [
        'title' => 'Consultant Update',
        'description' => 'This announcement is only visible to consultants.',
        'target_role' => 'consultant'
    ],
    [
        'title' => 'Vendor Information',
        'description' => 'This announcement is only visible to vendors.',
        'target_role' => 'vendor'
    ],
    [
        'title' => 'Admin Notice',
        'description' => 'This announcement is visible to all users.',
        'target_role' => 'all'
    ]
];

foreach ($test_announcements as $ann) {
    $result = $chatDAO->createAnnouncement(
        $ann['title'],
        $ann['description'],
        $ann['target_role'],
        1  // Created by admin (user_id = 1)
    );
    
    if ($result['status']) {
        echo "<p>✅ Created: <strong>" . htmlspecialchars($ann['title']) . "</strong> for: <strong>" . $ann['target_role'] . "</strong></p>";
    } else {
        echo "<p>❌ Failed to create: " . htmlspecialchars($ann['title']) . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>✨ All test announcements created!</strong></p>";
echo "<p>Now log in as different roles and check the bell icon (🔔)</p>";
echo "<ul>";
echo "<li>Go to: <a href='farmer/dashboard.php' target='_blank'>Farmer Dashboard</a></li>";
echo "<li>Go to: <a href='buyer/dashboard.php' target='_blank'>Buyer Dashboard</a></li>";
echo "<li>Go to: <a href='consultant/dashboard.php' target='_blank'>Consultant Dashboard</a></li>";
echo "<li>Go to: <a href='vendor/dashboard.php' target='_blank'>Vendor Dashboard</a></li>";
echo "<li>Go to: <a href='admin/dashboard.php' target='_blank'>Admin Dashboard</a></li>";
echo "</ul>";

?>
