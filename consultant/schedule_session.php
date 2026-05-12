<?php
session_start();
include_once('../_functions.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;
$user_id = intval($_SESSION['user']['user_id']);

// Get consultant profile
$sql = "SELECT * FROM consultants WHERE user_id = ? AND is_active = 'Y'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$consultant = $result->fetch_assoc();
$stmt->close();

if (!$consultant) {
    die('Consultant profile not found.');
}

$consultant_id = $consultant['consultant_id'];
$subscription_id = isset($_GET['subscription_id']) ? intval($_GET['subscription_id']) : 0;

// Get subscription and farmer details
$sql = "SELECT 
    fcs.*,
    cs.service_name,
    cs.max_consultations,
    cs.consultation_duration_mins,
    f.farm_name,
    f.address,
    u.user_name,
    u.email,
    u.mb_number
FROM farmer_consultancy_subscriptions fcs
JOIN consultancy_services cs ON fcs.service_id = cs.service_id
JOIN farmers f ON fcs.farmer_id = f.farmer_id
JOIN users u ON f.user_id = u.user_id
WHERE fcs.subscription_id = ? AND fcs.consultant_id = ? AND fcs.subscription_status = 'active'";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $subscription_id, $consultant_id);
$stmt->execute();
$result = $stmt->get_result();
$subscription = $result->fetch_assoc();
$stmt->close();

if (!$subscription) {
    header('location:consultant/my_clients');
    exit;
}

$message = '';
$message_type = '';

// Handle session confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm') {
    $session_id = intval($_POST['session_id'] ?? 0);
    $meeting_link = trim($_POST['meeting_link'] ?? '');
    $meeting_notes = trim($_POST['meeting_notes'] ?? '');

    // Get session details
    $sql = "SELECT * FROM consultancy_sessions WHERE session_id = ? AND subscription_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $session_id, $subscription_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();
    $stmt->close();

    if (!$session) {
        $message = 'Invalid session.';
        $message_type = 'error';
    } else {
        // Update session
        $sql = "UPDATE consultancy_sessions 
                SET session_status = 'confirmed', meeting_link = ?, meeting_notes = ?, updated_by = ?
                WHERE session_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssii', $meeting_link, $meeting_notes, $user_id, $session_id);
        
        if ($stmt->execute()) {
            $message = 'Session confirmed successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to confirm session.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Get pending sessions for this subscription
$sql = "SELECT * FROM consultancy_sessions 
        WHERE subscription_id = ? 
        ORDER BY session_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $subscription_id);
$stmt->execute();
$result = $stmt->get_result();
$sessions = [];
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
$stmt->close();

$pending_sessions = array_filter($sessions, function($s) { return $s['session_status'] === 'scheduled'; });
$confirmed_sessions = array_filter($sessions, function($s) { return $s['session_status'] === 'confirmed'; });

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Schedule Session - Consultant</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .farmer-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .session-item {
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .session-item.confirmed {
            border-left-color: #28a745;
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
                        <h1 class="m-0"><i class="fas fa-calendar-check"></i> Schedule Session</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="consultant/my_clients">My Clients</a></li>
                            <li class="breadcrumb-item active">Schedule</li>
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

                <!-- Farmer Info Card -->
                <div class="farmer-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($subscription['user_name']); ?></h3>
                            <p class="mb-1"><i class="fas fa-barn"></i> <?php echo htmlspecialchars($subscription['farm_name']); ?></p>
                            <p class="mb-0"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($subscription['address'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="col-md-4 text-right">
                            <p class="mb-1"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($subscription['mb_number']); ?></p>
                            <p class="mb-0"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($subscription['email']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Subscription & Sessions Info -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary">
                                <h5 class="m-0">Subscription Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Service:</strong> <?php echo htmlspecialchars($subscription['service_name']); ?></p>
                                        <p><strong>Total Consultations:</strong> <?php echo $subscription['max_consultations']; ?></p>
                                        <p><strong>Duration Each:</strong> <?php echo $subscription['consultation_duration_mins']; ?> minutes</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Amount Paid:</strong> ₹<?php echo number_format($subscription['amount_paid'], 2); ?></p>
                                        <p><strong>Remaining Sessions:</strong> <span class="badge badge-info"><?php echo $subscription['remaining_consultations']; ?></span></p>
                                        <p><strong>Valid Until:</strong> <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Sessions -->
                        <?php if (!empty($pending_sessions)): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-warning">
                                    <h5 class="m-0"><i class="fas fa-hourglass-half"></i> Pending Session Requests (<?php echo count($pending_sessions); ?>)</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($pending_sessions as $session): ?>
                                        <div class="session-item">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong><?php echo date('d M Y, g:i A', strtotime($session['session_date'])); ?></strong></p>
                                                    <p class="mb-1 text-muted">
                                                        <i class="fas fa-video"></i> 
                                                        <?php echo ucfirst(str_replace('_', ' ', $session['session_mode'])); ?>
                                                    </p>
                                                    <?php if ($session['session_notes']): ?>
                                                        <p class="mb-0 text-muted"><small><?php echo htmlspecialchars(substr($session['session_notes'], 0, 50)); ?></small></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6 text-right">
                                                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#confirmModal<?php echo $session['session_id']; ?>">
                                                        <i class="fas fa-check"></i> Confirm
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Confirm Modal -->
                                        <div class="modal fade" id="confirmModal<?php echo $session['session_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Session</h5>
                                                        <button type="button" class="btn-close" data-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="confirm">
                                                            <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                            
                                                            <p class="mb-3"><strong>Session Date & Time:</strong> <?php echo date('d M Y, g:i A', strtotime($session['session_date'])); ?></p>

                                                            <div class="mb-3">
                                                                <label class="form-label">Meeting Link/Details *</label>
                                                                <input type="text" name="meeting_link" class="form-control" 
                                                                       placeholder="e.g., https://zoom.us/j/... or +91-XXXXXXXXXX" required>
                                                                <small class="text-muted">Video call URL, phone number, or meeting details</small>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Session Notes</label>
                                                                <textarea name="meeting_notes" class="form-control" rows="3" 
                                                                          placeholder="Agenda, topics to cover, or instructions for the farmer..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-check"></i> Confirm & Send
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Confirmed Sessions -->
                        <?php if (!empty($confirmed_sessions)): ?>
                            <div class="card">
                                <div class="card-header bg-success">
                                    <h5 class="m-0"><i class="fas fa-check-double"></i> Confirmed Sessions (<?php echo count($confirmed_sessions); ?>)</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($confirmed_sessions as $session): ?>
                                        <div class="session-item confirmed">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <p class="mb-1"><strong><?php echo date('d M Y, g:i A', strtotime($session['session_date'])); ?></strong></p>
                                                    <p class="mb-1">
                                                        <i class="fas fa-link"></i> 
                                                        <code><?php echo htmlspecialchars($session['meeting_link']); ?></code>
                                                    </p>
                                                    <?php if ($session['meeting_notes']): ?>
                                                        <p class="mb-0 text-muted"><small><?php echo htmlspecialchars($session['meeting_notes']); ?></small></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4 text-right">
                                                    <span class="badge badge-success">Confirmed</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($sessions)): ?>
                            <div class="alert alert-info text-center py-5">
                                <i class="fas fa-calendar-times" style="font-size: 2rem;"></i>
                                <h5 class="mt-3">No Sessions Yet</h5>
                                <p>The farmer hasn't requested any sessions yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Info Sidebar -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-secondary">
                                <h5 class="m-0">Quick Info</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">ACTIVE SUBSCRIPTIONS</small>
                                    <h4 class="m-0">
                                        <?php 
                                        $sql = "SELECT COUNT(*) as count FROM farmer_consultancy_subscriptions 
                                                WHERE consultant_id = ? AND subscription_status = 'active'";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param('i', $consultant_id);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $count = $result->fetch_assoc()['count'];
                                        $stmt->close();
                                        echo $count;
                                        ?>
                                    </h4>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">SESSIONS THIS MONTH</small>
                                    <h4 class="m-0">
                                        <?php 
                                        $sql = "SELECT COUNT(*) as count FROM consultancy_sessions 
                                                WHERE subscription_id IN (
                                                    SELECT subscription_id FROM farmer_consultancy_subscriptions 
                                                    WHERE consultant_id = ?
                                                ) AND MONTH(session_date) = MONTH(NOW())";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param('i', $consultant_id);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $count = $result->fetch_assoc()['count'];
                                        $stmt->close();
                                        echo $count;
                                        ?>
                                    </h4>
                                </div>
                                <hr>
                                <a href="consultant/my_clients" class="btn btn-primary btn-block">
                                    <i class="fas fa-arrow-left"></i> Back to Clients
                                </a>
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
