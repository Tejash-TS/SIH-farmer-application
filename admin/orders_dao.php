<?php
include_once('../_functions.php');
global $conn;

/**
 * Get all orders in the system
 * @param string $filter - Filter by status or vendor (optional)
 * @return array - Array of all orders
 */
function get_all_orders($filter = null)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        pp.user_id,
        u.user_name as customer_name,
        u.email as customer_email,
        u.mb_number as customer_phone,
        pp.pro_id,
        p.pro_name,
        p.type,
        pp.pro_qty,
        pp.total_amt,
        pp.payment_method,
        pp.transaction_id,
        pp.created_on,
        v.vendor_id,
        u_vendor.user_name as vendor_name,
        u_vendor.email as vendor_email,
        ven.company_name
    FROM purchase_product pp
    JOIN users u ON pp.user_id = u.user_id
    JOIN products p ON pp.pro_id = p.pro_id
    LEFT JOIN vendor_products vp ON p.pro_id = vp.pro_id
    LEFT JOIN vendors v ON vp.vendor_id = v.vendor_id
    LEFT JOIN users u_vendor ON v.user_id = u_vendor.user_id
    LEFT JOIN vendors ven ON v.vendor_id = ven.vendor_id
    ORDER BY pp.created_on DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
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
 * Get order details by order ID for admin
 * @param int $order_id - Purchase ID
 * @return array - Order details
 */
function get_admin_order_detail($order_id)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        pp.user_id,
        u.user_name as customer_name,
        u.email as customer_email,
        u.mb_number as customer_phone,
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
        v.vendor_id,
        u_vendor.user_name as vendor_name,
        u_vendor.email as vendor_email,
        u_vendor.mb_number as vendor_phone,
        ven.company_name,
        ven.location
    FROM purchase_product pp
    JOIN users u ON pp.user_id = u.user_id
    JOIN products p ON pp.pro_id = p.pro_id
    LEFT JOIN vendor_products vp ON p.pro_id = vp.pro_id
    LEFT JOIN vendors v ON vp.vendor_id = v.vendor_id
    LEFT JOIN users u_vendor ON v.user_id = u_vendor.user_id
    LEFT JOIN vendors ven ON v.vendor_id = ven.vendor_id
    WHERE pp.purchas_id = ?
    LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $order = $result->fetch_assoc();
    $stmt->close();
    return $order;
}

/**
 * Get admin order statistics
 * @return array - Order statistics
 */
function get_admin_order_stats()
{
    global $conn;
    
    $stats = [];
    
    // Total orders
    $query = "SELECT COUNT(DISTINCT purchas_id) as total_orders, 
              SUM(total_amt) as total_revenue
    FROM purchase_product";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_orders'] = $row['total_orders'] ?? 0;
        $stats['total_revenue'] = $row['total_revenue'] ?? 0;
        $stmt->close();
    }
    
    // Total items sold
    $query = "SELECT SUM(pro_qty) as total_items_sold
    FROM purchase_product";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_items_sold'] = $row['total_items_sold'] ?? 0;
        $stmt->close();
    }
    
    // Total customers
    $query = "SELECT COUNT(DISTINCT user_id) as total_customers
    FROM purchase_product";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_customers'] = $row['total_customers'] ?? 0;
        $stmt->close();
    }
    
    return $stats;
}

/**
 * Get orders by vendor
 * @param int $vendor_id - Vendor ID
 * @return array - Orders list
 */
function get_orders_by_vendor($vendor_id)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        pp.user_id,
        u.user_name as customer_name,
        u.email as customer_email,
        pp.pro_id,
        p.pro_name,
        pp.pro_qty,
        pp.total_amt,
        pp.created_on
    FROM purchase_product pp
    JOIN users u ON pp.user_id = u.user_id
    JOIN products p ON pp.pro_id = p.pro_id
    JOIN vendor_products vp ON p.pro_id = vp.pro_id
    WHERE vp.vendor_id = ?
    ORDER BY pp.created_on DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
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
 * Get orders by customer
 * @param int $user_id - Customer/User ID
 * @return array - Orders list
 */
function get_orders_by_customer($user_id)
{
    global $conn;
    
    $query = "SELECT 
        pp.purchas_id,
        p.pro_name,
        pp.pro_qty,
        pp.total_amt,
        pp.created_on,
        v.vendor_id,
        u_vendor.user_name as vendor_name
    FROM purchase_product pp
    JOIN products p ON pp.pro_id = p.pro_id
    LEFT JOIN vendor_products vp ON p.pro_id = vp.pro_id
    LEFT JOIN vendors v ON vp.vendor_id = v.vendor_id
    LEFT JOIN users u_vendor ON v.user_id = u_vendor.user_id
    WHERE pp.user_id = ?
    ORDER BY pp.created_on DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
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
 * Get revenue summary by vendor
 * @return array - Revenue summary
 */
function get_revenue_by_vendor()
{
    global $conn;
    
    $query = "SELECT 
        v.vendor_id,
        u.user_name as vendor_name,
        ven.company_name,
        COUNT(DISTINCT pp.purchas_id) as order_count,
        SUM(pp.total_amt) as total_revenue,
        SUM(pp.pro_qty) as items_sold
    FROM vendor_products vp
    JOIN vendors v ON vp.vendor_id = v.vendor_id
    JOIN users u ON v.user_id = u.user_id
    JOIN vendors ven ON v.vendor_id = ven.vendor_id
    LEFT JOIN products p ON vp.pro_id = p.pro_id
    LEFT JOIN purchase_product pp ON p.pro_id = pp.pro_id
    GROUP BY v.vendor_id
    ORDER BY total_revenue DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}

?>
