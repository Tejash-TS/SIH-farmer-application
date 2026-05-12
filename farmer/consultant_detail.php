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

$consultant_id = isset($_GET['consultant_id']) ? intval($_GET['consultant_id']) : 0;

// Get consultant details
$sql = "SELECT 
    c.consultant_id,
    c.user_id,
    c.specialization,
    c.degree,
    c.bio,
    c.profile_image,
    c.license_no,
    u.user_name,
    u.email,
    u.mb_number,
    AVG(cr.rating) as avg_rating,
    COUNT(DISTINCT fcs.subscription_id) as total_clients
FROM consultants c
JOIN users u ON c.user_id = u.user_id
LEFT JOIN farmer_consultancy_subscriptions fcs ON c.consultant_id = fcs.consultant_id AND fcs.subscription_status = 'active'
LEFT JOIN consultancy_ratings cr ON c.consultant_id = cr.consultant_id AND cr.is_active = 'Y'
WHERE c.consultant_id = ? AND c.is_active = 'Y'
GROUP BY c.consultant_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$result = $stmt->get_result();
$consultant = $result->fetch_assoc();
$stmt->close();

if (!$consultant) {
    header('location:farmer/browse_consultants');
    exit;
}

// Get consultant's services
$sql = "SELECT * FROM consultancy_services 
        WHERE consultant_id = ? AND is_active = 'Y'
        ORDER BY price_per_month ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$result = $stmt->get_result();
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
$stmt->close();

// Get consultant reviews
$sql = "SELECT cr.*, u.user_name, f.farm_name
        FROM consultancy_ratings cr
        JOIN farmer_consultancy_subscriptions fcs ON cr.subscription_id = fcs.subscription_id
        JOIN farmers f ON fcs.farmer_id = f.farmer_id
        JOIN users u ON f.user_id = u.user_id
        WHERE cr.consultant_id = ? AND cr.is_active = 'Y'
        ORDER BY cr.created_on DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();

