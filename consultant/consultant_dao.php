<?php
include_once('../_functions.php');
global $conn;

/**
 * Get consultant profile
 * @param int $user_id - User ID
 * @return array - Consultant profile
 */
function get_consultant_profile($user_id)
{
    global $conn;
    
    $query = "SELECT 
        c.consultant_id,
        c.user_id,
        c.specialization,
        c.degree,
        c.bio,
        c.license_no,
        c.profile_image,
        c.verification_status,
        c.is_active,
        c.created_on,
        u.user_name,
        u.email,
        u.mb_number
    FROM consultants c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.user_id = ?
    LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    
    return $profile;
}

/**
 * Update consultant profile
 * @param int $consultant_id - Consultant ID
 * @param array $data - Profile data
 * @return bool - Success status
 */
function update_consultant_profile($consultant_id, $data)
{
    global $conn;
    
    // Check if profile_image is being updated
    if (isset($data['profile_image']) && !empty($data['profile_image'])) {
        $query = "UPDATE consultants 
        SET specialization = ?, degree = ?, bio = ?, license_no = ?, profile_image = ?, modified_on = NOW()
        WHERE consultant_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param(
            'sssssi',
            $data['specialization'],
            $data['degree'],
            $data['bio'],
            $data['license_no'],
            $data['profile_image'],
            $consultant_id
        );
    } else {
        $query = "UPDATE consultants 
        SET specialization = ?, degree = ?, bio = ?, license_no = ?, modified_on = NOW()
        WHERE consultant_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param(
            'ssssi',
            $data['specialization'],
            $data['degree'],
            $data['bio'],
            $data['license_no'],
            $consultant_id
        );
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Create consultant profile
 * @param int $user_id - User ID
 * @param array $data - Profile data
 * @return bool|int - Consultant ID or false
 */
function create_consultant_profile($user_id, $data)
{
    global $conn;
    
    $profile_image = isset($data['profile_image']) ? $data['profile_image'] : null;
    
    $query = "INSERT INTO consultants (user_id, specialization, degree, bio, license_no, profile_image, created_on, created_by)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param(
        'isssssi',
        $user_id,
        $data['specialization'],
        $data['degree'],
        $data['bio'],
        $data['license_no'],
        $profile_image,
        $user_id
    );
    
    if ($stmt->execute()) {
        $consultant_id = $conn->insert_id;
        $stmt->close();
        return $consultant_id;
    }
    
    $stmt->close();
    return false;
}

/**
 * Get consultant's uploaded videos
 * @param int $consultant_id - Consultant ID
 * @return array - Videos list
 */
function get_consultant_videos($consultant_id)
{
    global $conn;
    
    $query = "SELECT 
        vt.video_tutorial_id,
        vt.title,
        vt.description,
        vt.video,
        vt.thumbnail,
        vt.uploaded_by,
        vt.approval_status,
        vt.created_on
    FROM video_tutorial vt
    WHERE vt.uploaded_by = ?
    ORDER BY vt.created_on DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('i', $consultant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
    
    $stmt->close();
    return $videos;
}

/**
 * Get consultant statistics
 * @param int $consultant_id - Consultant ID
 * @return array - Statistics
 */
function get_consultant_stats($consultant_id)
{
    global $conn;
    
    $stats = [];
    
    // Total videos uploaded
    $query = "SELECT COUNT(*) as total_videos FROM video_tutorial WHERE uploaded_by = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $consultant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_videos'] = $row['total_videos'] ?? 0;
        $stmt->close();
    }
    
    // Approved videos
    $query = "SELECT COUNT(*) as approved_videos FROM video_tutorial WHERE uploaded_by = ? AND approval_status = 'approved'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $consultant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['approved_videos'] = $row['approved_videos'] ?? 0;
        $stmt->close();
    }
    
    // Pending videos
    $query = "SELECT COUNT(*) as pending_videos FROM video_tutorial WHERE uploaded_by = ? AND approval_status = 'pending'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $consultant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['pending_videos'] = $row['pending_videos'] ?? 0;
        $stmt->close();
    }
    
    return $stats;
}

?>
