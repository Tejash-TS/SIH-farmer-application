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
$admin_id = $_SESSION['user']['user_id'];

// Handle product approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $approval_id = intval($_POST['approval_id'] ?? 0);

    if ($action === 'approve' && $approval_id) {
        $adminDAO->approveProduct($approval_id, $admin_id);
        header("location:admin/product_approval.php?status=approved");
        exit;
    } elseif ($action === 'reject' && $approval_id) {
        $reason = $_POST['reason'] ?? '';
        $adminDAO->rejectProduct($approval_id, $reason, $admin_id);
        header("location:admin/product_approval.php?status=rejected");
        exit;
    } elseif ($action === 'block') {
        $pro_id = intval($_POST['pro_id'] ?? 0);
        $block_status = $_POST['block_status'] ?? 'N';
        $adminDAO->blockProduct($pro_id, $block_status);
        header("location:admin/product_approval.php?status=blocked");
        exit;
    }
}

$pending_products = $adminDAO->getPendingProducts();
$approved_products = $adminDAO->getApprovedProducts();
$status_message = $_GET['status'] ?? '';
$tab = $_GET['tab'] ?? 'pending';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Approval - Admin</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }
        .product-info {
            padding: 12px;
        }
        .badge-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
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
                        <h1 class="m-0"><i class="fas fa-box"></i> Product Approval</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin">Home</a></li>
                            <li class="breadcrumb-item active">Product Approval</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($status_message === 'approved'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Product approved successfully!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php elseif ($status_message === 'rejected'): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-times-circle"></i> Product rejected!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php elseif ($status_message === 'blocked'): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-ban"></i> Product status updated!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'pending' ? 'active' : ''; ?>" href="admin/product_approval.php?tab=pending">
                            <i class="fas fa-hourglass-half"></i> Pending Approval
                            <span class="badge badge-warning ml-2"><?php echo count($pending_products); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab === 'approved' ? 'active' : ''; ?>" href="admin/product_approval.php?tab=approved">
                            <i class="fas fa-check-circle"></i> Approved Products
                        </a>
                    </li>
                </ul>

                <!-- Pending Products -->
                <?php if ($tab === 'pending'): ?>
                    <div class="row">
                        <?php if (empty($pending_products)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-check-circle"></i> All products are approved! No pending items.
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_products as $product): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="product-card">
                                        <img src="<?php echo htmlspecialchars($product['pro_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['pro_name']); ?>"
                                             class="product-image" onerror="this.src='assets/dist/img/placeholder.png'">
                                        
                                        <div class="product-info">
                                            <h5 class="mb-1">
                                                <?php echo htmlspecialchars(substr($product['pro_name'], 0, 40)); ?>
                                            </h5>
                                            <p class="text-muted small mb-2">
                                                by <strong><?php echo htmlspecialchars($product['farmer_name']); ?></strong>
                                            </p>
                                            <p class="text-muted small mb-2">
                                                Farm: <strong><?php echo htmlspecialchars($product['farm_name']); ?></strong>
                                            </p>
                                            <p class="text-muted small mb-2">
                                                <?php echo htmlspecialchars(substr($product['pro_description'], 0, 60)); ?>...
                                            </p>
                                            
                                            <span class="badge badge-type" style="background-color: #2196F3; color: white;">
                                                <?php echo htmlspecialchars($product['type']); ?>
                                            </span>
                                            
                                            <div class="d-flex gap-2 mt-3">
                                                <form method="POST" style="flex: 1;">
                                                    <input type="hidden" name="approval_id" value="<?php echo $product['approval_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="openRejectModal(<?php echo $product['approval_id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                <!-- Approved Products -->
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title"><i class="fas fa-check-circle"></i> Approved Products</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="approvedProductsTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Vendor</th>
                                            <th>Type</th>
                                            <th>Rating</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars(substr($product['pro_name'], 0, 30)); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['farm_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($product['type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($product['avg_rating']): ?>
                                                        <span class="badge badge-warning">
                                                            ⭐ <?php echo number_format($product['avg_rating'], 1); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No ratings</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success">Active</span>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="pro_id" value="<?php echo $product['pro_id']; ?>">
                                                        <input type="hidden" name="action" value="block">
                                                        <input type="hidden" name="block_status" value="Y">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Block Product">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                </td>
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

    <!-- Reject Modal -->
    <div id="rejectModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4);">
        <div style="background-color:#fefefe; margin:10% auto; padding:20px; border:1px solid #888; width:80%; max-width:500px; border-radius:8px;">
            <span class="close" onclick="closeRejectModal()" style="cursor:pointer; float:right; font-size:28px;">&times;</span>
            <h4><i class="fas fa-times-circle"></i> Reject Product</h4>
            <form method="POST">
                <input type="hidden" id="reject_approval_id" name="approval_id">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label>Rejection Reason (optional):</label>
                    <textarea class="form-control" id="reason" name="reason" rows="4" 
                              placeholder="Enter reason for rejection..."></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Product</button>
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
    $('#approvedProductsTable').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
    });
});

function openRejectModal(approvalId) {
    document.getElementById('reject_approval_id').value = approvalId;
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
