<?php
include_once('../_functions.php');
global $conn;

/**
 * Get all orders for a farmer (customer)
 * @param int $user_id - Farmer/Customer ID
 * @return array - Array of orders
 */
function get_farmer_order_history($user_id)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        pp.user_id,
        pp.pro_id,
        p.pro_name,
        p.pro_image,
        p.type,
        p.pro_description,
        pp.pro_qty,
        pp.total_amt,
        pp.payment_method,
        pp.transaction_id,
        pp.created_on,
        v.user_id as vendor_user_id,
        u_vendor.user_name as vendor_name,
        vp.vendor_id
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    LEFT JOIN vendors v ON vp.vendor_id = v.vendor_id
    LEFT JOIN users u_vendor ON v.user_id = u_vendor.user_id
    WHERE pp.user_id = ?
    ORDER BY pp.created_on DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->bind_param('i', $user_id);
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
 * @param int $user_id - User ID (for security check)
 * @return array - Order details
 */
function get_farmer_order_detail($order_id, $user_id)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        pp.user_id,
        pp.pro_id,
        p.pro_name,
        p.pro_image,
        p.type,
        p.pro_description,
        p.pro_uses,
        p.pro_contents,
        pp.pro_qty,
        pp.total_amt,
        pp.payment_method,
        pp.transaction_id,
        pp.created_on,
        pp.created_by,
        v.user_id as vendor_user_id,
        u_vendor.user_name as vendor_name,
        u_vendor.email as vendor_email,
        u_vendor.mb_number as vendor_phone,
        vp.vendor_id
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    LEFT JOIN vendors v ON vp.vendor_id = v.vendor_id
    LEFT JOIN users u_vendor ON v.user_id = u_vendor.user_id
    WHERE pp.purchas_id = ? AND pp.user_id = ?
    LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $order = $result->fetch_assoc();
    $stmt->close();
    return $order;
}

/**
 * Get farmer order statistics
 * @param int $user_id - Farmer/Customer ID
 * @return array - Order statistics
 */
function get_farmer_order_stats($user_id)
{
    global $conn;
    
    $stats = [];
    
    // Total orders
    $query = "SELECT COUNT(DISTINCT purchas_id) as total_orders, 
              SUM(total_amt) as total_spent
    FROM purchase_product
    WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_orders'] = $row['total_orders'] ?? 0;
        $stats['total_spent'] = $row['total_spent'] ?? 0;
        $stmt->close();
    }
    
    // Total items purchased
    $query = "SELECT SUM(pro_qty) as total_items_purchased
    FROM purchase_product
    WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_items_purchased'] = $row['total_items_purchased'] ?? 0;
        $stmt->close();
    }
    
    // Average order value
    $stats['average_order_value'] = ($stats['total_orders'] > 0) ? ($stats['total_spent'] / $stats['total_orders']) : 0;
    
    return $stats;
}

/**
 * Get recent orders
 * @param int $user_id - Farmer/Customer ID
 * @param int $limit - Number of recent orders to fetch
 * @return array - Recent orders
 */
function get_farmer_recent_orders($user_id, $limit = 5)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        p.pro_name,
        pp.pro_qty,
        pp.total_amt,
        pp.created_on
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    WHERE pp.user_id = ?
    ORDER BY pp.created_on DESC
    LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('ii', $user_id, $limit);
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
 * Get favorite vendors (vendors farmer has purchased from most)
 * @param int $user_id - Farmer/Customer ID
 * @param int $limit - Number of vendors to fetch
 * @return array - Favorite vendors
 */
function get_farmer_favorite_vendors($user_id, $limit = 5)
{
    global $conn;
    
    $query = "SELECT 
        v.vendor_id,
        u.user_name as vendor_name,
        ven.company_name,
        COUNT(DISTINCT pp.purchas_id) as order_count,
        SUM(pp.total_amt) as total_spent
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    JOIN vendors v ON vp.vendor_id = v.vendor_id
    JOIN users u ON v.user_id = u.user_id
    LEFT JOIN vendors ven ON v.vendor_id = ven.vendor_id
    WHERE pp.user_id = ?
    GROUP BY v.vendor_id
    ORDER BY order_count DESC
    LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vendors = [];
    while ($row = $result->fetch_assoc()) {
        $vendors[] = $row;
    }
    
    $stmt->close();
    return $vendors;
}

?>
