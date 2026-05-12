<?php
session_start();
include_once('../_functions.php');
include_once('orders_dao.php');
global $conn;

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid order ID'
    ]);
    exit;
}

$order = get_admin_order_detail($order_id);

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
