<?php
/**
 * Buyer Data Access Object (DAO)
 * Handles all buyer-related database operations including shopping and orders
 */

class BuyerDAO {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get buyer profile by user_id
     */
    public function getBuyerProfile($user_id) {
        $stmt = $this->conn->prepare('SELECT * FROM buyers WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $buyer = $result->fetch_assoc() ?: null;
        $stmt->close();
        return $buyer;
    }
    
    /**
     * Create buyer profile
     */
    public function createBuyerProfile($user_id, $address, $phone) {
        $stmt = $this->conn->prepare(
            'INSERT INTO buyers (user_id, address, phone_number, created_on, created_by) 
             VALUES (?, ?, ?, NOW(), ?)'
        );
        $stmt->bind_param('issi', $user_id, $address, $phone, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Update buyer profile
     */
    public function updateBuyerProfile($buyer_id, $address, $phone) {
        $stmt = $this->conn->prepare(
            'UPDATE buyers SET address = ?, phone_number = ?, modified_on = NOW() WHERE buyer_id = ?'
        );
        $stmt->bind_param('ssi', $address, $phone, $buyer_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Get all farmer products (approved only)
     */
    public function getFarmerProducts($limit = 20, $offset = 0, $search = '', $type = '') {
        $where = 'p.product_source = "farmer" AND p.is_active = "Y" AND pa.approval_status = "approved" AND pa.is_active = "Y"';
        
        if (!empty($search)) {
            $search = '%' . $search . '%';
            $where .= ' AND (p.pro_name LIKE ? OR p.pro_description LIKE ?)';
        }
        
        if (!empty($type)) {
            $where .= ' AND p.type = ?';
        }
        
        $sql = 'SELECT p.pro_id, p.pro_name, p.pro_image, p.type, p.pro_description, p.farmer_id, f.farm_name, 
                        COALESCE(pi.pro_price, 0) AS pro_price, 
                        COALESCE(pi.pro_qty, 0) AS pro_qty,
                        COALESCE(COUNT(pr.review_id), 0) AS review_count,
                        COALESCE(AVG(pr.rating), 0) AS avg_rating
                FROM products p 
                INNER JOIN farmer_product_approval pa ON p.pro_id = pa.pro_id 
                LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y" 
                LEFT JOIN farmers f ON p.farmer_id = f.farmer_id
                LEFT JOIN product_reviews pr ON pr.pro_id = p.pro_id AND pr.product_source = "farmer" AND pr.is_active = "Y"
                WHERE ' . $where . '
                GROUP BY p.pro_id
                ORDER BY p.created_on DESC 
                LIMIT ? OFFSET ?';
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search) && !empty($type)) {
            $stmt->bind_param('sssii', $search, $search, $type, $limit, $offset);
        } elseif (!empty($search)) {
            $stmt->bind_param('ssii', $search, $search, $limit, $offset);
        } elseif (!empty($type)) {
            $stmt->bind_param('sii', $type, $limit, $offset);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
        return $products;
    }
    
    /**
     * Get all vendor products (approved only)
     */
    public function getVendorProducts($limit = 20, $offset = 0, $search = '', $type = '') {
        $where = 'p.product_source = "vendor" AND p.is_active = "Y" AND pa.approval_status = "approved" AND pa.is_active = "Y"';
        
        if (!empty($search)) {
            $search = '%' . $search . '%';
            $where .= ' AND (p.pro_name LIKE ? OR p.pro_description LIKE ?)';
        }
        
        if (!empty($type)) {
            $where .= ' AND p.type = ?';
        }
        
        $sql = 'SELECT p.pro_id, p.pro_name, p.pro_image, p.type, p.pro_description, p.vendor_id, v.company_name, 
                        COALESCE(pi.pro_price, 0) AS pro_price, 
                        COALESCE(pi.pro_qty, 0) AS pro_qty,
                        COALESCE(COUNT(pr.review_id), 0) AS review_count,
                        COALESCE(AVG(pr.rating), 0) AS avg_rating
                FROM products p 
                INNER JOIN product_approval pa ON p.pro_id = pa.pro_id 
                LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y" 
                LEFT JOIN vendors v ON p.vendor_id = v.vendor_id
                LEFT JOIN product_reviews pr ON pr.pro_id = p.pro_id AND pr.product_source = "vendor" AND pr.is_active = "Y"
                WHERE ' . $where . '
                GROUP BY p.pro_id
                ORDER BY p.created_on DESC 
                LIMIT ? OFFSET ?';
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search) && !empty($type)) {
            $stmt->bind_param('sssii', $search, $search, $type, $limit, $offset);
        } elseif (!empty($search)) {
            $stmt->bind_param('ssii', $search, $search, $limit, $offset);
        } elseif (!empty($type)) {
            $stmt->bind_param('sii', $type, $limit, $offset);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
        return $products;
    }
    
    /**
     * Get combined products from both farmer and vendor
     */
    public function getAllProducts($limit = 20, $offset = 0, $search = '', $type = '') {
        $farmer_products = $this->getFarmerProducts($limit, $offset, $search, $type);
        $vendor_products = $this->getVendorProducts($limit, $offset, $search, $type);
        return array_merge($farmer_products, $vendor_products);
    }
    
    /**
     * Get product details
     */
    public function getProductDetails($pro_id) {
        $sql = 'SELECT p.*, pi.pro_price, pi.pro_qty, f.farm_name, f.farmer_id, v.company_name, v.vendor_id
                FROM products p 
                LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y"
                LEFT JOIN farmers f ON p.farmer_id = f.farmer_id AND p.product_source = "farmer"
                LEFT JOIN vendors v ON p.vendor_id = v.vendor_id AND p.product_source = "vendor"
                WHERE p.pro_id = ? AND p.is_active = "Y"';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $pro_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc() ?: null;
        $stmt->close();
        return $product;
    }
    
    /**
     * Add to cart
     */
    public function addToCart($user_id, $pro_id, $quantity) {
        $stmt = $this->conn->prepare(
            'INSERT INTO user_cart (user_id, pro_id, pro_qty, created_on) VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE pro_qty = pro_qty + ?'
        );
        $stmt->bind_param('iiii', $user_id, $pro_id, $quantity, $quantity);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Get cart items
     */
    public function getCartItems($user_id) {
        $sql = 'SELECT c.*, p.pro_name, p.pro_image, p.type, p.product_source, pi.pro_price,
                       f.farm_name, v.company_name
                FROM user_cart c 
                INNER JOIN products p ON c.pro_id = p.pro_id AND p.is_active = "Y"
                LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y"
                LEFT JOIN farmers f ON p.farmer_id = f.farmer_id
                LEFT JOIN vendors v ON p.vendor_id = v.vendor_id
                WHERE c.user_id = ?
                ORDER BY c.created_on DESC';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }
    
    /**
     * Remove from cart
     */
    public function removeFromCart($user_id, $pro_id) {
        $stmt = $this->conn->prepare('DELETE FROM user_cart WHERE user_id = ? AND pro_id = ?');
        $stmt->bind_param('ii', $user_id, $pro_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Update cart quantity
     */
    public function updateCartQuantity($user_id, $pro_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($user_id, $pro_id);
        }
        
        $stmt = $this->conn->prepare('UPDATE user_cart SET pro_qty = ? WHERE user_id = ? AND pro_id = ?');
        $stmt->bind_param('iii', $quantity, $user_id, $pro_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Place order
     */
    public function placeOrder($user_id, $total_amount, $payment_method, $transaction_id = null) {
        $this->conn->begin_transaction();
        try {
            $cart_items = $this->getCartItems($user_id);
            
            if (empty($cart_items)) {
                throw new Exception('Cart is empty');
            }
            
            foreach ($cart_items as $item) {
                $item_total = (float)($item['pro_price'] ?? 0) * (int)($item['pro_qty'] ?? 0);
                $order_stmt = $this->conn->prepare(
                    'INSERT INTO purchase_product (user_id, pro_id, pro_qty, total_amt, payment_method, transaction_id, product_source, seller_id, created_on, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)'
                );
                
                $seller_id = $item['product_source'] === 'farmer' ? $item['farmer_id'] : $item['vendor_id'];
                
                $order_stmt->bind_param(
                    'iiidsssii',
                    $user_id,
                    $item['pro_id'],
                    $item['pro_qty'],
                    $item_total,
                    $payment_method,
                    $transaction_id,
                    $item['product_source'],
                    $seller_id,
                    $user_id
                );
                
                if (!$order_stmt->execute()) {
                    throw new Exception('Order insert failed');
                }
                $order_stmt->close();
            }
            
            // Clear cart
            $clear_stmt = $this->conn->prepare('DELETE FROM user_cart WHERE user_id = ?');
            $clear_stmt->bind_param('i', $user_id);
            if (!$clear_stmt->execute()) {
                throw new Exception('Cart clear failed');
            }
            $clear_stmt->close();
            
            $this->conn->commit();
            return ['status' => true, 'message' => 'Order placed successfully'];
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['status' => false, 'message' => 'Unable to place order: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get buyer order history
     */
    public function getOrderHistory($user_id) {
        $sql = 'SELECT pp.purchas_id, pp.pro_id, pp.pro_qty, pp.total_amt, pp.payment_method, pp.product_source, pp.created_on,
                       p.pro_name, p.pro_image, f.farm_name, v.company_name
                FROM purchase_product pp 
                INNER JOIN products p ON pp.pro_id = p.pro_id 
                LEFT JOIN farmers f ON p.farmer_id = f.farmer_id AND pp.product_source = "farmer"
                LEFT JOIN vendors v ON p.vendor_id = v.vendor_id AND pp.product_source = "vendor"
                WHERE pp.user_id = ?
                ORDER BY pp.created_on DESC';
        
        $stmt = $this->conn->prepare($sql);
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
     * Add product review
     */
    public function addReview($user_id, $pro_id, $rating, $review_text, $product_source, $seller_id) {
        $stmt = $this->conn->prepare(
            'INSERT INTO product_reviews (pro_id, user_id, rating, review_text, seller_id, product_source, is_active, created_on) 
             VALUES (?, ?, ?, ?, ?, ?, "Y", NOW())'
        );
        $stmt->bind_param('iiisis', $pro_id, $user_id, $rating, $review_text, $seller_id, $product_source);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Get product reviews
     */
    public function getProductReviews($pro_id, $product_source = '') {
        $sql = 'SELECT pr.*, u.user_name FROM product_reviews pr 
                INNER JOIN users u ON pr.user_id = u.user_id
                WHERE pr.pro_id = ? AND pr.is_active = "Y"';
        
        if (!empty($product_source)) {
            $sql .= ' AND pr.product_source = ?';
        }
        
        $sql .= ' ORDER BY pr.created_on DESC';
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($product_source)) {
            $stmt->bind_param('is', $pro_id, $product_source);
        } else {
            $stmt->bind_param('i', $pro_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();
        return $reviews;
    }
    
    /**
     * Submit feedback to the system
     */
    public function submitFeedback($user_id, $feedback_type, $target_user_id, $rating, $comment) {
        $stmt = $this->conn->prepare(
            'INSERT INTO feedback_reports (user_id, feedback_type, target_user_id, rating, comment, created_on, created_by) 
             VALUES (?, ?, ?, ?, ?, NOW(), ?)'
        );
        $stmt->bind_param('isiiis', $user_id, $feedback_type, $target_user_id, $rating, $comment, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
