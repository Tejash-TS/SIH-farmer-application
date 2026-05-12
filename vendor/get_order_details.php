<?php
session_start();
include_once('../_functions.php');
include_once('orders_dao.php');
global $conn;

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'vendor') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$user_id = intval($_SESSION['user']['user_id']);

if ($order_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid order ID'
    ]);
    exit;
}

// Get vendor info
$stmt = $conn->prepare('SELECT vendor_id FROM vendors WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$vendor_result = $stmt->get_result();
$vendor = $vendor_result->fetch_assoc();
$stmt->close();

if (!$vendor) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor profile not found'
    ]);
    exit;
}

$vendor_id = intval($vendor['vendor_id']);
$order = get_order_details($order_id, $vendor_id);

if (!$order) {
    echo json_encode([
        'success' => false,
        'message' => 'Order not found'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $order
]);
?>
