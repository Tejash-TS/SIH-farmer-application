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

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$limit = 12;
$offset = ($page - 1) * $limit;

$products = $buyerDAO->getFarmerProducts($limit, $offset, $search, $type);
$cart_count = count($buyerDAO->getCartItems($user_id));

$types_result = $conn->query("SELECT DISTINCT type FROM products WHERE is_active = 'Y' AND product_source = 'farmer' ORDER BY type");
$types = [];
while ($row = $types_result->fetch_assoc()) {
    if (!empty($row['type'])) {
        $types[] = $row['type'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farm Fresh Products</title>
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
                    <div class="col-sm-6"><h1 class="m-0">Farm Fresh Products</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="buyer/dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Farm Fresh</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" placeholder="Search farm products..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="type" class="form-control">
                                    <option value="">All Types</option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="alert alert-info"><i class="fas fa-info-circle"></i> No farm products found.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card product-card shadow-sm h-100">
                                    <div style="height: 200px; overflow: hidden; background-color: #f0f0f0;" class="position-relative">
                                        <?php if (!empty($product['pro_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['pro_image']); ?>" alt="<?php echo htmlspecialchars($product['pro_name']); ?>" class="card-img-top" style="height: 100%; width: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center" style="height: 100%;"><i class="fas fa-image fa-3x text-muted"></i></div>
                                        <?php endif; ?>
                                        <span class="badge badge-success position-absolute" style="top: 10px; right: 10px;">🌾 Farm</span>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title"><?php echo htmlspecialchars($product['pro_name']); ?></h6>
                                        <p class="small text-muted mb-1"><i class="fas fa-tractor"></i> <?php echo htmlspecialchars($product['farm_name'] ?? 'Unknown Farm'); ?></p>
                                        <span class="badge badge-secondary mb-2"><?php echo htmlspecialchars($product['type']); ?></span>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="text-success">₹<?php echo number_format((float)$product['pro_price'], 2); ?></strong>
                                                <small class="text-muted"><?php echo intval($product['pro_qty']); ?> left</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light p-2">
                                        <div class="btn-group btn-block">
                                            <a href="buyer/product_detail?id=<?php echo urlencode(ed('en', $product['pro_id'])); ?>&source=farmer" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>
                                            <a href="#" class="btn btn-sm btn-success add-to-cart" data-product-id="<?php echo intval($product['pro_id']); ?>"><i class="fas fa-cart-plus"></i> Add</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<div class="modal fade" id="addToCartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add to Cart</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Quantity:</label>
                    <input type="number" id="addQuantity" class="form-control" min="1" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAddToCart">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    let selectedProductId = null;
    $('.add-to-cart').click(function(e) {
        e.preventDefault();
        selectedProductId = $(this).data('product-id');
        $('#addToCartModal').modal('show');
    });

    $('#confirmAddToCart').click(function() {
        const quantity = parseInt($('#addQuantity').val());
        if (quantity > 0) {
            const form = $('<form method="POST" action="buyer/cart"><input type="hidden" name="action" value="add"><input type="hidden" name="pro_id" value="' + selectedProductId + '"><input type="hidden" name="quantity" value="' + quantity + '"></form>');
            $('body').append(form);
            form.submit();
        }
    });
});
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
