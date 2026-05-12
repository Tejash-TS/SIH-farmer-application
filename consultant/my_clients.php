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

// Get consultant's active subscriptions
$sql = "SELECT 
    fcs.*,
    cs.service_name,
    cs.price_per_month,
    cs.max_consultations,
    f.farm_name,
    u.user_name,
    u.email,
    u.mb_number,
    COUNT(DISTINCT se.session_id) as total_sessions
FROM farmer_consultancy_subscriptions fcs
JOIN consultancy_services cs ON fcs.service_id = cs.service_id
JOIN farmers f ON fcs.farmer_id = f.farmer_id
JOIN users u ON f.user_id = u.user_id
LEFT JOIN consultancy_sessions se ON fcs.subscription_id = se.subscription_id AND se.session_status = 'completed'
WHERE fcs.consultant_id = ? AND fcs.subscription_status = 'active'
GROUP BY fcs.subscription_id
ORDER BY fcs.created_on DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$result = $stmt->get_result();
$subscriptions = [];
while ($row = $result->fetch_assoc()) {
    $subscriptions[] = $row;
}
$stmt->close();

// Get statistics
$total_active = count($subscriptions);
$total_revenue = 0;
$total_sessions = 0;
foreach ($subscriptions as $sub) {
    $total_revenue += $sub['amount_paid'];
    $total_sessions += $sub['total_sessions'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Clients - Consultant</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .stat-card {
            border-radius: 8px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
        .client-card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .client-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
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
                        <h1 class="m-0"><i class="fas fa-users"></i> My Clients</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="consultant/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Clients</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_active; ?></div>
                            <div class="stat-label">Active Clients</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_sessions; ?></div>
                            <div class="stat-label">Sessions Completed</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php 
                                if ($total_active > 0) {
                                    echo number_format($total_revenue / $total_active, 2);
                                } else {
                                    echo '0.00';
                                }
                                ?>
                            </div>
                            <div class="stat-label">Avg Revenue/Client</div>
                        </div>
                    </div>
                </div>

                <!-- Clients List -->
                <div class="row">
                    <div class="col-12">
                        <?php if (!empty($subscriptions)): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="m-0">Active Client Subscriptions</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Farmer</th>
                                                    <th>Service</th>
                                                    <th>Amount Paid</th>
                                                    <th>Sessions Used</th>
                                                    <th>Remaining</th>
                                                    <th>Expires</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($subscriptions as $sub): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($sub['user_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($sub['farm_name']); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($sub['service_name']); ?></td>
                                                        <td><strong>₹<?php echo number_format($sub['amount_paid'], 2); ?></strong></td>
                                                        <td>
                                                            <?php echo $sub['max_consultations'] - $sub['remaining_consultations']; ?>/<?php echo $sub['max_consultations']; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-info"><?php echo $sub['remaining_consultations']; ?></span>
                                                        </td>
                                                        <td><?php echo date('d M Y', strtotime($sub['end_date'])); ?></td>
                                                        <td>
                                                            <a href="consultant/schedule_session?subscription_id=<?php echo $sub['subscription_id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-calendar"></i> Schedule
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center py-5">
                                <i class="fas fa-info-circle" style="font-size: 2rem;"></i>
                                <h5 class="mt-3">No Active Clients</h5>
                                <p>You don't have any active subscriptions yet. Create services to attract clients!</p>
                                <a href="consultant/manage_services" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Services
                                </a>
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