// Handle subscription
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? '');
    
    if (!$service_id || !$payment_method) {
        $message = 'Please select a service and payment method.';
        $message_type = 'error';
    } else {
        // Get service details
        $sql = "SELECT * FROM consultancy_services WHERE service_id = ? AND consultant_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $service_id, $consultant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $service = $result->fetch_assoc();
        $stmt->close();

        if (!$service) {
            $message = 'Invalid service selected.';
            $message_type = 'error';
        } else {
            // Create subscription
            $end_date = date('Y-m-d H:i:s', strtotime("+{$service['duration_months']} months"));
            $transaction_id = 'TXN-' . time() . '-' . $farmer_id;
            
            $sql = "INSERT INTO farmer_consultancy_subscriptions 
                    (farmer_id, service_id, consultant_id, start_date, end_date, 
                     amount_paid, remaining_consultations, payment_status, transaction_id, created_by)
                    VALUES (?, ?, ?, NOW(), ?, ?, ?, 'completed', ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiiidisi', 
                $farmer_id, $service_id, $consultant_id, $end_date,
                $service['price_per_month'], $service['max_consultations'],
                $transaction_id, $user_id
            );
            
            if ($stmt->execute()) {
                $message = 'Successfully subscribed to ' . htmlspecialchars($service['service_name']) . '! Check your subscriptions page.';
                $message_type = 'success';
                $stmt->close();
                // Redirect after 2 seconds
                echo '<meta http-equiv="refresh" content="2; url=farmer/my_consultancies">';
            } else {
                $message = 'Failed to create subscription. Please try again.';
                $message_type = 'error';
                $stmt->close();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($consultant['user_name']); ?> - Consultant</title>
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
        .consultant-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .consultant-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .service-card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .service-price {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .service-features {
            list-style: none;
            padding: 0;
        }
        .service-features li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .service-features li i {
            color: #28a745;
            margin-right: 10px;
        }
        .review-item {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 4px;
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
                        <h1 class="m-0"><i class="fas fa-user-tie"></i> Consultant Profile</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/browse_consultants">Consultants</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($consultant['user_name']); ?></li>
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

                <!-- Consultant Header -->
                <div class="consultant-header">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <img src="<?php echo !empty($consultant['profile_image']) ? htmlspecialchars($consultant['profile_image']) : 'assets/dist/img/user-default.png'; ?>" class="consultant-avatar" alt="<?php echo htmlspecialchars($consultant['user_name']); ?>">
                        </div>
                        <div class="col-md-9">
                            <h1><?php echo htmlspecialchars($consultant['user_name']); ?></h1>
                            <h4><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($consultant['specialization'] ?? 'General Consultant'); ?></h4>
                            <p class="mb-2"><i class="fas fa-certificate"></i> <?php echo htmlspecialchars($consultant['degree'] ?? 'Not specified'); ?></p>
                            
                            <!-- Rating -->
                            <div class="mt-3">
                                <?php 
                                $rating = round($consultant['avg_rating'] ?? 0);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star" style="color: #ffc107;"></i>';
                                    } else {
                                        echo '<i class="far fa-star" style="color: #ffc107;"></i>';
                                    }
                                }
                                echo ' <span style="margin-left: 10px;">(' . ($consultant['avg_rating'] ? number_format($consultant['avg_rating'], 1) : 'No ratings') . ') | ' . $consultant['total_clients'] . ' Active Clients</span>';
                                ?>
                            </div>

                            <p class="mt-2"><?php echo htmlspecialchars($consultant['bio'] ?? 'No bio available'); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($consultant['email'] ?? 'Not provided'); ?> | <?php echo htmlspecialchars($consultant['mb_number'] ?? 'Not provided'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Services Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2><i class="fas fa-briefcase"></i> Available Services</h2>
                        <?php if (!empty($services)): ?>
                            <div class="row">
                                <?php foreach ($services as $service): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card service-card">
                                            <div class="card-header bg-primary">
                                                <h5 class="m-0"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="service-price mb-3">
                                                    ₹<?php echo number_format($service['price_per_month'], 2); ?>
                                                    <small class="text-muted" style="font-size: 1rem;"> / <?php echo $service['duration_months']; ?> month(s)</small>
                                                </div>

                                                <p class="text-muted"><?php echo htmlspecialchars($service['description'] ?? 'No description'); ?></p>

                                                <ul class="service-features">
                                                    <li><i class="fas fa-check"></i> Up to <?php echo $service['max_consultations']; ?> consultations</li>
                                                    <li><i class="fas fa-clock"></i> <?php echo $service['consultation_duration_mins']; ?> mins per session</li>
                                                    <li><i class="fas fa-list"></i> <strong>Areas:</strong> <?php echo htmlspecialchars(substr($service['expertise_areas'], 0, 50)); ?></li>
                                                </ul>

                                                <form method="POST" class="mt-3">
                                                    <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                                                    <div class="mb-2">
                                                        <label class="form-label">Payment Method:</label>
                                                        <select name="payment_method" class="form-control form-control-sm" required>
                                                            <option value="">-- Select Payment Method --</option>
                                                            <option value="credit_card">Credit Card</option>
                                                            <option value="debit_card">Debit Card</option>
                                                            <option value="upi">UPI</option>
                                                            <option value="bank_transfer">Bank Transfer</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-success btn-block">
                                                        <i class="fas fa-shopping-cart"></i> Subscribe Now
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> This consultant hasn't set up any services yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="row">
                    <div class="col-12">
                        <h2><i class="fas fa-star"></i> Client Reviews</h2>
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="d-flex justify-content-between">
                                        <h6><?php echo htmlspecialchars($review['user_name']); ?> - <?php echo htmlspecialchars($review['farm_name']); ?></h6>
                                        <small class="text-muted"><?php echo date('d M Y', strtotime($review['created_on'])); ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review['rating']) {
                                                echo '<i class="fas fa-star" style="color: #ffc107;"></i>';
                                            } else {
                                                echo '<i class="far fa-star" style="color: #ffc107;"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <p><?php echo htmlspecialchars($review['review']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No reviews yet. Be the first to review this consultant!
                            </div>
                        <?php endif; ?>
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
