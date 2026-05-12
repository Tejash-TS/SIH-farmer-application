<?php
include_once('../_functions.php');
global $conn;

/**
 * Get all pending videos awaiting approval
 * @return array - Pending videos
 */
function get_pending_videos()
{
    global $conn;

    $query = "SELECT 
        vt.video_tutorial_id,
        vt.title,
        vt.description,
        vt.video,
        vt.thumbnail,
        vt.approval_status,
        vt.created_on,
        u.user_name,
        u.email
    FROM video_tutorial vt
    JOIN users u ON vt.uploaded_by = u.user_id
    WHERE vt.approval_status = 'pending'
    ORDER BY vt.created_on DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

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
 * Get all videos
 * @return array - All videos
 */
function get_all_videos()
{
    global $conn;

    $query = "SELECT 
        vt.video_tutorial_id,
        vt.title,
        vt.description,
        vt.video,
        vt.thumbnail,
        vt.approval_status,
        vt.created_on,
        u.user_name,
        u.email
    FROM video_tutorial vt
    JOIN users u ON vt.uploaded_by = u.user_id
    ORDER BY vt.created_on DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

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
 * Get video statistics
 * @return array - Statistics
 */
function get_video_stats()
{
    global $conn;

    $stats = [];

    // Total videos
    $query = "SELECT COUNT(*) as total FROM video_tutorial";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_videos'] = $row['total'] ?? 0;
        $stmt->close();
    }

    // Approved videos
    $query = "SELECT COUNT(*) as total FROM video_tutorial WHERE approval_status = 'approved'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['approved_videos'] = $row['total'] ?? 0;
        $stmt->close();
    }

    // Pending videos
    $query = "SELECT COUNT(*) as total FROM video_tutorial WHERE approval_status = 'pending'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['pending_videos'] = $row['total'] ?? 0;
        $stmt->close();
    }

    // Rejected videos
    $query = "SELECT COUNT(*) as total FROM video_tutorial WHERE approval_status = 'rejected'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['rejected_videos'] = $row['total'] ?? 0;
        $stmt->close();
    }

    return $stats;
}

/**
 * Get video details
 * @param int $video_id - Video ID
 * @return array|null - Video details
 */
function get_video_details($video_id)
{
    global $conn;

    $query = "SELECT 
        vt.*,
        u.user_name,
        u.email
    FROM video_tutorial vt
    JOIN users u ON vt.uploaded_by = u.user_id
    WHERE vt.video_tutorial_id = ?
    LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();
    $stmt->close();

    return $video;
}

/**
 * Update video approval status
 * @param int $video_id - Video ID
 * @param string $status - Approval status (approved, rejected)
 * @param int $admin_id - Admin user ID
 * @return bool - Success status
 */
function update_video_status($video_id, $status, $admin_id)
{
    global $conn;

    // Only allow valid status values
    $allowed_statuses = ['approved', 'rejected', 'pending'];
    if (!in_array($status, $allowed_statuses)) {
        return false;
    }

    $query = "UPDATE video_tutorial 
    SET approval_status = ?, modified_on = NOW(), modified_by = ?
    WHERE video_tutorial_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sii', $status, $admin_id, $video_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Delete video
 * @param int $video_id - Video ID
 * @return bool - Success status
 */
function delete_video($video_id)
{
    global $conn;

    $query = "DELETE FROM video_tutorial WHERE video_tutorial_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $video_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Get video by ID for farmer viewing
 * @param int $video_id - Video ID
 * @return array|null - Video details
 */
function get_video_by_id($video_id)
{
    global $conn;

    $query = "SELECT 
        vt.*,
        u.user_name as consultant_name,
        u.email as consultant_email
    FROM video_tutorial vt
    JOIN users u ON vt.uploaded_by = u.user_id
    WHERE vt.video_tutorial_id = ? 
    AND vt.approval_status = 'approved'
    AND vt.is_active = 'Y'
    LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();
    $stmt->close();

    return $video;
}

/**
 * Get all approved videos for farmers
 * @return array - Approved videos
 */
function get_approved_videos()
{
    global $conn;

    $query = "SELECT 
        vt.video_tutorial_id,
        vt.title,
        vt.description,
        vt.video,
        vt.thumbnail,
        vt.created_on,
        u.user_name as consultant_name,
        u.email as consultant_email
    FROM video_tutorial vt
    JOIN users u ON vt.uploaded_by = u.user_id
    WHERE vt.approval_status = 'approved'
    AND vt.is_active = 'Y'
    ORDER BY vt.created_on DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

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
 * Search videos by title or description
 * @param string $keyword - Search keyword
 * @return array - Search results
 */
function search_videos($keyword)
{
    global $conn;

    $keyword = '%' . $keyword . '%';

    $query = "SELECT 
        vt.video_tutorial_id,
        vt.title,
        vt.description,
        vt.video,
        vt.thumbnail,
        vt.created_on,
        u.user_name as consultant_name
    FROM video_tutorial vt
    JOIN users u ON vt.uploaded_by = u.user_id
    WHERE vt.approval_status = 'approved'
    AND vt.is_active = 'Y'
    AND (vt.title LIKE ? OR vt.description LIKE ?)
    ORDER BY vt.created_on DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ss', $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();

    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }

    // FIX #1: Missing return statement — function was silently returning null
    $stmt->close();
    return $videos;
}
