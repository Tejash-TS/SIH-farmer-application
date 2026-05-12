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

// Get recent farmer products
$recent_farmer_products = $buyerDAO->getFarmerProducts(6, 0);

// Get recent vendor products
$recent_vendor_products = $buyerDAO->getVendorProducts(6, 0);

// Get cart count
$cart_items = $buyerDAO->getCartItems($user_id);
$cart_count = count($cart_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buyer Dashboard</title>
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
                        <h1 class="m-0">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></h1>
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
                <!-- Quick Stats Row -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $cart_count; ?></h3>
                                <p>Items in Cart</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <a href="cart.php" class="small-box-footer">View Cart <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo count($recent_farmer_products); ?></h3>
                                <p>Farm Fresh Products</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-apple-alt"></i>
                            </div>
                            <a href="farmer_products.php" class="small-box-footer">Browse <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo count($recent_vendor_products); ?></h3>
                                <p>Vendor Products</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <a href="vendor_products.php" class="small-box-footer">Browse <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>Shop</h3>
                                <p>Start Shopping</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <a href="browse_products.php" class="small-box-footer">Browse All <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Featured Farm Products -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success">
                                <h3 class="card-title"><i class="fas fa-apple-alt"></i> Fresh from Farmers</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_farmer_products)): ?>
                                    <p class="text-muted text-center">No farm products available yet.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($recent_farmer_products as $product): ?>
                                            <div class="col-md-4 col-sm-6 mb-4">
                                                <div class="card product-card shadow-sm">
                                                    <div class="position-relative" style="height: 200px; overflow: hidden;">
                                                        <?php if (!empty($product['pro_image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($product['pro_image']); ?>" alt="<?php echo htmlspecialchars($product['pro_name']); ?>" class="card-img-top" style="height: 100%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="d-flex align-items-center justify-content-center bg-light" style="height: 100%;">
                                                                <i class="fas fa-image fa-3x text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <span class="badge badge-success position-absolute" style="top: 10px; right: 10px;"><?php echo htmlspecialchars($product['type']); ?></span>
                                                    </div>
                                                    <div class="card-body p-3">
                                                        <h5 class="card-title"><?php echo htmlspecialchars($product['pro_name']); ?></h5>
                                                        <p class="card-text small text-muted"><i class="fas fa-farm"></i> <?php echo htmlspecialchars($product['farm_name']); ?></p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <strong class="text-success">₹<?php echo number_format((float)$product['pro_price'], 2); ?></strong>
                                                            <small class="text-muted"><?php echo intval($product['pro_qty']); ?> in stock</small>
                                                        </div>
                                                        <?php if ((float)$product['avg_rating'] > 0): ?>
                                                            <div class="mt-2">
                                                                <i class="fas fa-star text-warning"></i> <?php echo round($product['avg_rating'], 1); ?> (<?php echo intval($product['review_count']); ?> reviews)
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-footer bg-light">
                                                        <a href="buyer/product_detail.php?id=<?php echo urlencode(ed('en', $product['pro_id'])); ?>&source=farmer" class="btn btn-sm btn-info btn-block">
                                                            <i class="fas fa-eye"></i> View Details
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="buyer/farmer_products.php" class="btn btn-success">View All Farm Products <i class="fas fa-arrow-right"></i></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Featured Vendor Products -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title"><i class="fas fa-store"></i> From Vendors</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_vendor_products)): ?>
                                    <p class="text-muted text-center">No vendor products available yet.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($recent_vendor_products as $product): ?>
                                            <div class="col-md-4 col-sm-6 mb-4">
                                                <div class="card product-card shadow-sm">
                                                    <div class="position-relative" style="height: 200px; overflow: hidden;">
                                                        <?php if (!empty($product['pro_image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($product['pro_image']); ?>" alt="<?php echo htmlspecialchars($product['pro_name']); ?>" class="card-img-top" style="height: 100%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="d-flex align-items-center justify-content-center bg-light" style="height: 100%;">
                                                                <i class="fas fa-image fa-3x text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <span class="badge badge-primary position-absolute" style="top: 10px; right: 10px;"><?php echo htmlspecialchars($product['type']); ?></span>
                                                    </div>
                                                    <div class="card-body p-3">
                                                        <h5 class="card-title"><?php echo htmlspecialchars($product['pro_name']); ?></h5>
                                                        <p class="card-text small text-muted"><i class="fas fa-building"></i> <?php echo htmlspecialchars($product['company_name']); ?></p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <strong class="text-primary">₹<?php echo number_format((float)$product['pro_price'], 2); ?></strong>
                                                            <small class="text-muted"><?php echo intval($product['pro_qty']); ?> in stock</small>
                                                        </div>
                                                        <?php if ((float)$product['avg_rating'] > 0): ?>
                                                            <div class="mt-2">
                                                                <i class="fas fa-star text-warning"></i> <?php echo round($product['avg_rating'], 1); ?> (<?php echo intval($product['review_count']); ?> reviews)
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-footer bg-light">
                                                        <a href="buyer/product_detail.php?id=<?php echo urlencode(ed('en', $product['pro_id'])); ?>&source=vendor" class="btn btn-sm btn-info btn-block">
                                                            <i class="fas fa-eye"></i> View Details
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="vendor_products.php" class="btn btn-primary">View All Vendor Products <i class="fas fa-arrow-right"></i></a>
                                    </div>
                                <?php endif; ?>
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
