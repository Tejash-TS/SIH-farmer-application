<?php
include_once('../_functions.php');
global $conn;

/**
 * Get all consultants
 * @return array - Array of consultants
 */
function get_all_consultants()
{
    global $conn;
    
    $query = "SELECT 
        c.consultant_id,
        c.user_id,
        c.specialization,
        c.degree,
        c.license_no,
        c.verification_status,
        c.is_active,
        c.created_on,
        u.user_name,
        u.email,
        u.mb_number
    FROM consultants c
    JOIN users u ON c.user_id = u.user_id
    ORDER BY c.created_on DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $consultants = [];
    while ($row = $result->fetch_assoc()) {
        $consultants[] = $row;
    }
    
    $stmt->close();
    return $consultants;
}

/**
 * Get consultant by ID
 * @param int $consultant_id - Consultant ID
 * @return array - Consultant details
 */
function get_consultant($consultant_id)
{
    global $conn;
    
    $query = "SELECT 
        c.consultant_id,
        c.user_id,
        c.specialization,
        c.degree,
        c.bio,
        c.license_no,
        c.verification_status,
        c.is_active,
        c.created_on,
        u.user_name,
        u.email,
        u.mb_number
    FROM consultants c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.consultant_id = ?
    LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }
    
    $stmt->bind_param('i', $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $consultant = $result->fetch_assoc();
    $stmt->close();
    return $consultant;
}

/**
 * Update consultant verification status
 * @param int $consultant_id - Consultant ID
 * @param string $status - Status (approved/rejected/pending)
 * @param int $admin_id - Admin user ID
 * @return bool - Success status
 */
function update_consultant_status($consultant_id, $status, $admin_id)
{
    global $conn;
    
    $query = "UPDATE consultants 
    SET verification_status = ?, modified_on = NOW(), modified_by = ?
    WHERE consultant_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('sii', $status, $admin_id, $consultant_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Toggle consultant active status
 * @param int $consultant_id - Consultant ID
 * @param string $status - Y or N
 * @return bool - Success status
 */
function toggle_consultant_status($consultant_id, $status)
{
    global $conn;
    
    $query = "UPDATE consultants SET is_active = ? WHERE consultant_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('si', $status, $consultant_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Delete consultant
 * @param int $consultant_id - Consultant ID
 * @return bool - Success status
 */
function delete_consultant($consultant_id)
{
    global $conn;
    
    $query = "DELETE FROM consultants WHERE consultant_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('i', $consultant_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get pending consultant verifications
 * @return array - Pending consultants
 */
function get_pending_consultants()
{
    global $conn;
    
    $query = "SELECT 
        c.consultant_id,
        c.user_id,
        c.specialization,
        c.degree,
        c.license_no,
        c.verification_status,
        c.created_on,
        u.user_name,
        u.email
    FROM consultants c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.verification_status = 'pending'
    ORDER BY c.created_on ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $consultants = [];
    while ($row = $result->fetch_assoc()) {
        $consultants[] = $row;
    }
    
    $stmt->close();
    return $consultants;
}

/**
 * Get consultant statistics
 * @return array - Consultant statistics
 */
function get_consultant_stats()
{
    global $conn;
    
    $stats = [];
    
    // Total consultants
    $query = "SELECT COUNT(*) as total FROM consultants WHERE is_active = 'Y'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total'] = $row['total'] ?? 0;
        $stmt->close();
    }
    
    // Approved consultants
    $query = "SELECT COUNT(*) as approved FROM consultants WHERE verification_status = 'approved' AND is_active = 'Y'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['approved'] = $row['approved'] ?? 0;
        $stmt->close();
    }
    
    // Pending consultants
    $query = "SELECT COUNT(*) as pending FROM consultants WHERE verification_status = 'pending'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['pending'] = $row['pending'] ?? 0;
        $stmt->close();
    }
    
    // Rejected consultants
    $query = "SELECT COUNT(*) as rejected FROM consultants WHERE verification_status = 'rejected'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['rejected'] = $row['rejected'] ?? 0;
        $stmt->close();
    }
    
    return $stats;
}

?>
