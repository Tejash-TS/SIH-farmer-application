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

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $pro_id = intval($_POST['pro_id'] ?? 0);
        
        if ($_POST['action'] === 'add') {
            $quantity = intval($_POST['quantity'] ?? 1);
            if ($quantity > 0) {
                $buyerDAO->addToCart($user_id, $pro_id, $quantity);
                header('location:cart.php?status=added');
                exit;
            }
        } elseif ($_POST['action'] === 'remove') {
            $buyerDAO->removeFromCart($user_id, $pro_id);
            header('location:cart.php?status=removed');
            exit;
        } elseif ($_POST['action'] === 'update') {
            $quantity = intval($_POST['quantity'] ?? 0);
            $buyerDAO->updateCartQuantity($user_id, $pro_id, $quantity);
            header('location:cart.php?status=updated');
            exit;
        }
    }
}

$cart_items = $buyerDAO->getCartItems($user_id);
$status = isset($_GET['status']) ? $_GET['status'] : '';
$total_amount = 0;
$cart_count = count($cart_items);

foreach ($cart_items as $item) {
    $total_amount += (float)$item['pro_price'] * intval($item['pro_qty']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopping Cart</title>
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
                    <div class="col-sm-6">
                        <h1 class="m-0">Shopping Cart</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Cart</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <section class="content">
            <div class="container-fluid">
                <?php if ($status === 'added'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        Product added to cart!
                    </div>
                <?php elseif ($status === 'removed'): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        Product removed from cart.
                    </div>
                <?php elseif ($status === 'updated'): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        Cart updated.
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">Cart Items (<?php echo $cart_count; ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($cart_items)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                        <p class="text-muted mb-3">Your cart is empty</p>
                                        <a href="browse_products.php" class="btn btn-primary">
                                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cart_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($item['pro_image'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($item['pro_image']); ?>" alt="<?php echo htmlspecialchars($item['pro_name']); ?>" class="img-thumbnail mr-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <div class="img-thumbnail mr-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f0f0;">
                                                                        <i class="fas fa-image text-muted"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($item['pro_name']); ?></strong><br>
                                                                    <small class="text-muted">
                                                                        <?php if ($item['product_source'] === 'farmer'): ?>
                                                                            🌾 <?php echo htmlspecialchars($item['farm_name'] ?? 'Unknown'); ?>
                                                                        <?php else: ?>
                                                                            🏪 <?php echo htmlspecialchars($item['company_name'] ?? 'Unknown'); ?>
                                                                        <?php endif; ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>₹<?php echo number_format((float)$item['pro_price'], 2); ?></td>
                                                        <td>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="pro_id" value="<?php echo htmlspecialchars($item['pro_id']); ?>">
                                                                <div class="input-group" style="width: 100px;">
                                                                    <input type="number" name="quantity" class="form-control form-control-sm" min="1" value="<?php echo intval($item['pro_qty']); ?>" onchange="this.form.submit()">
                                                                </div>
                                                            </form>
                                                        </td>
                                                        <td>
                                                            <strong>₹<?php echo number_format((float)$item['pro_price'] * intval($item['pro_qty']), 2); ?></strong>
                                                        </td>
                                                        <td>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="remove">
                                                                <input type="hidden" name="pro_id" value="<?php echo htmlspecialchars($item['pro_id']); ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this item?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-right p-3 border-top">
                                        <a href="browse_products.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Continue Shopping
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cart Summary -->
                    <?php if (!empty($cart_items)): ?>
                        <div class="col-lg-4">
                            <div class="card shadow-sm sticky-top" style="top: 20px;">
                                <div class="card-header bg-primary">
                                    <h5 class="mb-0 text-white">Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-7">Subtotal:</div>
                                        <div class="col-5 text-right">₹<?php echo number_format($total_amount, 2); ?></div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-7">Tax (18%):</div>
                                        <div class="col-5 text-right">₹<?php echo number_format($total_amount * 0.18, 2); ?></div>
                                    </div>
                                    
                                    <div class="row border-top pt-3 mb-3">
                                        <div class="col-7"><strong>Total:</strong></div>
                                        <div class="col-5 text-right"><strong>₹<?php echo number_format($total_amount * 1.18, 2); ?></strong></div>
                                    </div>
                                    
                                    <a href="buyer/checkout.php" class="btn btn-success btn-block">
                                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
