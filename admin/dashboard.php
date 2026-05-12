<?php
session_start();
include_once('../_functions.php');
include_once('./admin_dao.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("location:../login");
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;

$adminDAO = new AdminDAO($conn);
$summary = $adminDAO->getDashboardSummary();

// Get recent activities
$conn->query("SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/chart.js/Chart.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .stat-box {
            border-radius: 8px;
            padding: 20px;
            color: white;
            font-weight: bold;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .stat-number {
            font-size: 28px;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        .bg-pending { background: linear-gradient(135deg, #ff9800, #ff6b6b); }
        .bg-vendors { background: linear-gradient(135deg, #667eea, #764ba2); }
        .bg-consultants { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .bg-products { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .bg-videos { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .bg-reports { background: linear-gradient(135deg, #fa709a, #fee140); }
        
        .quick-action {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .action-btn {
            flex: 1;
            min-width: 150px;
            padding: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: white;
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
                        <h1 class="m-0"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <h4 class="alert-heading">
                                <i class="fas fa-shield-alt"></i> Welcome, Admin!
                            </h4>
                            <p>
                                You have <strong><?php echo $summary['pending_approvals']['pending_vendors'] + $summary['pending_approvals']['pending_consultants'] + $summary['pending_approvals']['pending_farmers'] + $summary['pending_approvals']['pending_products'] + $summary['pending_approvals']['pending_videos']; ?></strong> 
                                pending approvals awaiting your action.
                            </p>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-bolt"></i> Quick Actions</h5>
                        <div class="quick-action">
                            <a href="admin/video_management.php" class="action-btn" style="background-color: #43e97b; color: white;">
                                <i class="fas fa-video"></i> Review Videos
                            </a>
                            <a href="admin/vendors_consultants.php?tab=vendors" class="action-btn" style="background-color: #667eea; color: white;">
                                <i class="fas fa-store"></i> Approve Vendors
                            </a>
                            <a href="admin/farmers.php" class="action-btn" style="background-color: #28a745; color: white;">
                                <i class="fas fa-tractor"></i> Approve Farmers
                            </a>
                            <a href="admin/vendors_consultants.php?tab=consultants" class="action-btn" style="background-color: #f5576c; color: white;">
                                <i class="fas fa-user-tie"></i> Approve Consultants
                            </a>
                            <a href="admin/product_approval.php" class="action-btn" style="background-color: #00f2fe; color: white;">
                                <i class="fas fa-box"></i> Approve Products
                            </a>
                            <a href="admin/reports.php" class="action-btn" style="background-color: #fee140; color: #333;">
                                <i class="fas fa-file-alt"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="admin/vendors_consultants.php?tab=vendors" class="stat-box bg-vendors" style="text-decoration: none; display: block;">
                            <i class="fas fa-store" style="font-size: 24px;"></i>
                            <div class="stat-number"><?php echo $summary['pending_approvals']['pending_vendors']; ?></div>
                            <div class="stat-label">Pending Vendors</div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="admin/vendors_consultants.php?tab=consultants" class="stat-box bg-consultants" style="text-decoration: none; display: block;">
                            <i class="fas fa-user-tie" style="font-size: 24px;"></i>
                            <div class="stat-number"><?php echo $summary['pending_approvals']['pending_consultants']; ?></div>
                            <div class="stat-label">Pending Consultants</div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="admin/farmers.php" class="stat-box" style="text-decoration: none; display: block; background: linear-gradient(135deg, #43a047, #2e7d32);">
                            <i class="fas fa-tractor" style="font-size: 24px;"></i>
                            <div class="stat-number"><?php echo $summary['pending_approvals']['pending_farmers']; ?></div>
                            <div class="stat-label">Pending Farmers</div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="admin/product_approval.php?tab=pending" class="stat-box bg-products" style="text-decoration: none; display: block;">
                            <i class="fas fa-box" style="font-size: 24px;"></i>
                            <div class="stat-number"><?php echo $summary['pending_approvals']['pending_products']; ?></div>
                            <div class="stat-label">Pending Products</div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="admin/video_management.php" class="stat-box bg-videos" style="text-decoration: none; display: block;">
                            <i class="fas fa-video" style="font-size: 24px;"></i>
                            <div class="stat-number"><?php echo $summary['pending_approvals']['pending_videos']; ?></div>
                            <div class="stat-label">Pending Videos</div>
                        </a>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title"><i class="fas fa-users"></i> User Statistics</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($summary['users_by_role'] as $role => $count): ?>
                                        <div class="col-md-3 text-center mb-3">
                                            <h4 style="color: #007bff;"><?php echo $count; ?></h4>
                                            <p class="text-muted text-capitalize"><?php echo ucfirst($role); ?>s</p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Cards -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h3 class="card-title"><i class="fas fa-virus"></i> Disease Reports</h3>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 style="color: #ff9800; margin: 20px 0;">
                                        <?php echo $summary['total_disease_reports']; ?>
                                    </h2>
                                    <p class="text-muted">Total Disease Reports Submitted</p>
                                    <a href="admin/reports.php?tab=disease" class="btn btn-warning btn-sm">
                                        <i class="fas fa-arrow-right"></i> View Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info">
                                <h3 class="card-title"><i class="fas fa-comments"></i> Feedback Reports</h3>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 style="color: #17a2b8; margin: 20px 0;">
                                        <?php echo $summary['total_feedback']; ?>
                                    </h2>
                                    <p class="text-muted">Total Feedback Received</p>
                                    <a href="admin/reports.php?tab=feedback" class="btn btn-info btn-sm">
                                        <i class="fas fa-arrow-right"></i> View Feedback
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/plugins/chart.js/Chart.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>

<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
