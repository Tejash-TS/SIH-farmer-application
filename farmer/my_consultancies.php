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

// Handle rating submission
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    $subscription_id = intval($_POST['subscription_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $message = 'Invalid rating. Please select 1-5 stars.';
        $message_type = 'error';
    } elseif (strlen($review) < 10) {
        $message = 'Review must be at least 10 characters long.';
        $message_type = 'error';
    } else {
        // Get subscription details
        $sql = "SELECT * FROM farmer_consultancy_subscriptions WHERE subscription_id = ? AND farmer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $subscription_id, $farmer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $subscription = $result->fetch_assoc();
        $stmt->close();

        if (!$subscription) {
            $message = 'Invalid subscription.';
            $message_type = 'error';
        } else {
            // Insert rating
            $sql = "INSERT INTO consultancy_ratings (subscription_id, consultant_id, farmer_id, rating, review, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiiiis', $subscription_id, $subscription['consultant_id'], $farmer_id, $rating, $review, $user_id);
            
            if ($stmt->execute()) {
                $message = 'Thank you for your review!';
                $message_type = 'success';
            } else {
                $message = 'Failed to submit review. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Get farmer's consultancy subscriptions
$sql = "SELECT 
    fcs.*,
    cs.service_name,
    cs.price_per_month,
    cs.max_consultations,
    c.consultant_id,
    c.specialization,
    c.profile_image,
    u.user_name,
    u.email,
    COUNT(DISTINCT se.session_id) as completed_sessions
FROM farmer_consultancy_subscriptions fcs
JOIN consultancy_services cs ON fcs.service_id = cs.service_id
JOIN consultants c ON fcs.consultant_id = c.consultant_id
JOIN users u ON c.user_id = u.user_id
LEFT JOIN consultancy_sessions se ON fcs.subscription_id = se.subscription_id AND se.session_status = 'completed'
WHERE fcs.farmer_id = ?
GROUP BY fcs.subscription_id
ORDER BY fcs.created_on DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$subscriptions = [];
while ($row = $result->fetch_assoc()) {
    $subscriptions[] = $row;
}
$stmt->close();

// Separate active and expired subscriptions
$active_subscriptions = array_filter($subscriptions, function($s) { 
    return $s['subscription_status'] === 'active'; 
});
$expired_subscriptions = array_filter($subscriptions, function($s) { 
    return $s['subscription_status'] !== 'active'; 
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Consultancies - Farmer</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
	
    <style>
        .subscription-card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #28a745;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .subscription-card.expired {
            border-left-color: #dc3545;
            opacity: 0.8;
        }
        .consultant-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .service-name {
            color: #007bff;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
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
                        <h1 class="m-0"><i class="fas fa-handshake"></i> My Consultancies</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">My Consultancies</li>
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

                <!-- Stats Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($active_subscriptions); ?></div>
                            <div class="stat-label">Active Subscriptions</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($expired_subscriptions); ?></div>
                            <div class="stat-label">Past Subscriptions</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value">
                                ₹<?php 
                                $total_paid = array_sum(array_map(function($s) { return $s['amount_paid'] ?? 0; }, $subscriptions));
                                echo number_format($total_paid, 2);
                                ?>
                            </div>
                            <div class="stat-label">Total Invested</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <a href="farmer/browse_consultants" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Browse More
                        </a>
                    </div>
                </div>

                <!-- Active Subscriptions -->
                <?php if (!empty($active_subscriptions)): ?>
                    <h3 class="mb-3"><i class="fas fa-check-circle"></i> Active Subscriptions</h3>
                    <?php foreach ($active_subscriptions as $sub): ?>
                        <div class="card subscription-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="consultant-name">
                                            <img src="<?php echo !empty($sub['profile_image']) ? htmlspecialchars($sub['profile_image']) : 'assets/dist/img/user-default.png'; ?>" 
                                                 width="40" height="40" class="rounded-circle mr-2" alt="">
                                            <?php echo htmlspecialchars($sub['user_name']); ?>
                                        </div>
                                        <div class="mb-2">
                                            <span class="service-name"><?php echo htmlspecialchars($sub['service_name']); ?></span>
                                            <span class="text-muted"> • <?php echo htmlspecialchars($sub['specialization']); ?></span>
                                        </div>
                                        <div class="row mt-3 mb-3">
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">Amount Paid</small>
                                                <div class="font-weight-bold">₹<?php echo number_format($sub['amount_paid'], 2); ?></div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">Consultations Remaining</small>
                                                <div class="font-weight-bold"><?php echo $sub['remaining_consultations']; ?>/<?php echo $sub['max_consultations']; ?></div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">Completed Sessions</small>
                                                <div class="font-weight-bold"><?php echo $sub['completed_sessions']; ?></div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">Expires On</small>
                                                <div class="font-weight-bold"><?php echo date('d M Y', strtotime($sub['end_date'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <div class="mb-3">
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        </div>
                                        <button class="btn btn-primary btn-sm mb-2" data-toggle="modal" data-target="#rateModal<?php echo $sub['subscription_id']; ?>">
                                            <i class="fas fa-star"></i> Leave Review
                                        </button>
                                        <a href="farmer/book_consultation?subscription_id=<?php echo $sub['subscription_id']; ?>" class="btn btn-success btn-sm d-block">
                                            <i class="fas fa-calendar"></i> Book Session
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Review Modal -->
                        <div class="modal fade" id="rateModal<?php echo $sub['subscription_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Rate <?php echo htmlspecialchars($sub['user_name']); ?></h5>
                                        <button type="button" class="btn-close" data-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="rate">
                                            <input type="hidden" name="subscription_id" value="<?php echo $sub['subscription_id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Rating *</label>
                                                <div class="star-rating" style="font-size: 2rem; letter-spacing: 0.5rem;">
                                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                                        <input type="radio" id="star<?php echo $sub['subscription_id']; ?>_<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                        <label for="star<?php echo $sub['subscription_id']; ?>_<?php echo $i; ?>" style="cursor: pointer; color: #ddd;">
                                                            <i class="fas fa-star"></i>
                                                        </label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Your Review *</label>
                                                <textarea name="review" class="form-control" rows="4" placeholder="Share your experience..." required minlength="10"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Submit Review</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Past Subscriptions -->
                <?php if (!empty($expired_subscriptions)): ?>
                    <h3 class="mb-3 mt-5"><i class="fas fa-history"></i> Past Subscriptions</h3>
                    <?php foreach ($expired_subscriptions as $sub): ?>
                        <div class="card subscription-card expired">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="consultant-name opacity-75">
                                            <img src="<?php echo !empty($sub['profile_image']) ? htmlspecialchars($sub['profile_image']) : 'assets/dist/img/user-default.png'; ?>" 
                                                 width="40" height="40" class="rounded-circle mr-2" alt="">
                                            <?php echo htmlspecialchars($sub['user_name']); ?>
                                        </div>
                                        <div class="mb-2 text-muted">
                                            <span class="service-name"><?php echo htmlspecialchars($sub['service_name']); ?></span>
                                            <span> • <?php echo htmlspecialchars($sub['specialization']); ?></span>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">Amount Paid</small>
                                                <div class="text-muted">₹<?php echo number_format($sub['amount_paid'], 2); ?></div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">Sessions Used</small>
                                                <div class="text-muted"><?php echo $sub['max_consultations'] - $sub['remaining_consultations']; ?>/<?php echo $sub['max_consultations']; ?></div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">Ended On</small>
                                                <div class="text-muted"><?php echo date('d M Y', strtotime($sub['end_date'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <span class="status-badge status-expired">
                                            <i class="fas fa-times-circle"></i> <?php echo ucfirst($sub['subscription_status']); ?>
                                        </span>
                                        <a href="farmer/browse_consultants" class="btn btn-primary btn-sm d-block mt-3">
                                            <i class="fas fa-plus"></i> Subscribe Again
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- No Subscriptions -->
                <?php if (empty($subscriptions)): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-info-circle" style="font-size: 2rem;"></i>
                        <h5 class="mt-3">No Active Consultancies</h5>
                        <p>You haven't hired any consultants yet. Browse and hire a consultant to get expert guidance.</p>
                        <a href="farmer/browse_consultants" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Consultants
                        </a>
                    </div>
                <?php endif; ?>
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

<style>
    .star-rating input { display: none; }
    .star-rating label { cursor: pointer; color: #ddd; }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label { color: #ffc107; }
    .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
</style>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
