<?php
session_start();
include_once('../_functions.php');
require_once('farmer_dao.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$farmerDAO = new FarmerDAO($conn);
$farmer = $farmerDAO->getFarmerProfile($user_id);

$products = [];
if ($farmer) {
    $products = $farmerDAO->getFarmerProducts($farmer['farmer_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Farm Products</title>
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
	

</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php include_once('_header.php'); ?>
    <?php include_once('_sidebar.php'); ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1 class="m-0">My Farm Products</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/dashboard">Home</a></li>
                            <li class="breadcrumb-item active">My Products</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-apple-alt"></i> Farm Products
                            <a href="add_farm_product.php" class="btn btn-sm btn-success float-right">
                                <i class="fas fa-plus"></i> Add New Product
                            </a>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Product Name</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Added On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            No products added yet.
                                            <a href="add_farm_product.php" class="btn btn-sm btn-success mt-2">
                                                <i class="fas fa-plus"></i> Add First Product
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($product['pro_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($product['pro_image']); ?>" alt="<?php echo htmlspecialchars($product['pro_name']); ?>" class="img-thumbnail mr-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="img-thumbnail mr-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f0f0;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($product['pro_name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['type']); ?></td>
                                            <td><strong>₹<?php echo number_format((float)$product['pro_price'], 2); ?></strong></td>
                                            <td>
                                                <span class="badge <?php echo $product['pro_qty'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?php echo intval($product['pro_qty']); ?> units
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $status_class = $product['approval_status'] === 'approved' ? 'success' : 
                                                                   ($product['approval_status'] === 'rejected' ? 'danger' : 'warning');
                                                ?>
                                                <span class="badge badge-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst($product['approval_status']); ?>
                                                </span>
                                                <?php if (!empty($product['rejection_reason'])): ?>
                                                    <div class="small text-muted mt-1">
                                                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($product['rejection_reason']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo !empty($product['created_on']) ? datetime_format($product['created_on'], 'd M Y, h:i A') : '-'; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="edit_farm_product.php?id=<?php echo urlencode(ed('en', $product['pro_id'])); ?>" class="btn btn-sm btn-info" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view_farm_product.php?id=<?php echo urlencode(ed('en', $product['pro_id'])); ?>" class="btn btn-sm btn-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.min.js"></script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
