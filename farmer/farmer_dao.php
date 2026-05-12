<?php
/**
 * Farmer Data Access Object (DAO)
 * Handles all farmer-related database operations including product uploads and management
 */

class FarmerDAO {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get farmer profile by user_id
     */
    public function getFarmerProfile($user_id) {
        $stmt = $this->conn->prepare('SELECT * FROM farmers WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $farmer = $result->fetch_assoc() ?: null;
        $stmt->close();
        return $farmer;
    }
    
    /**
     * Create farmer profile
     */
    public function createFarmerProfile($user_id, $farm_name, $location, $phone, $farm_size, $crops_grown) {
        $stmt = $this->conn->prepare(
            'INSERT INTO farmers (user_id, farm_name, location, phone_number, farm_size, crops_grown, created_on, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)'
        );
        $stmt->bind_param('isssssi', $user_id, $farm_name, $location, $phone, $farm_size, $crops_grown, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Update farmer profile
     */
    public function updateFarmerProfile($farmer_id, $farm_name, $location, $phone, $farm_size, $crops_grown) {
        $stmt = $this->conn->prepare(
            'UPDATE farmers SET farm_name = ?, location = ?, phone_number = ?, farm_size = ?, crops_grown = ?, modified_on = NOW() 
             WHERE farmer_id = ?'
        );
        $stmt->bind_param('sssssi', $farm_name, $location, $phone, $farm_size, $crops_grown, $farmer_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Add product from farmer
     */
    public function addFarmerProduct($farmer_id, $pro_name, $pro_image, $pro_description, $pro_uses, $pro_contents, $type, $pro_price, $pro_qty, $user_id) {
        $this->conn->begin_transaction();
        try {
            // Insert product
            $source = 'farmer';
            $product_stmt = $this->conn->prepare(
                'INSERT INTO products (pro_name, pro_image, pro_description, pro_uses, pro_contents, type, farmer_id, product_source, is_block, is_active, created_on, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "N", "Y", NOW(), ?)'
            );
            $product_stmt->bind_param('ssssssisi', $pro_name, $pro_image, $pro_description, $pro_uses, $pro_contents, $type, $farmer_id, $source, $user_id);
            if (!$product_stmt->execute()) {
                throw new Exception('Product insert failed');
            }
            $pro_id = $this->conn->insert_id;
            $product_stmt->close();
            
            // Link product to farmer
            $link_stmt = $this->conn->prepare('INSERT INTO farmer_products (farmer_id, pro_id, created_on) VALUES (?, ?, NOW())');
            $link_stmt->bind_param('ii', $farmer_id, $pro_id);
            if (!$link_stmt->execute()) {
                throw new Exception('Farmer link failed');
            }
            $link_stmt->close();
            
            // Create approval record
            $approval_stmt = $this->conn->prepare(
                'INSERT INTO farmer_product_approval (pro_id, farmer_id, approval_status, created_on, created_by) 
                 VALUES (?, ?, "pending", NOW(), ?)'
            );
            $approval_stmt->bind_param('iii', $pro_id, $farmer_id, $user_id);
            if (!$approval_stmt->execute()) {
                throw new Exception('Approval insert failed');
            }
            $approval_stmt->close();
            
            // Create inventory record
            $inventory_stmt = $this->conn->prepare(
                'INSERT INTO pro_inventory (pro_id, pro_price, pro_qty, is_active, created_on, created_by) 
                 VALUES (?, ?, ?, "Y", NOW(), ?)'
            );
            $inventory_stmt->bind_param('isii', $pro_id, $pro_price, $pro_qty, $user_id);
            if (!$inventory_stmt->execute()) {
                throw new Exception('Inventory insert failed');
            }
            $inventory_stmt->close();
            
            $this->conn->commit();
            return ['status' => true, 'pro_id' => $pro_id, 'message' => 'Product submitted for approval'];
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['status' => false, 'message' => 'Unable to add product. ' . $e->getMessage()];
        }
    }
    
    /**
     * Get farmer products
     */
    public function getFarmerProducts($farmer_id) {
        $sql = 'SELECT p.pro_id, p.pro_name, p.pro_image, p.type, p.created_on, 
                        COALESCE(pi.pro_price, 0) AS pro_price, 
                        COALESCE(pi.pro_qty, 0) AS pro_qty, 
                        COALESCE(pa.approval_status, "pending") AS approval_status, 
                        pa.rejection_reason 
                FROM products p 
                LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y" 
                LEFT JOIN farmer_product_approval pa ON pa.pro_id = p.pro_id AND pa.farmer_id = p.farmer_id AND pa.is_active = "Y" 
                WHERE p.farmer_id = ? AND p.product_source = "farmer" 
                ORDER BY p.created_on DESC';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $farmer_id);
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
     * Get single farmer product
     */
    public function getFarmerProduct($pro_id, $farmer_id) {
        $stmt = $this->conn->prepare(
            'SELECT p.*, pi.pro_price, pi.pro_qty, pa.approval_status, pa.rejection_reason 
             FROM products p 
             LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y" 
             LEFT JOIN farmer_product_approval pa ON pa.pro_id = p.pro_id AND pa.is_active = "Y" 
             WHERE p.pro_id = ? AND p.farmer_id = ? AND p.product_source = "farmer"'
        );
        $stmt->bind_param('ii', $pro_id, $farmer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc() ?: null;
        $stmt->close();
        return $product;
    }
    
    /**
     * Update farmer product
     */
    public function updateFarmerProduct($pro_id, $farmer_id, $pro_name, $pro_image, $pro_description, $pro_uses, $pro_contents, $type, $pro_price, $pro_qty, $user_id) {
        $this->conn->begin_transaction();
        try {
            // Update product
            $product_stmt = $this->conn->prepare(
                'UPDATE products SET pro_name = ?, pro_image = ?, pro_description = ?, pro_uses = ?, pro_contents = ?, type = ?, modified_on = NOW(), modified_by = ? 
                 WHERE pro_id = ? AND farmer_id = ? AND product_source = "farmer"'
            );
            $product_stmt->bind_param('ssssssi ii', $pro_name, $pro_image, $pro_description, $pro_uses, $pro_contents, $type, $user_id, $pro_id, $farmer_id);
            if (!$product_stmt->execute()) {
                throw new Exception('Product update failed');
            }
            $product_stmt->close();
            
            // Update inventory
            $inventory_stmt = $this->conn->prepare(
                'UPDATE pro_inventory SET pro_price = ?, pro_qty = ?, modified_on = NOW(), modified_by = ? 
                 WHERE pro_id = ?'
            );
            $inventory_stmt->bind_param('sii', $pro_price, $pro_qty, $user_id, $pro_id);
            if (!$inventory_stmt->execute()) {
                throw new Exception('Inventory update failed');
            }
            $inventory_stmt->close();
            
            $this->conn->commit();
            return ['status' => true, 'message' => 'Product updated successfully'];
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['status' => false, 'message' => 'Unable to update product. ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete farmer product
     */
    public function deleteFarmerProduct($pro_id, $farmer_id) {
        $stmt = $this->conn->prepare(
            'UPDATE products SET is_active = "N" WHERE pro_id = ? AND farmer_id = ? AND product_source = "farmer"'
        );
        $stmt->bind_param('ii', $pro_id, $farmer_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Get all approved farmer products for browsing
     */
    public function getApprovedFarmerProducts($limit = 10, $offset = 0) {
        $sql = 'SELECT p.pro_id, p.pro_name, p.pro_image, p.type, p.pro_description, p.farmer_id, f.farm_name, 
                        COALESCE(pi.pro_price, 0) AS pro_price, 
                        COALESCE(pi.pro_qty, 0) AS pro_qty
                FROM products p 
                INNER JOIN farmer_product_approval pa ON p.pro_id = pa.pro_id AND pa.approval_status = "approved" AND pa.is_active = "Y" 
                LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y" 
                LEFT JOIN farmers f ON p.farmer_id = f.farmer_id
                WHERE p.product_source = "farmer" AND p.is_active = "Y" 
                ORDER BY p.created_on DESC 
                LIMIT ? OFFSET ?';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
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
     * Get farmer sales/orders
     */
    public function getFarmerOrders($farmer_id, $limit = 50) {
        $sql = 'SELECT pp.purchas_id, pp.user_id, pp.pro_id, pp.pro_qty, pp.total_amt, pp.payment_method, pp.transaction_id, pp.created_on, 
                        p.pro_name, u.user_name, u.email, u.mb_number
                FROM purchase_product pp 
                INNER JOIN products p ON pp.pro_id = p.pro_id 
                INNER JOIN users u ON pp.user_id = u.user_id
                WHERE p.farmer_id = ? AND p.product_source = "farmer" 
                ORDER BY pp.created_on DESC 
                LIMIT ?';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $farmer_id, $limit);
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
     * Get farmer statistics
     */
    public function getFarmerStats($farmer_id) {
        $stats = [];
        
        // Total products
        $stmt = $this->conn->prepare('SELECT COUNT(*) as total FROM products WHERE farmer_id = ? AND product_source = "farmer" AND is_active = "Y"');
        $stmt->bind_param('i', $farmer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_products'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Approved products
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) as total FROM farmer_product_approval WHERE farmer_id = ? AND approval_status = "approved" AND is_active = "Y"'
        );
        $stmt->bind_param('i', $farmer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['approved_products'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Pending approval
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) as total FROM farmer_product_approval WHERE farmer_id = ? AND approval_status = "pending" AND is_active = "Y"'
        );
        $stmt->bind_param('i', $farmer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['pending_products'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Total sales
        $stmt = $this->conn->prepare(
            'SELECT COALESCE(SUM(pp.total_amt), 0) as total FROM purchase_product pp 
             INNER JOIN products p ON pp.pro_id = p.pro_id 
             WHERE p.farmer_id = ? AND p.product_source = "farmer"'
        );
        $stmt->bind_param('i', $farmer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_sales'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        return $stats;
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
