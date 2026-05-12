<?php
session_start();
include_once('../_functions.php');
require_once('buyer_dao.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$buyerDAO = new BuyerDAO($conn);
$orders = $buyerDAO->getOrderHistory($user_id);
$cart_count = count($buyerDAO->getCartItems($user_id));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php include_once('_header.php'); ?>
    <?php include_once('_sidebar.php'); ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1 class="m-0">My Orders</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="buyer/dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Orders</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h3 class="card-title mb-0">Order History (<?php echo count($orders); ?>)</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                <p class="text-muted mb-3">You have not placed any orders yet.</p>
                                <a href="buyer/browse_products" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag"></i> Browse Products
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Product</th>
                                            <th>Source</th>
                                            <th>Qty</th>
                                            <th>Amount</th>
                                            <th>Payment</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo intval($order['purchas_id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['pro_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($order['product_source'] === 'farmer'): ?>
                                                        <span class="badge badge-success">Farm</span>
                                                        <small class="d-block text-muted"><?php echo htmlspecialchars($order['farm_name'] ?? 'Unknown'); ?></small>
                                                    <?php else: ?>
                                                        <span class="badge badge-primary">Vendor</span>
                                                        <small class="d-block text-muted"><?php echo htmlspecialchars($order['company_name'] ?? 'Unknown'); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo intval($order['pro_qty']); ?></td>
                                                <td>₹<?php echo number_format((float)$order['total_amt'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
                                                <td><?php echo datetime_format($order['created_on'], 'd M Y, h:i A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
