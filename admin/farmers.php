<?php
session_start();
include_once('../_functions.php');
include_once('./admin_dao.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;

$adminDAO = new AdminDAO($conn);
$tab = $_GET['tab'] ?? 'pending';
$status_message = $_GET['status'] ?? '';
$admin_id = $_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $farmer_id = intval($_POST['farmer_id'] ?? 0);

    if ($action === 'approve' && $farmer_id) {
        $adminDAO->approveFarmer($farmer_id, $admin_id);
        header('location:admin/farmers.php?tab=pending&status=approved');
        exit;
    } elseif ($action === 'reject' && $farmer_id) {
        $reason = $_POST['reason'] ?? '';
        $adminDAO->rejectFarmer($farmer_id, $reason, $admin_id);
        header('location:admin/farmers.php?tab=pending&status=rejected');
        exit;
    }
}

$pending_farmers = $adminDAO->getPendingFarmers();
$approved_farmers = $adminDAO->getApprovedFarmers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farmers Approval - Admin</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .nav-tabs .nav-link.active { border-bottom: 3px solid #28a745; }
        .farmer-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .farmer-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
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
                        <h1 class="m-0"><i class="fas fa-tractor"></i> Farmers Approval</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin">Home</a></li>
                            <li class="breadcrumb-item active">Farmers Approval</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($status_message === 'approved'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Farmer profile approved successfully!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php elseif ($status_message === 'rejected'): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-times-circle"></i> Farmer profile rejected.
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'pending' ? 'active' : ''; ?>" href="admin/farmers.php?tab=pending">
                            <i class="fas fa-hourglass-half"></i> Pending Approvals
                            <span class="badge badge-warning ml-2"><?php echo count($pending_farmers); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'approved' ? 'active' : ''; ?>" href="admin/farmers.php?tab=approved">
                            <i class="fas fa-check-circle"></i> Approved Farmers
                        </a>
                    </li>
                </ul>

                <?php if ($tab === 'pending'): ?>
                    <div class="row">
                        <?php if (empty($pending_farmers)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No pending farmer profiles.
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_farmers as $farmer): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card farmer-card shadow-sm h-100">
                                        <div class="card-body">
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($farmer['farm_name'] ?: $farmer['user_name']); ?></h5>
                                            <p class="text-muted small mb-2">
                                                <strong>Owner:</strong> <?php echo htmlspecialchars($farmer['user_name']); ?><br>
                                                <strong>Email:</strong> <?php echo htmlspecialchars($farmer['email']); ?><br>
                                                <strong>Phone:</strong> <?php echo htmlspecialchars($farmer['phone_number'] ?: 'N/A'); ?><br>
                                                <strong>Location:</strong> <?php echo htmlspecialchars($farmer['location'] ?: 'N/A'); ?><br>
                                                <strong>Farm Size:</strong> <?php echo htmlspecialchars($farmer['farm_size'] ?: 'N/A'); ?>
                                            </p>
                                            <p class="small mb-3">
                                                <strong>Crops:</strong> <?php echo htmlspecialchars($farmer['crops_grown'] ?: 'N/A'); ?>
                                            </p>
                                            <div class="d-flex gap-2">
                                                <form method="POST" style="flex: 1;">
                                                    <input type="hidden" name="farmer_id" value="<?php echo $farmer['farmer_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="openRejectModal(<?php echo $farmer['farmer_id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title"><i class="fas fa-check-circle"></i> Approved Farmers</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="farmersTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Farm Name</th>
                                            <th>Owner</th>
                                            <th>Email</th>
                                            <th>Location</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_farmers as $farmer): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($farmer['farm_name'] ?: 'Unnamed Farm'); ?></strong></td>
                                                <td><?php echo htmlspecialchars($farmer['user_name']); ?></td>
                                                <td><?php echo htmlspecialchars($farmer['email']); ?></td>
                                                <td><?php echo htmlspecialchars($farmer['location'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($farmer['phone_number'] ?: '-'); ?></td>
                                                <td><span class="badge badge-success">Approved</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div id="rejectModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4);">
        <div style="background-color:#fefefe; margin:10% auto; padding:20px; border:1px solid #888; width:80%; max-width:500px; border-radius:8px;">
            <span class="close" onclick="closeRejectModal()" style="cursor:pointer; float:right; font-size:28px;">&times;</span>
            <h4><i class="fas fa-times-circle"></i> Reject Farmer Profile</h4>
            <form method="POST">
                <input type="hidden" id="reject_farmer_id" name="farmer_id">
                <input type="hidden" name="action" value="reject">

                <div class="form-group">
                    <label>Rejection Reason (optional):</label>
                    <textarea class="form-control" name="reason" rows="4" placeholder="Enter reason for rejection..."></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Farmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script>
$(function() {
    $('#farmersTable').DataTable({
        paging: true,
        lengthChange: false,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        pageLength: 25
    });
});

function openRejectModal(farmerId) {
    document.getElementById('reject_farmer_id').value = farmerId;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};
</script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
