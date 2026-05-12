<?php
session_start();
include_once('../_functions.php');
global $conn;

if (!isset($_SESSION['user'])) {
	header('location:../login');
	exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$status = $_GET['status'] ?? '';
$message = '';
$message_type = 'info';

function load_vendor_profile($conn, $user_id)
{
	$stmt = $conn->prepare('SELECT * FROM vendors WHERE user_id = ? LIMIT 1');
	$stmt->bind_param('i', $user_id);
	$stmt->execute();
	$result = $stmt->get_result();
	$vendor = $result->fetch_assoc() ?: null;
	$stmt->close();
	return $vendor;
}

function vendor_product_redirect($status)
{
	header('location:add_product?status=' . urlencode($status));
	exit;
}

$vendor = load_vendor_profile($conn, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!$vendor) {
		$message = 'Complete your vendor profile first.';
		$message_type = 'warning';
	} elseif (($vendor['verification_status'] ?? 'pending') !== 'approved') {
		$message = 'Your vendor profile must be approved before product submission.';
		$message_type = 'warning';
	} else {
		$pro_name = trim($_POST['pro_name'] ?? '');
		$pro_image = trim($_POST['pro_image'] ?? '');
		$pro_description = trim($_POST['pro_description'] ?? '');
		$pro_uses = trim($_POST['pro_uses'] ?? '');
		$pro_contents = trim($_POST['pro_contents'] ?? '');
		$type = trim($_POST['type'] ?? '');
		$pro_price = trim($_POST['pro_price'] ?? '0');
		$pro_qty = intval($_POST['pro_qty'] ?? 0);

		if ($pro_name === '' || $type === '') {
			$message = 'Product name and type are required.';
			$message_type = 'warning';
		} else {
			$conn->begin_transaction();
			try {
				$product_stmt = $conn->prepare('INSERT INTO products (pro_name, pro_image, pro_description, pro_uses, pro_contents, type, vendor_id, is_block, is_active, created_on, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, "N", "Y", NOW(), ?)');
				$product_stmt->bind_param('ssssssii', $pro_name, $pro_image, $pro_description, $pro_uses, $pro_contents, $type, $vendor['vendor_id'], $user_id);
				if (!$product_stmt->execute()) {
					throw new Exception('Product insert failed');
				}
				$pro_id = $conn->insert_id;
				$product_stmt->close();

				$link_stmt = $conn->prepare('INSERT INTO vendor_products (vendor_id, pro_id, created_on) VALUES (?, ?, NOW())');
				$link_stmt->bind_param('ii', $vendor['vendor_id'], $pro_id);
				if (!$link_stmt->execute()) {
					throw new Exception('Vendor link failed');
				}
				$link_stmt->close();

				$approval_stmt = $conn->prepare('INSERT INTO product_approval (pro_id, vendor_id, approval_status, created_on, created_by) VALUES (?, ?, "pending", NOW(), ?)');
				$approval_stmt->bind_param('iii', $pro_id, $vendor['vendor_id'], $user_id);
				if (!$approval_stmt->execute()) {
					throw new Exception('Approval insert failed');
				}
				$approval_stmt->close();

				$inventory_stmt = $conn->prepare('INSERT INTO pro_inventory (pro_id, pro_price, pro_qty, is_active, created_on, created_by) VALUES (?, ?, ?, "Y", NOW(), ?)');
				$inventory_stmt->bind_param('isii', $pro_id, $pro_price, $pro_qty, $user_id);
				if (!$inventory_stmt->execute()) {
					throw new Exception('Inventory insert failed');
				}
				$inventory_stmt->close();

				$conn->commit();
				vendor_product_redirect('added');
			} catch (Throwable $e) {
				$conn->rollback();
				$message = 'Unable to add product. Please verify your inputs and try again.';
				$message_type = 'danger';
			}
		}
	}
}

$vendor = load_vendor_profile($conn, $user_id);
$profile_status = $vendor['verification_status'] ?? 'not_created';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Add Product</title>
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
					<div class="col-sm-6"><h1 class="m-0">Add Product</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="vendor/dashboard">Home</a></li><li class="breadcrumb-item active">Add Product</li></ol></div>
				</div>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<?php if ($status === 'added'): ?><div class="alert alert-success alert-dismissible fade show">Product submitted for approval.<button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
				<?php if ($message): ?><div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show"><?php echo htmlspecialchars($message); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
				<div class="card shadow-sm">
					<div class="card-body">
						<?php if (!$vendor): ?>
							<div class="alert alert-warning mb-0">Complete your vendor profile first.</div>
						<?php elseif ($profile_status !== 'approved'): ?>
							<div class="alert alert-warning mb-0">Your vendor profile must be approved before product submission.</div>
						<?php else: ?>
							<form method="post">
								<div class="row">
									<div class="col-md-6 form-group"><label>Product Name</label><input type="text" name="pro_name" class="form-control" required></div>
									<div class="col-md-6 form-group"><label>Type</label><input type="text" name="type" class="form-control" placeholder="Pesticide / Seed / Fertilizer" required></div>
									<div class="col-md-6 form-group"><label>Image URL</label><input type="text" name="pro_image" class="form-control" placeholder="assets/... or https://..."></div>
									<div class="col-md-3 form-group"><label>Price</label><input type="text" name="pro_price" class="form-control" placeholder="0.00"></div>
									<div class="col-md-3 form-group"><label>Quantity</label><input type="number" name="pro_qty" class="form-control" min="0" value="0"></div>
									<div class="col-12 form-group"><label>Description</label><textarea name="pro_description" class="form-control" rows="3"></textarea></div>
									<div class="col-md-6 form-group"><label>Uses</label><textarea name="pro_uses" class="form-control" rows="3"></textarea></div>
									<div class="col-md-6 form-group"><label>Contents</label><textarea name="pro_contents" class="form-control" rows="3"></textarea></div>
								</div>
								<button type="submit" class="btn btn-success">Submit Product for Approval</button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/dist/js/custom.js"></script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
