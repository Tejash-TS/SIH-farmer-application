<?php
session_start();
include_once('../_functions.php');
require_once('farmer_dao.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;
$farmerDAO = new FarmerDAO($conn);
$user_id = intval($_SESSION['user']['user_id']);

// Get farmer profile
$farmer_profile = $farmerDAO->getFarmerProfile($user_id);
$farmer_id = $farmer_profile['farmer_id'] ?? null;

if (!$farmer_id) {
    header('location:../login');
    exit;
}

$subscription_id = isset($_GET['subscription_id']) ? intval($_GET['subscription_id']) : 0;

// Get subscription details
$sql = "SELECT 
    fcs.*,
    cs.service_name,
    cs.max_consultations,
    cs.consultation_duration_mins,
    c.consultant_id,
    u.user_name,
    u.email,
    u.mb_number
FROM farmer_consultancy_subscriptions fcs
JOIN consultancy_services cs ON fcs.service_id = cs.service_id
JOIN consultants c ON fcs.consultant_id = c.consultant_id
JOIN users u ON c.user_id = u.user_id
WHERE fcs.subscription_id = ? AND fcs.farmer_id = ? AND fcs.subscription_status = 'active'";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $subscription_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$subscription = $result->fetch_assoc();
$stmt->close();

if (!$subscription) {
    header('location:farmer/my_consultancies');
    exit;
}

// Check if farmer has consultations remaining
if ($subscription['remaining_consultations'] <= 0) {
    $message = 'You have no consultations remaining. Please renew your subscription.';
    $message_type = 'error';
} else {
    $message = '';
    $message_type = '';
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $subscription['remaining_consultations'] > 0) {
    $session_date = trim($_POST['session_date'] ?? '');
    $session_time = trim($_POST['session_time'] ?? '');
    $meeting_mode = trim($_POST['meeting_mode'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$session_date || !$session_time || !$meeting_mode) {
        $message = 'Please fill all required fields.';
        $message_type = 'error';
    } else {
        // Combine date and time
        $session_datetime = $session_date . ' ' . $session_time . ':00';
        
        // Verify the session date is within subscription period
        if (strtotime($session_datetime) < strtotime($subscription['start_date']) || 
            strtotime($session_datetime) > strtotime($subscription['end_date'])) {
            $message = 'Session date must be within your subscription period.';
            $message_type = 'error';
        } else {
            // Check for existing sessions at the same time (for the consultant)
            $sql = "SELECT COUNT(*) as count FROM consultancy_sessions 
                    WHERE session_date = ? AND session_status != 'cancelled'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $session_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();

            if ($count > 0) {
                $message = 'This time slot is already booked. Please select another time.';
                $message_type = 'error';
            } else {
                // Create session
                $sql = "INSERT INTO consultancy_sessions 
                        (subscription_id, session_date, session_mode, session_notes, session_status, created_by)
                        VALUES (?, ?, ?, ?, 'scheduled', ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('isssi', $subscription_id, $session_datetime, $meeting_mode, $notes, $user_id);
                
                if ($stmt->execute()) {
                    $message = 'Session booked successfully! The consultant will confirm shortly.';
                    $message_type = 'success';
                    echo '<meta http-equiv="refresh" content="2; url=farmer/my_consultancies">';
                } else {
                    $message = 'Failed to book session. Please try again.';
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// Get existing sessions for this subscription
$sql = "SELECT * FROM consultancy_sessions 
        WHERE subscription_id = ? 
        ORDER BY session_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $subscription_id);
$stmt->execute();
$result = $stmt->get_result();
$sessions = [];
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
$stmt->close();

$min_date = date('Y-m-d');
$max_date = date('Y-m-d', strtotime($subscription['end_date']));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Consultation - Farmer</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <style>
        .session-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .session-card.completed {
            border-left-color: #28a745;
        }
        .session-card.cancelled {
            border-left-color: #dc3545;
            opacity: 0.7;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php include_once('_header.php'); ?>
    <?php include_once('_sidebar.php'); ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-calendar"></i> Book Consultation</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/my_consultancies">My Consultancies</a></li>
                            <li class="breadcrumb-item active">Book Session</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Message Alert -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Booking Form -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h5 class="m-0">Schedule Session</h5>
                            </div>
                            <div class="card-body">
                                <!-- Subscription Summary -->
                                <div class="alert alert-info mb-4">
                                    <h6><i class="fas fa-info-circle"></i> Subscription Details</h6>
                                    <p class="mb-0">
                                        <strong><?php echo htmlspecialchars($subscription['service_name']); ?></strong> 
                                        with <strong><?php echo htmlspecialchars($subscription['user_name']); ?></strong>
                                    </p>
                                    <p class="mb-0">
                                        <strong><?php echo $subscription['remaining_consultations']; ?></strong> consultation(s) remaining 
                                        (<?php echo $subscription['consultation_duration_mins']; ?> mins each)
                                    </p>
                                    <small>Valid until: <strong><?php echo date('d M Y', strtotime($subscription['end_date'])); ?></strong></small>
                                </div>

                                <?php if ($subscription['remaining_consultations'] > 0): ?>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Select Date *</label>
                                            <input type="date" name="session_date" class="form-control" 
                                                   min="<?php echo $min_date; ?>" 
                                                   max="<?php echo $max_date; ?>" required>
                                            <small class="text-muted">Valid dates: <?php echo date('d M Y', strtotime($subscription['start_date'])); ?> to <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Select Time *</label>
                                            <input type="time" name="session_time" class="form-control" required>
                                            <small class="text-muted">Recommended: 9:00 AM to 6:00 PM</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Meeting Mode *</label>
                                            <select name="meeting_mode" class="form-control" required>
                                                <option value="">-- Select Meeting Mode --</option>
                                                <option value="video_call">Video Call</option>
                                                <option value="phone_call">Phone Call</option>
                                                <option value="chat">Chat</option>
                                                <option value="in_person">In-Person</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Session Notes / Questions</label>
                                            <textarea name="notes" class="form-control" rows="4" 
                                                      placeholder="Any specific topics or questions you'd like to discuss?"></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Confirm Booking
                                        </button>
                                        <a href="farmer/my_consultancies" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Cancel
                                        </a>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> You have no consultations remaining for this subscription.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Session History -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-secondary">
                                <h5 class="m-0">Your Sessions</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($sessions)): ?>
                                    <div style="max-height: 400px; overflow-y: auto;">
                                        <?php foreach ($sessions as $session): ?>
                                            <div class="session-card <?php echo $session['session_status']; ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <p class="mb-1"><strong><?php echo date('d M Y, g:i A', strtotime($session['session_date'])); ?></strong></p>
                                                        <p class="mb-1 text-muted">
                                                            <i class="fas fa-phone"></i> 
                                                            <?php echo ucfirst(str_replace('_', ' ', $session['session_mode'])); ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <?php echo ucfirst($session['session_status']); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge badge-<?php 
                                                        if ($session['session_status'] === 'completed') echo 'success';
                                                        elseif ($session['session_status'] === 'cancelled') echo 'danger';
                                                        else echo 'primary';
                                                    ?>">
                                                        <?php echo substr(ucfirst($session['session_status']), 0, 3); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No sessions booked yet</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.min.js"></script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
