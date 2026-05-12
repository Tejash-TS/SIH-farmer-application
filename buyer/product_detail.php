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

$pro_id = null;
$source = isset($_GET['source']) ? $_GET['source'] : '';

if (isset($_GET['id'])) {
    $encrypted_id = $_GET['id'];
    $pro_id = intval(ed('de', $encrypted_id));
} else {
    header('location:browse_products.php');
    exit;
}

$product = $buyerDAO->getProductDetails($pro_id);
if (!$product) {
    header('location:browse_products.php');
    exit;
}

$reviews = $buyerDAO->getProductReviews($pro_id, $product['product_source']);
$cart_items = $buyerDAO->getCartItems($user_id);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
        $quantity = intval($_POST['quantity'] ?? 1);
        if ($quantity > 0 && $quantity <= $product['pro_qty']) {
            if ($buyerDAO->addToCart($user_id, $pro_id, $quantity)) {
                $message = 'Product added to cart!';
                $message_type = 'success';
            } else {
                $message = 'Failed to add product to cart.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Invalid quantity.';
            $message_type = 'warning';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_review') {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? '');
        
        if ($rating >= 1 && $rating <= 5) {
            $seller_id = $product['product_source'] === 'farmer' ? $product['farmer_id'] : $product['vendor_id'];
            if ($buyerDAO->addReview($user_id, $pro_id, $rating, $review_text, $product['product_source'], $seller_id)) {
                $message = 'Review added successfully!';
                $message_type = 'success';
                $reviews = $buyerDAO->getProductReviews($pro_id, $product['product_source']);
            } else {
                $message = 'Failed to add review.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Please select a rating.';
            $message_type = 'warning';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($product['pro_name']); ?></title>
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
                        <h1 class="m-0"><?php echo htmlspecialchars($product['pro_name']); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="browse_products.php">Products</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['pro_name']); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <section class="content">
            <div class="container-fluid">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Product Image & Details -->
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="product-image-container" style="height: 400px; overflow: hidden; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($product['pro_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['pro_image']); ?>" alt="<?php echo htmlspecialchars($product['pro_name']); ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <i class="fas fa-image fa-5x text-muted"></i>
                                                    <p class="text-muted mt-2">No image available</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <span class="badge badge-<?php echo $product['product_source'] === 'farmer' ? 'success' : 'primary'; ?> mb-2">
                                                <?php echo $product['product_source'] === 'farmer' ? '🌾 Fresh Farm Product' : '🏪 Vendor Product'; ?>
                                            </span>
                                            <span class="badge badge-secondary mb-2"><?php echo htmlspecialchars($product['type']); ?></span>
                                        </div>
                                        
                                        <h3><?php echo htmlspecialchars($product['pro_name']); ?></h3>
                                        
                                        <div class="mb-3">
                                            <h4 class="text-success">₹<?php echo number_format((float)$product['pro_price'], 2); ?></h4>
                                            <p class="text-muted">
                                                Stock: <strong><?php echo intval($product['pro_qty']); ?> units</strong>
                                            </p>
                                        </div>
                                        
                                        <div class="seller-info card mb-3">
                                            <div class="card-body">
                                                <h6><i class="fas fa-info-circle"></i> Seller Information</h6>
                                                <?php if ($product['product_source'] === 'farmer'): ?>
                                                    <p><strong>Farm:</strong> <?php echo htmlspecialchars($product['farm_name'] ?? 'Unknown'); ?></p>
                                                <?php else: ?>
                                                    <p><strong>Vendor:</strong> <?php echo htmlspecialchars($product['company_name'] ?? 'Unknown'); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($product['pro_qty'] > 0): ?>
                                            <form method="post">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <div class="input-group mb-3">
                                                    <input type="number" name="quantity" class="form-control" min="1" max="<?php echo intval($product['pro_qty']); ?>" value="1" required>
                                                    <div class="input-group-append">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-danger">Out of Stock</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Details -->
                        <div class="card shadow-sm mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Product Details</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($product['pro_description'])): ?>
                                    <div class="mb-4">
                                        <h6>Description</h6>
                                        <p><?php echo nl2br(htmlspecialchars($product['pro_description'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['pro_uses'])): ?>
                                    <div class="mb-4">
                                        <h6>Uses & Benefits</h6>
                                        <p><?php echo nl2br(htmlspecialchars($product['pro_uses'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['pro_contents'])): ?>
                                    <div class="mb-4">
                                        <h6>Contents & Specifications</h6>
                                        <p><?php echo nl2br(htmlspecialchars($product['pro_contents'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reviews Section -->
                    <div class="col-md-4">
                        <!-- Add Review Form -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Write a Review</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="add_review">
                                    
                                    <div class="form-group">
                                        <label>Rating</label>
                                        <div class="rating-input">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <label class="d-inline mr-2">
                                                    <input type="radio" name="rating" value="<?php echo $i; ?>" required> 
                                                    <i class="fas fa-star text-warning"></i> <?php echo $i; ?>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Your Review</label>
                                        <textarea name="review_text" class="form-control" rows="3" placeholder="Share your experience with this product..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-block">Submit Review</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Display Reviews -->
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">Customer Reviews (<?php echo count($reviews); ?>)</h5>
                            </div>
                            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                <?php if (empty($reviews)): ?>
                                    <p class="text-muted text-center">No reviews yet. Be the first to review!</p>
                                <?php else: ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="review-item mb-3 pb-3 border-bottom">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                                                <small class="text-muted"><?php echo datetime_format($review['created_on'], 'd M Y'); ?></small>
                                            </div>
                                            <div class="mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
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
