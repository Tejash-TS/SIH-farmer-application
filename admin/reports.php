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
$tab = $_GET['tab'] ?? 'disease';
$filter = $_GET['filter'] ?? '';

// Get reports based on tab
if ($tab === 'disease') {
    $reports = $adminDAO->getDiseaseReports($filter ? $filter : null);
    $report_type_label = $filter ? 'Filter: ' . ucfirst($filter) : 'All Reports';
} else {
    $reports = $adminDAO->getFeedbackReports($filter ? $filter : null);
    $statistics = $adminDAO->getFeedbackStatistics();
    $report_type_label = $filter ? 'Filter: ' . ucfirst($filter) : 'All Feedback';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/plugins/chart.js/Chart.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .report-card {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        .report-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .severity-mild { border-left-color: #28a745; }
        .severity-moderate { border-left-color: #ffc107; }
        .severity-severe { border-left-color: #dc3545; }
        
        .badge-severity {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        .severity-mild-badge { background-color: #d4edda; color: #155724; }
        .severity-moderate-badge { background-color: #fff3cd; color: #856404; }
        .severity-severe-badge { background-color: #f8d7da; color: #721c24; }
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
                        <h1 class="m-0"><i class="fas fa-file-alt"></i> Reports</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin">Home</a></li>
                            <li class="breadcrumb-item active">Reports</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'disease' ? 'active' : ''; ?>" href="admin/reports.php?tab=disease">
                            <i class="fas fa-virus"></i> Disease Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'feedback' ? 'active' : ''; ?>" href="admin/reports.php?tab=feedback">
                            <i class="fas fa-comments"></i> Feedback Reports
                        </a>
                    </li>
                </ul>

                <!-- Disease Reports Tab -->
                <?php if ($tab === 'disease'): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-primary">
                                    <h3 class="card-title">
                                        <i class="fas fa-virus"></i> <?php echo $report_type_label; ?> 
                                        <span class="badge badge-light ml-2"><?php echo count($reports); ?></span>
                                    </h3>
                                    <div class="card-tools">
                                        <form method="GET" class="form-inline" style="display: flex; gap: 10px;">
                                            <input type="hidden" name="tab" value="disease">
                                            <select name="filter" class="form-control form-control-sm" onchange="this.form.submit();">
                                                <option value="">All Report Types</option>
                                                <option value="farmer" <?php echo $filter === 'farmer' ? 'selected' : ''; ?>>Farmer Reports</option>
                                                <option value="crop" <?php echo $filter === 'crop' ? 'selected' : ''; ?>>Crop Reports</option>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($reports)): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No disease reports found.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($reports as $report): ?>
                                            <div class="report-card severity-<?php echo htmlspecialchars($report['severity']); ?>">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <h5 class="mb-1">
                                                            <strong><?php echo htmlspecialchars($report['disease_name']); ?></strong>
                                                            <span class="badge badge-severity severity-<?php echo htmlspecialchars($report['severity']); ?>-badge ml-2">
                                                                <?php echo strtoupper($report['severity']); ?>
                                                            </span>
                                                        </h5>
                                                        <p class="mb-2">
                                                            <strong>Reported by:</strong> <?php echo htmlspecialchars($report['user_name']); ?> 
                                                            (<?php echo htmlspecialchars($report['email']); ?>)
                                                        </p>
                                                        <p class="mb-2">
                                                            <strong>Crop:</strong> <?php echo htmlspecialchars($report['crop_type'] ?? 'N/A'); ?> | 
                                                            <strong>Location:</strong> <?php echo htmlspecialchars($report['location'] ?? 'N/A'); ?>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Description:</strong> <?php echo htmlspecialchars(substr($report['description'], 0, 150)); ?>...
                                                        </p>
                                                    </div>
                                                    <div class="col-md-4 text-right">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar-alt"></i> 
                                                            <?php echo date('d M Y H:i', strtotime($report['created_on'])); ?>
                                                        </small>
                                                        <br>
                                                        <span class="badge badge-info mt-2">
                                                            <?php echo ucfirst($report['report_type']); ?> Report
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Feedback Reports Tab -->
                <?php else: ?>
                    <!-- Feedback Statistics -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-success">
                                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> Feedback Statistics</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php if (isset($statistics)): ?>
                                            <?php foreach ($statistics as $stat): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="text-center">
                                                        <h5 class="mb-2">
                                                            <i class="fas fa-user"></i> <?php echo ucfirst($stat['feedback_type']); ?> Feedback
                                                        </h5>
                                                        <h3 class="text-primary mb-1"><?php echo $stat['total_feedback']; ?></h3>
                                                        <p class="text-muted mb-1">
                                                            Average Rating: <strong>★ <?php echo number_format($stat['avg_rating'], 1); ?></strong>
                                                        </p>
                                                        <small class="text-muted">
                                                            <?php echo number_format($stat['percentage'], 1); ?>% of total
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback List -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info">
                                    <h3 class="card-title">
                                        <i class="fas fa-comments"></i> <?php echo $report_type_label; ?>
                                        <span class="badge badge-light ml-2"><?php echo count($reports); ?></span>
                                    </h3>
                                    <div class="card-tools">
                                        <form method="GET" class="form-inline" style="display: flex; gap: 10px;">
                                            <input type="hidden" name="tab" value="feedback">
                                            <select name="filter" class="form-control form-control-sm" onchange="this.form.submit();">
                                                <option value="">All Feedback Types</option>
                                                <option value="farmer" <?php echo $filter === 'farmer' ? 'selected' : ''; ?>>Farmer Feedback</option>
                                                <option value="vendor" <?php echo $filter === 'vendor' ? 'selected' : ''; ?>>Vendor Feedback</option>
                                                <option value="consultant" <?php echo $filter === 'consultant' ? 'selected' : ''; ?>>Consultant Feedback</option>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($reports)): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No feedback found.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>From</th>
                                                        <th>For</th>
                                                        <th>Type</th>
                                                        <th>Rating</th>
                                                        <th>Comment</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reports as $feedback): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($feedback['from_user']); ?></strong><br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($feedback['from_email']); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if ($feedback['for_user']): ?>
                                                                    <strong><?php echo htmlspecialchars($feedback['for_user']); ?></strong><br>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($feedback['for_email']); ?></small>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-primary">
                                                                    <?php echo ucfirst($feedback['feedback_type']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($feedback['rating']): ?>
                                                                    <span class="badge badge-warning">
                                                                        ⭐ <?php echo $feedback['rating']; ?>/5
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars(substr($feedback['comment'], 0, 50)); ?>...</small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('d M Y', strtotime($feedback['created_on'])); ?>
                                                                </small>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
</body>
</html>
<?php include_once('../_chat_widget.php'); ?>