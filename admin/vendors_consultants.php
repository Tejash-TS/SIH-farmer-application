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
$tab = $_GET['tab'] ?? 'vendors'; // vendors or consultants

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $admin_id = $_SESSION['user']['user_id'];

    if ($action === 'approve' && $id && $type) {
        if ($type === 'vendor') {
            $adminDAO->approveVendor($id, $admin_id);
        } elseif ($type === 'consultant') {
            $adminDAO->approveConsultant($id, $admin_id);
        }
        header("location:admin/vendors_consultants.php?tab=$type&status=approved");
        exit;
    } elseif ($action === 'reject' && $id && $type) {
        $reason = $_POST['reason'] ?? '';
        if ($type === 'vendor') {
            $adminDAO->rejectVendor($id, $reason, $admin_id);
        } elseif ($type === 'consultant') {
            $adminDAO->rejectConsultant($id, $reason, $admin_id);
        }
        header("location:admin/vendors_consultants.php?tab=$type&status=rejected");
        exit;
    }
}

$pending_vendors = $adminDAO->getPendingVendors();
$pending_consultants = $adminDAO->getPendingConsultants();
$approved_vendors = $adminDAO->getApprovedVendors();
$approved_consultants = $adminDAO->getApprovedConsultants();
$status_message = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendors & Consultants - Admin</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .status-pending { color: #ff9800; font-weight: bold; }
        .status-approved { color: #4caf50; font-weight: bold; }
        .status-rejected { color: #f44336; font-weight: bold; }
        .nav-tabs .nav-link.active { border-bottom: 3px solid #007bff; }
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
                        <h1 class="m-0"><i class="fas fa-users-cog"></i> Vendors & Consultants</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin">Home</a></li>
                            <li class="breadcrumb-item active">Vendors & Consultants</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($status_message === 'approved'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Registration approved successfully!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php elseif ($status_message === 'rejected'): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-times-circle"></i> Registration rejected!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'vendors' ? 'active' : ''; ?>" 
                           href="admin/vendors_consultants.php?tab=vendors">
                            <i class="fas fa-store"></i> Vendors
                            <span class="badge badge-warning ml-2"><?php echo count($pending_vendors); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'consultants' ? 'active' : ''; ?>" 
                           href="admin/vendors_consultants.php?tab=consultants">
                            <i class="fas fa-user-tie"></i> Consultants
                            <span class="badge badge-warning ml-2"><?php echo count($pending_consultants); ?></span>
                        </a>
                    </li>
                </ul>

                <!-- Vendors Tab -->
                <?php if ($tab === 'vendors'): ?>
                    <div class="row">
                        <!-- Pending Vendors -->
                        <div class="col-lg-6">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-hourglass-half"></i> Pending Vendor Registrations
                                    </h3>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <?php if (empty($pending_vendors)): ?>
                                        <p class="text-muted"><i class="fas fa-check-circle"></i> No pending vendors!</p>
                                    <?php else: ?>
                                        <?php foreach ($pending_vendors as $vendor): ?>
                                            <div class="mb-3 p-3" style="border: 1px solid #ddd; border-radius: 5px;">
                                                <h5><?php echo htmlspecialchars($vendor['company_name']); ?></h5>
                                                <p class="mb-2">
                                                    <strong>Contact:</strong> <?php echo htmlspecialchars($vendor['user_name']); ?><br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($vendor['email']); ?><br>
                                                    <strong>License:</strong> <?php echo htmlspecialchars($vendor['license_no']); ?><br>
                                                    <strong>Location:</strong> <?php echo htmlspecialchars($vendor['location']); ?>
                                                </p>
                                                <div class="btn-group d-flex gap-2">
                                                    <form method="POST" style="flex: 1;">
                                                        <input type="hidden" name="id" value="<?php echo $vendor['vendor_id']; ?>">
                                                        <input type="hidden" name="type" value="vendor">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="openRejectModal(<?php echo $vendor['vendor_id']; ?>, 'vendor')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Vendors -->
                        <div class="col-lg-6">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-check-circle"></i> Approved Vendors
                                    </h3>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <?php if (empty($approved_vendors)): ?>
                                        <p class="text-muted"><i class="fas fa-info-circle"></i> No approved vendors yet.</p>
                                    <?php else: ?>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Company</th>
                                                    <th>Contact</th>
                                                    <th>License</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($approved_vendors as $vendor): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(substr($vendor['company_name'], 0, 20)); ?></td>
                                                        <td><?php echo htmlspecialchars($vendor['user_name']); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($vendor['license_no'], 0, 15)); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Consultants Tab -->
                <?php else: ?>
                    <div class="row">
                        <!-- Pending Consultants -->
                        <div class="col-lg-6">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-hourglass-half"></i> Pending Consultant Registrations
                                    </h3>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <?php if (empty($pending_consultants)): ?>
                                        <p class="text-muted"><i class="fas fa-check-circle"></i> No pending consultants!</p>
                                    <?php else: ?>
                                        <?php foreach ($pending_consultants as $consultant): ?>
                                            <div class="mb-3 p-3" style="border: 1px solid #ddd; border-radius: 5px;">
                                                <h5><?php echo htmlspecialchars($consultant['user_name']); ?></h5>
                                                <p class="mb-2">
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($consultant['email']); ?><br>
                                                    <strong>Specialization:</strong> <?php echo htmlspecialchars($consultant['specialization']); ?><br>
                                                    <strong>Degree:</strong> <?php echo htmlspecialchars($consultant['degree']); ?><br>
                                                    <strong>License:</strong> <?php echo htmlspecialchars($consultant['license_no'] ?? 'N/A'); ?>
                                                </p>
                                                <div class="btn-group d-flex gap-2">
                                                    <form method="POST" style="flex: 1;">
                                                        <input type="hidden" name="id" value="<?php echo $consultant['consultant_id']; ?>">
                                                        <input type="hidden" name="type" value="consultant">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="openRejectModal(<?php echo $consultant['consultant_id']; ?>, 'consultant')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Consultants -->
                        <div class="col-lg-6">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-check-circle"></i> Approved Consultants
                                    </h3>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <?php if (empty($approved_consultants)): ?>
                                        <p class="text-muted"><i class="fas fa-info-circle"></i> No approved consultants yet.</p>
                                    <?php else: ?>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Specialization</th>
                                                    <th>Email</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($approved_consultants as $consultant): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($consultant['user_name']); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($consultant['specialization'], 0, 15)); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($consultant['email'], 0, 20)); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4);">
        <div style="background-color:#fefefe; margin:10% auto; padding:20px; border:1px solid #888; width:80%; max-width:500px; border-radius:8px;">
            <span class="close" onclick="closeRejectModal()" style="cursor:pointer; float:right; font-size:28px;">&times;</span>
            <h4><i class="fas fa-times-circle"></i> Reject Registration</h4>
            <form method="POST">
                <input type="hidden" id="reject_id" name="id">
                <input type="hidden" id="reject_type" name="type">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label>Rejection Reason (optional):</label>
                    <textarea class="form-control" id="reason" name="reason" rows="4"></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>

<script>
function openRejectModal(id, type) {
    document.getElementById('reject_id').value = id;
    document.getElementById('reject_type').value = type;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('reason').value = '';
}

window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
