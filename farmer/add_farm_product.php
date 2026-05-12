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
$status = $_GET['status'] ?? '';
$message = '';
$message_type = 'info';

$farmerDAO = new FarmerDAO($conn);
$farmer = $farmerDAO->getFarmerProfile($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$farmer) {
        $message = 'Complete your farm profile first.';
        $message_type = 'warning';
    } elseif (($farmer['verification_status'] ?? 'pending') !== 'approved') {
        $message = 'Your farm profile must be approved before product submission.';
        $message_type = 'warning';
    } else {
        $pro_name = trim($_POST['pro_name'] ?? '');
        $pro_description = trim($_POST['pro_description'] ?? '');
        $pro_uses = trim($_POST['pro_uses'] ?? '');
        $pro_contents = trim($_POST['pro_contents'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $pro_price = trim($_POST['pro_price'] ?? '0');
        $pro_qty = intval($_POST['pro_qty'] ?? 0);
        $pro_image = '';

        if ($pro_name === '' || $type === '') {
            $message = 'Product name and type are required.';
            $message_type = 'warning';
        } elseif (empty($_FILES['pro_image']['name'])) {
            $message = 'Please upload a product image.';
            $message_type = 'warning';
        } else {
            $upload_dir = dirname(__DIR__) . '/uploads/farmer_products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['pro_image']['type'] ?? '';
            $file_tmp = $_FILES['pro_image']['tmp_name'] ?? '';
            $file_error = $_FILES['pro_image']['error'] ?? UPLOAD_ERR_NO_FILE;
            $file_name = $_FILES['pro_image']['name'] ?? '';

            if ($file_error !== UPLOAD_ERR_OK) {
                $message = 'Image upload failed. Please try again.';
                $message_type = 'danger';
            } elseif (!in_array($file_type, $allowed_types, true)) {
                $message = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
                $message_type = 'warning';
            } else {
                $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $safe_name = 'product_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $destination = $upload_dir . $safe_name;

                if (!move_uploaded_file($file_tmp, $destination)) {
                    $message = 'Unable to save uploaded image.';
                    $message_type = 'danger';
                } else {
                    $pro_image = 'uploads/farmer_products/' . $safe_name;
                }
            }

            if ($pro_image === '') {
                // Keep the current message if upload already failed.
            } else {
            $result = $farmerDAO->addFarmerProduct(
                $farmer['farmer_id'],
                $pro_name,
                $pro_image,
                $pro_description,
                $pro_uses,
                $pro_contents,
                $type,
                $pro_price,
                $pro_qty,
                $user_id
            );
            
            if ($result['status']) {
                header('location:add_farm_product?status=added');
                exit;
            } else {
                $message = $result['message'];
                $message_type = 'danger';
            }
            }
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
    <title>Add Farm Product</title>
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
                    <div class="col-sm-6"><h1 class="m-0">Add Farm Product</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Add Product</li>
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
                        <strong>Success!</strong> Your farm product has been submitted for approval.
                    </div>
                <?php endif; ?>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!$farmer): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle"></i> Please complete your farm profile first before adding products.
                                <a href="farmer/profile" class="btn btn-sm btn-warning mt-2">Complete Profile</a>
                            </div>
                        <?php elseif ($farmer['verification_status'] !== 'approved'): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-clock"></i> Your farm profile is under review. 
                                Status: <strong><?php echo htmlspecialchars($farmer['verification_status']); ?></strong><br>
                                You can add products once your profile is approved.
                            </div>
                        <?php else: ?>
                            <form method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="pro_name">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" id="pro_name" name="pro_name" class="form-control" placeholder="e.g., Fresh Tomatoes, Organic Carrots" required>
                                    </div>
                                    
                                    <div class="col-md-6 form-group">
                                        <label for="type">Product Type <span class="text-danger">*</span></label>
                                        <select id="type" name="type" class="form-control" required>
                                            <option value="">Select Type</option>
                                            <option value="Vegetables">Vegetables</option>
                                            <option value="Fruits">Fruits</option>
                                            <option value="Grains">Grains</option>
                                            <option value="Dairy">Dairy</option>
                                            <option value="Organic">Organic</option>
                                            <option value="Processed">Processed Products</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 form-group">
                                        <label for="pro_image">Product Image <span class="text-danger">*</span></label>
                                        <input type="file" id="pro_image" name="pro_image" class="form-control" accept="image/*" required>
                                        <small class="text-muted">Upload JPG, PNG, GIF, or WEBP image.</small>
                                    </div>
                                    
                                    <div class="col-md-3 form-group">
                                        <label for="pro_price">Price (₹) <span class="text-danger">*</span></label>
                                        <input type="number" id="pro_price" name="pro_price" class="form-control" placeholder="0.00" step="0.01" required>
                                    </div>
                                    
                                    <div class="col-md-3 form-group">
                                        <label for="pro_qty">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" id="pro_qty" name="pro_qty" class="form-control" min="0" value="0" required>
                                    </div>
                                    
                                    <div class="col-12 form-group">
                                        <label for="pro_description">Description</label>
                                        <textarea id="pro_description" name="pro_description" class="form-control" rows="3" placeholder="Describe your product..."></textarea>
                                    </div>
                                    
                                    <div class="col-md-6 form-group">
                                        <label for="pro_uses">Uses/Benefits</label>
                                        <textarea id="pro_uses" name="pro_uses" class="form-control" rows="3" placeholder="What are the uses and benefits?"></textarea>
                                    </div>
                                    
                                    <div class="col-md-6 form-group">
                                        <label for="pro_contents">Contents/Specifications</label>
                                        <textarea id="pro_contents" name="pro_contents" class="form-control" rows="3" placeholder="Details about contents, packing, etc."></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Submit Product for Approval
                                        </button>
                                        <a href="farmer/farmer_products" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Products
                                        </a>
                                    </div>
                                </div>
                            </form>
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
