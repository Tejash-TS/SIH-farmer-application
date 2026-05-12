<?php
include_once('../_functions.php');
global $conn;

/**
 * Get all orders for a vendor's products
 * @param int $vendor_id - Vendor ID
 * @param string $status - Filter by order status (optional)
 * @return array - Array of orders
 */
function get_vendor_orders($vendor_id, $status = null)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        pp.user_id,
        u.user_name,
        u.email,
        u.mb_number,
        pp.pro_id,
        p.pro_name,
        p.pro_image,
        pp.pro_qty,
        pp.total_amt,
        pp.payment_method,
        pp.transaction_id,
        pp.created_on,
        pp.created_by,
        vp.vendor_id
    FROM purchase_product pp
    JOIN user_cart uc ON uc.pro_id = pp.pro_id AND uc.user_id = pp.user_id
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    JOIN users u ON pp.user_id = u.user_id
    WHERE vp.vendor_id = ? AND vp.is_active = 'Y'
    ORDER BY pp.created_on DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->bind_param('i', $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    $stmt->close();
    return $orders;
}

/**
 * Get order details by order ID
 * @param int $order_id - Purchase ID
 * @param int $vendor_id - Vendor ID (for security check)
 * @return array - Order details
 */
function get_order_details($order_id, $vendor_id)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        pp.user_id,
        u.user_name,
        u.email,
        u.mb_number,
        pp.pro_id,
        p.pro_name,
        p.pro_image,
        p.pro_description,
        p.pro_uses,
        p.pro_contents,
        p.type,
        pp.pro_qty,
        pp.total_amt,
        pp.payment_method,
        pp.transaction_id,
        pp.created_on,
        vp.vendor_id
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    JOIN users u ON pp.user_id = u.user_id
    WHERE pp.purchas_id = ? AND vp.vendor_id = ? AND vp.is_active = 'Y'
    LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->bind_param('ii', $order_id, $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $order = $result->fetch_assoc();
    $stmt->close();
    return $order;
}

/**
 * Get vendor order statistics
 * @param int $vendor_id - Vendor ID
 * @return array - Order statistics
 */
function get_vendor_order_stats($vendor_id)
{
    global $conn;
    
    $stats = [];
    
    // Total orders
    $query = "SELECT COUNT(DISTINCT pp.purchas_id) as total_orders, 
              SUM(pp.total_amt) as total_revenue
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    WHERE vp.vendor_id = ? AND vp.is_active = 'Y'";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_orders'] = $row['total_orders'] ?? 0;
        $stats['total_revenue'] = $row['total_revenue'] ?? 0;
        $stmt->close();
    }
    
    // Total items sold
    $query = "SELECT SUM(pp.pro_qty) as total_items_sold
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    WHERE vp.vendor_id = ? AND vp.is_active = 'Y'";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_items_sold'] = $row['total_items_sold'] ?? 0;
        $stmt->close();
    }
    
    return $stats;
}

/**
 * Get vendor's products list
 * @param int $vendor_id - Vendor ID
 * @return array - Products list
 */
function get_vendor_products($vendor_id)
{
    global $conn;
    
    $query = "SELECT 
        p.pro_id,
        p.pro_name,
        p.type,
        COUNT(DISTINCT pp.purchas_id) as order_count,
        SUM(pp.pro_qty) as total_qty_sold
    FROM products p
    LEFT JOIN purchase_product pp ON p.pro_id = pp.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    WHERE vp.vendor_id = ? AND vp.is_active = 'Y' AND p.is_active = 'Y'
    GROUP BY p.pro_id
    ORDER BY order_count DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->bind_param('i', $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

?>
