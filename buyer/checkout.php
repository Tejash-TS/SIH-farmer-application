<?php
session_start();
include_once('../_functions.php');
require_once('buyer_dao.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;
$user_id = intval($_SESSION['user']['user_id']);
$buyerDAO = new BuyerDAO($conn);
$buyer = $buyerDAO->getBuyerProfile($user_id);
$cart_items = $buyerDAO->getCartItems($user_id);

if (empty($cart_items)) {
    header('location:cart.php?status=empty');
    exit;
}

$subtotal = 0.0;
foreach ($cart_items as $item) {
    $subtotal += (float)($item['pro_price'] ?? 0) * (int)($item['pro_qty'] ?? 0);
}
$tax = $subtotal * 0.18;
$grand_total = $subtotal + $tax;

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = trim($_POST['payment_method'] ?? '');
    $transaction_id = trim($_POST['transaction_id'] ?? '');

    if ($payment_method === '') {
        $message = 'Please select a payment method.';
        $message_type = 'warning';
    } else {
        if ($transaction_id === '') {
            $transaction_id = 'TXN' . date('YmdHis') . $user_id;
        }

        $result = $buyerDAO->placeOrder($user_id, $grand_total, $payment_method, $transaction_id);
        if (!empty($result['status'])) {
            header('location:orders.php?status=placed');
            exit;
        }

        $message = $result['message'] ?? 'Unable to place order.';
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .checkout-summary {
            position: sticky;
            top: 20px;
        }
        .item-row {
            border-bottom: 1px solid #e9ecef;
            padding: 12px 0;
        }
        .item-row:last-child {
            border-bottom: none;
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
                        <h1 class="m-0">Checkout</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="buyer/cart.php">Cart</a></li>
                            <li class="breadcrumb-item active">Checkout</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h3 class="card-title mb-0">Confirm Your Order</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="mb-2">Delivery Information</h5>
                                    <?php if ($buyer): ?>
                                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Buyer'); ?></p>
                                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($buyer['phone_number'] ?? $_SESSION['user']['mb_number'] ?? 'Not provided'); ?></p>
                                        <p class="mb-0"><strong>Address:</strong> <?php echo htmlspecialchars($buyer['address'] ?? 'Not provided'); ?></p>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">
                                            Please complete your buyer profile before placing an order.
                                            <a href="buyer/profile" class="btn btn-sm btn-warning ml-2">Update Profile</a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h5 class="mb-3">Order Items</h5>
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="item-row d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($item['pro_image']) ? htmlspecialchars($item['pro_image']) : 'assets/dist/img/photo1.png'; ?>" alt="<?php echo htmlspecialchars($item['pro_name']); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;" onerror="this.src='assets/dist/img/photo1.png'">
                                            <div class="ml-3">
                                                <strong><?php echo htmlspecialchars($item['pro_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo $item['product_source'] === 'farmer' ? 'Farmer: ' . htmlspecialchars($item['farm_name'] ?? 'Unknown') : 'Vendor: ' . htmlspecialchars($item['company_name'] ?? 'Unknown'); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div>₹<?php echo number_format((float)$item['pro_price'], 2); ?> x <?php echo intval($item['pro_qty']); ?></div>
                                            <strong>₹<?php echo number_format((float)$item['pro_price'] * intval($item['pro_qty']), 2); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="mt-4">
                                    <form method="POST">  
                                        <div class="form-group">
                                            <label for="payment_method">Payment Method *</label>
                                            <select id="payment_method" name="payment_method" class="form-control" required>
                                                <option value="">Select payment method</option>
                                                <option value="UPI">UPI</option>
                                                <option value="Cash On Delivery">Cash On Delivery</option>
                                                <option value="Credit Card">Credit Card</option>
                                                <option value="Debit Card">Debit Card</option>
                                                <option value="Net Banking">Net Banking</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="transaction_id">Transaction ID / Reference</label>
                                            <input type="text" id="transaction_id" name="transaction_id" class="form-control" placeholder="Optional for manual payment">
                                        </div>
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-check"></i> Place Order
                                        </button>
                                        <a href="buyer/cart.php" class="btn btn-secondary btn-lg">
                                            <i class="fas fa-arrow-left"></i> Back to Cart
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow-sm checkout-summary">
                            <div class="card-header bg-primary">
                                <h5 class="mb-0 text-white">Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <strong>₹<?php echo number_format($subtotal, 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (18%)</span>
                                    <strong>₹<?php echo number_format($tax, 2); ?></strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span>Total</span>
                                    <strong>₹<?php echo number_format($grand_total, 2); ?></strong>
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
<script src="assets/dist/js/adminlte.min.js"></script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
