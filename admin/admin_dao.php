<?php

// ============================================
// ADMIN DATA ACCESS OBJECT (DAO)
// ============================================

class AdminDAO {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    // ============================================
    // VIDEO MANAGEMENT
    // ============================================

    /**
     * Get all pending videos for approval
     */
    public function getPendingVideos() {
        $sql = "SELECT 
                    vt.video_tutorial_id,
                    vt.title,
                    vt.description,
                    vt.thumbnail,
                    vt.video,
                    u.user_name as uploaded_by_name,
                    u.email,
                    vt.created_on
                FROM video_tutorial vt
                JOIN users u ON vt.uploaded_by = u.user_id
                WHERE vt.approval_status = 'pending'
                AND vt.is_active = 'Y'
                ORDER BY vt.created_on DESC";
        
        $result = $this->conn->query($sql);
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row;
        }
        return $videos;
    }

    /**
     * Approve video tutorial
     */
    public function approveVideo($video_id, $admin_id) {
        $sql = "UPDATE video_tutorial 
                SET approval_status = 'approved',
                    approved_by = $admin_id,
                    is_active = 'Y'
                WHERE video_tutorial_id = $video_id";
        
        return $this->conn->query($sql);
    }

    /**
     * Reject video tutorial
     */
    public function rejectVideo($video_id, $rejection_reason) {
        $sql = "UPDATE video_tutorial 
                SET approval_status = 'rejected',
                    is_active = 'N'
                WHERE video_tutorial_id = $video_id";
        
        return $this->conn->query($sql);
    }

    // ============================================
    // VENDORS MANAGEMENT
    // ============================================

    /**
     * Get all pending vendor registrations
     */
    public function getPendingVendors() {
        $sql = "SELECT 
                    v.vendor_id,
                    v.user_id,
                    u.user_name,
                    u.email,
                    u.mb_number,
                    v.company_name,
                    v.license_no,
                    v.location,
                    v.phone_number,
                    v.verification_status,
                    v.created_on
                FROM vendors v
                JOIN users u ON v.user_id = u.user_id
                WHERE v.verification_status = 'pending'
                ORDER BY v.created_on ASC";
        
        $result = $this->conn->query($sql);
        $vendors = [];
        while ($row = $result->fetch_assoc()) {
            $vendors[] = $row;
        }
        return $vendors;
    }

    /**
     * Get all approved vendors
     */
    public function getApprovedVendors() {
        $sql = "SELECT 
                    v.vendor_id,
                    v.user_id,
                    u.user_name,
                    u.email,
                    v.company_name,
                    v.license_no,
                    v.location,
                    v.phone_number,
                    v.verification_status,
                    v.created_on
                FROM vendors v
                JOIN users u ON v.user_id = u.user_id
                WHERE v.verification_status = 'approved'
                AND v.is_active = 'Y'
                ORDER BY v.created_on DESC";
        
        $result = $this->conn->query($sql);
        $vendors = [];
        while ($row = $result->fetch_assoc()) {
            $vendors[] = $row;
        }
        return $vendors;
    }

    /**
     * Approve vendor registration
     */
    public function approveVendor($vendor_id, $admin_id) {
        $sql = "UPDATE vendors 
                SET verification_status = 'approved',
                    license_verified = 'Y',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE vendor_id = $vendor_id";
        
        return $this->conn->query($sql);
    }

    /**
     * Reject vendor registration
     */
    public function rejectVendor($vendor_id, $reason, $admin_id) {
        $sql = "UPDATE vendors 
                SET verification_status = 'rejected',
                    is_active = 'N',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE vendor_id = $vendor_id";
        
        return $this->conn->query($sql);
    }

    // ============================================
    // CONSULTANTS MANAGEMENT
    // ============================================

    /**
     * Get all pending consultant registrations
     */
    public function getPendingConsultants() {
        $sql = "SELECT 
                    c.consultant_id,
                    c.user_id,
                    u.user_name,
                    u.email,
                    u.mb_number,
                    c.specialization,
                    c.degree,
                    c.bio,
                    c.license_no,
                    c.verification_status,
                    c.created_on
                FROM consultants c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.verification_status = 'pending'
                ORDER BY c.created_on ASC";
        
        $result = $this->conn->query($sql);
        $consultants = [];
        while ($row = $result->fetch_assoc()) {
            $consultants[] = $row;
        }
        return $consultants;
    }

    /**
     * Get all approved consultants
     */
    public function getApprovedConsultants() {
        $sql = "SELECT 
                    c.consultant_id,
                    c.user_id,
                    u.user_name,
                    u.email,
                    c.specialization,
                    c.degree,
                    c.bio,
                    c.verification_status,
                    c.created_on
                FROM consultants c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.verification_status = 'approved'
                AND c.is_active = 'Y'
                ORDER BY c.created_on DESC";
        
        $result = $this->conn->query($sql);
        $consultants = [];
        while ($row = $result->fetch_assoc()) {
            $consultants[] = $row;
        }
        return $consultants;
    }

    /**
     * Approve consultant registration
     */
    public function approveConsultant($consultant_id, $admin_id) {
        $sql = "UPDATE consultants 
                SET verification_status = 'approved',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE consultant_id = $consultant_id";
        
        return $this->conn->query($sql);
    }

    /**
     * Reject consultant registration
     */
    public function rejectConsultant($consultant_id, $reason, $admin_id) {
        $sql = "UPDATE consultants 
                SET verification_status = 'rejected',
                    is_active = 'N',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE consultant_id = $consultant_id";
        
        return $this->conn->query($sql);
    }

    // ============================================
    // FARMERS MANAGEMENT
    // ============================================

    /**
     * Get all pending farmer profiles
     */
    public function getPendingFarmers() {
        $sql = "SELECT 
                    f.farmer_id,
                    f.user_id,
                    u.user_name,
                    u.email,
                    u.mb_number,
                    f.farm_name,
                    f.location,
                    f.phone_number,
                    f.farm_size,
                    f.crops_grown,
                    f.verification_status,
                    f.created_on
                FROM farmers f
                JOIN users u ON f.user_id = u.user_id
                WHERE f.verification_status = 'pending'
                ORDER BY f.created_on ASC";

        $result = $this->conn->query($sql);
        $farmers = [];
        while ($row = $result->fetch_assoc()) {
            $farmers[] = $row;
        }
        return $farmers;
    }

    /**
     * Get all approved farmer profiles
     */
    public function getApprovedFarmers() {
        $sql = "SELECT 
                    f.farmer_id,
                    f.user_id,
                    u.user_name,
                    u.email,
                    f.farm_name,
                    f.location,
                    f.phone_number,
                    f.farm_size,
                    f.verification_status,
                    f.created_on
                FROM farmers f
                JOIN users u ON f.user_id = u.user_id
                WHERE f.verification_status = 'approved'
                ORDER BY f.created_on DESC";

        $result = $this->conn->query($sql);
        $farmers = [];
        while ($row = $result->fetch_assoc()) {
            $farmers[] = $row;
        }
        return $farmers;
    }

    /**
     * Approve farmer profile
     */
    public function approveFarmer($farmer_id, $admin_id) {
        $sql = "UPDATE farmers 
                SET verification_status = 'approved',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE farmer_id = $farmer_id";

        return $this->conn->query($sql);
    }

    /**
     * Reject farmer profile
     */
    public function rejectFarmer($farmer_id, $reason, $admin_id) {
        $sql = "UPDATE farmers 
                SET verification_status = 'rejected',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE farmer_id = $farmer_id";

        return $this->conn->query($sql);
    }

    // ============================================
    // PRODUCT MANAGEMENT & APPROVAL
    // ============================================

    /**
     * Get all pending product approvals
     */
    public function getPendingProducts() {
        $sql = "SELECT 
                    fpa.approval_id,
                    p.pro_id,
                    p.pro_name,
                    p.pro_image,
                    p.pro_description,
                    p.type,
                    fpa.farmer_id,
                    f.farm_name,
                    u.user_name as farmer_name,
                    u.email,
                    fpa.approval_status,
                    fpa.created_on
                FROM farmer_product_approval fpa
                JOIN products p ON fpa.pro_id = p.pro_id
                JOIN farmers f ON fpa.farmer_id = f.farmer_id
                JOIN users u ON f.user_id = u.user_id
                WHERE fpa.approval_status = 'pending' AND fpa.is_active = 'Y'
                ORDER BY fpa.created_on ASC";
        
        $result = $this->conn->query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }

    /**
     * Approve product
     */
    public function approveProduct($approval_id, $admin_id) {
        $sql = "UPDATE farmer_product_approval 
                SET approval_status = 'approved',
                    approved_by = $admin_id,
                    approval_date = NOW()
                WHERE approval_id = $approval_id";
        
        return $this->conn->query($sql);
    }

    /**
     * Reject product
     */
    public function rejectProduct($approval_id, $rejection_reason, $admin_id) {
        $reason = $this->conn->real_escape_string($rejection_reason);
        $sql = "UPDATE farmer_product_approval 
                SET approval_status = 'rejected',
                    rejection_reason = '$reason',
                    approved_by = $admin_id,
                    approval_date = NOW()
                WHERE approval_id = $approval_id";
        
        return $this->conn->query($sql);
    }

    /**
     * Get all approved products
     */
    public function getApprovedProducts() {
        $sql = "SELECT 
                    p.pro_id,
                    p.pro_name,
                    p.pro_image,
                    p.type,
                    f.farm_name,
                    u.user_name as farmer_name,
                    COUNT(pr.review_id) as total_ratings,
                    AVG(pr.rating) as avg_rating
                FROM products p
                JOIN farmer_product_approval fpa ON p.pro_id = fpa.pro_id AND fpa.approval_status = 'approved' AND fpa.is_active = 'Y'
                JOIN farmers f ON fpa.farmer_id = f.farmer_id
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN product_reviews pr ON pr.pro_id = p.pro_id AND pr.is_active = 'Y'
                WHERE p.product_source = 'farmer' AND p.is_active = 'Y'
                GROUP BY p.pro_id
                ORDER BY p.pro_name ASC";
        
        $result = $this->conn->query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }

    /**
     * Block/Unblock product
     */
    public function blockProduct($pro_id, $block_status) {
        $status = $block_status === 'Y' ? 'Y' : 'N';
        $sql = "UPDATE products 
                SET is_block = '$status'
                WHERE pro_id = $pro_id";
        
        return $this->conn->query($sql);
    }

    // ============================================
    // DISEASE REPORTS
    // ============================================

    /**
     * Get all disease reports
     */
    public function getDiseaseReports($filter_type = null) {
        $sql = "SELECT 
                    dr.report_id,
                    dr.report_type,
                    u.user_name,
                    u.email,
                    d.disease_name,
                    dr.crop_type,
                    dr.location,
                    dr.severity,
                    dr.description,
                    dr.created_on
                FROM disease_reports dr
                JOIN users u ON dr.user_id = u.user_id
                JOIN diseases d ON dr.disease_id = d.diseases_id
                WHERE dr.is_active = 'Y'";
        
        if ($filter_type) {
            $sql .= " AND dr.report_type = '$filter_type'";
        }
        
        $sql .= " ORDER BY dr.created_on DESC";
        
        $result = $this->conn->query($sql);
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        return $reports;
    }

    /**
     * Get disease report details
     */
    public function getDiseaseReportDetails($report_id) {
        $sql = "SELECT 
                    dr.*,
                    u.user_name,
                    u.email,
                    u.mb_number,
                    d.disease_name,
                    d.description,
                    d.symptoms,
                    d.prevention
                FROM disease_reports dr
                JOIN users u ON dr.user_id = u.user_id
                JOIN diseases d ON dr.disease_id = d.diseases_id
                WHERE dr.report_id = $report_id";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    // ============================================
    // FEEDBACK REPORTS
    // ============================================

    /**
     * Get all feedback reports
     */
    public function getFeedbackReports($feedback_type = null) {
        $sql = "SELECT 
                    fr.feedback_report_id,
                    fr.feedback_type,
                    u1.user_name as from_user,
                    u1.email as from_email,
                    u2.user_name as for_user,
                    u2.email as for_email,
                    fr.rating,
                    fr.comment,
                    fr.created_on
                FROM feedback_reports fr
                JOIN users u1 ON fr.user_id = u1.user_id
                LEFT JOIN users u2 ON fr.target_user_id = u2.user_id
                WHERE fr.is_active = 'Y'";
        
        if ($feedback_type) {
            $sql .= " AND fr.feedback_type = '$feedback_type'";
        }
        
        $sql .= " ORDER BY fr.created_on DESC";
        
        $result = $this->conn->query($sql);
        $feedbacks = [];
        while ($row = $result->fetch_assoc()) {
            $feedbacks[] = $row;
        }
        return $feedbacks;
    }

    /**
     * Get feedback statistics
     */
    public function getFeedbackStatistics() {
        $sql = "SELECT 
                    feedback_type,
                    COUNT(*) as total_feedback,
                    AVG(rating) as avg_rating,
                    ROUND((COUNT(*) * 100 / (SELECT COUNT(*) FROM feedback_reports WHERE is_active = 'Y')), 2) as percentage
                FROM feedback_reports
                WHERE is_active = 'Y'
                GROUP BY feedback_type";
        
        $result = $this->conn->query($sql);
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        return $stats;
    }

    // ============================================
    // USERS MANAGEMENT
    // ============================================

    /**
     * Get all users (including inactive)
     */
    public function getAllUsers() {
        $sql = "SELECT 
                    user_id,
                    user_name,
                    email,
                    role,
                    mb_number,
                    is_active,
                    created_on
                FROM users
                WHERE role != 'admin'
                ORDER BY role ASC, created_on DESC";
        
        $result = $this->conn->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * Get all users by role
     */
    public function getUsersByRole($role) {
        $sql = "SELECT 
                    user_id,
                    user_name,
                    email,
                    role,
                    mb_number,
                    is_active,
                    created_on
                FROM users
                WHERE role = '$role'
                AND is_active = 'Y'
                ORDER BY created_on DESC";
        
        $result = $this->conn->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * Get user details
     */
    public function getUserDetails($user_id) {
        $sql = "SELECT * FROM users WHERE user_id = $user_id AND is_active = 'Y'";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    /**
     * Deactivate user
     */
    public function deactivateUser($user_id, $admin_id) {
        $sql = "UPDATE users 
                SET is_active = 'N',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE user_id = $user_id";
        
        return $this->conn->query($sql);
    }

    /**
     * Activate user
     */
    public function activateUser($user_id, $admin_id) {
        $sql = "UPDATE users 
                SET is_active = 'Y',
                    modified_on = NOW(),
                    modified_by = $admin_id
                WHERE user_id = $user_id";
        
        return $this->conn->query($sql);
    }

    // ============================================
    // DASHBOARD STATISTICS
    // ============================================

    /**
     * Get dashboard summary
     */
    public function getDashboardSummary() {
        $summary = [];

        // Total users by role
        $sql = "SELECT role, COUNT(*) as count FROM users WHERE is_active = 'Y' GROUP BY role";
        $result = $this->conn->query($sql);
        $summary['users_by_role'] = [];
        while ($row = $result->fetch_assoc()) {
            $summary['users_by_role'][$row['role']] = $row['count'];
        }

        // Pending approvals
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM vendors WHERE verification_status = 'pending') as pending_vendors,
                    (SELECT COUNT(*) FROM consultants WHERE verification_status = 'pending') as pending_consultants,
                    (SELECT COUNT(*) FROM farmers WHERE verification_status = 'pending') as pending_farmers,
                    (SELECT COUNT(*) FROM product_approval WHERE approval_status = 'pending') as pending_products,
                    (SELECT COUNT(*) FROM video_tutorial WHERE approval_status = 'pending') as pending_videos";
        
        $result = $this->conn->query($sql);
        $summary['pending_approvals'] = $result->fetch_assoc();

        // Total disease reports
        $sql = "SELECT COUNT(*) as total FROM disease_reports WHERE is_active = 'Y'";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        $summary['total_disease_reports'] = $row['total'];

        // Total feedback
        $sql = "SELECT COUNT(*) as total FROM feedback_reports WHERE is_active = 'Y'";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        $summary['total_feedback'] = $row['total'];

        return $summary;
    }
}

?>
