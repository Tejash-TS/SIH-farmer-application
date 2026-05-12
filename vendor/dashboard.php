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
$message = '';
$message_type = 'info';

function vendor_redirect_with_status($status)
{
	header('location:vendor/dashboard?status=' . urlencode($status));
	exit;
}

$vendor_stmt = $conn->prepare('SELECT * FROM vendors WHERE user_id = ? LIMIT 1');
$vendor_stmt->bind_param('i', $user_id);
$vendor_stmt->execute();
$vendor_result = $vendor_stmt->get_result();
$vendor = $vendor_result->fetch_assoc() ?: null;
$vendor_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	if ($action === 'save_profile') {
		$company_name = trim($_POST['company_name'] ?? '');
		$license_no = trim($_POST['license_no'] ?? '');
		$location = trim($_POST['location'] ?? '');
		$phone_number = trim($_POST['phone_number'] ?? '');

		if ($company_name === '' || $license_no === '') {
			$message = 'Company name and license number are required.';
			$message_type = 'warning';
		} else {
			if ($vendor) {
				$stmt = $conn->prepare('UPDATE vendors SET company_name = ?, license_no = ?, location = ?, phone_number = ?, modified_on = NOW(), modified_by = ? WHERE vendor_id = ?');
				$stmt->bind_param('ssssii', $company_name, $license_no, $location, $phone_number, $user_id, $vendor['vendor_id']);
			} else {
				$stmt = $conn->prepare('INSERT INTO vendors (user_id, company_name, license_no, location, phone_number, created_on, created_by) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
				$stmt->bind_param('issssi', $user_id, $company_name, $license_no, $location, $phone_number, $user_id);
			}

			if ($stmt && $stmt->execute()) {
				$stmt->close();
				vendor_redirect_with_status('profile_saved');
			}

			$message = 'Unable to save vendor profile. The license number may already exist.';
			$message_type = 'danger';
		}
	}

	if ($action === 'add_product') {
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
					vendor_redirect_with_status('product_added');
				} catch (Throwable $e) {
					$conn->rollback();
					$message = 'Unable to add product. Please verify your inputs and try again.';
					$message_type = 'danger';
				}
			}
		}
	}
}

$vendor_stmt = $conn->prepare('SELECT * FROM vendors WHERE user_id = ? LIMIT 1');
$vendor_stmt->bind_param('i', $user_id);
$vendor_stmt->execute();
$vendor_result = $vendor_stmt->get_result();
$vendor = $vendor_result->fetch_assoc() ?: null;
$vendor_stmt->close();

$vendor_id = $vendor['vendor_id'] ?? 0;
$status = $_GET['status'] ?? '';

$total_products = 0;
$pending_products = 0;
$approved_products = 0;
$total_orders = 0;
$total_revenue = 0;

if ($vendor_id) {
	$stmt = $conn->prepare('SELECT COUNT(*) AS total_products FROM products WHERE vendor_id = ? AND is_active = "Y"');
	$stmt->bind_param('i', $vendor_id);
	$stmt->execute();
	$total_products = intval($stmt->get_result()->fetch_assoc()['total_products'] ?? 0);
	$stmt->close();

	$stmt = $conn->prepare('SELECT COUNT(*) AS pending_products FROM product_approval WHERE vendor_id = ? AND approval_status = "pending" AND is_active = "Y"');
	$stmt->bind_param('i', $vendor_id);
	$stmt->execute();
	$pending_products = intval($stmt->get_result()->fetch_assoc()['pending_products'] ?? 0);
	$stmt->close();

	$stmt = $conn->prepare('SELECT COUNT(*) AS approved_products FROM product_approval WHERE vendor_id = ? AND approval_status = "approved" AND is_active = "Y"');
	$stmt->bind_param('i', $vendor_id);
	$stmt->execute();
	$approved_products = intval($stmt->get_result()->fetch_assoc()['approved_products'] ?? 0);
	$stmt->close();

	$stmt = $conn->prepare('SELECT COUNT(*) AS total_orders, COALESCE(SUM(pp.total_amt), 0) AS total_revenue FROM purchase_product pp INNER JOIN products p ON p.pro_id = pp.pro_id WHERE p.vendor_id = ?');
	$stmt->bind_param('i', $vendor_id);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc() ?: [];
	$total_orders = intval($row['total_orders'] ?? 0);
	$total_revenue = floatval($row['total_revenue'] ?? 0);
	$stmt->close();
}

$recent_products = [];
if ($vendor_id) {
	$sql = 'SELECT p.pro_id, p.pro_name, p.pro_image, p.type, p.created_on, COALESCE(pi.pro_price, 0) AS pro_price, COALESCE(pi.pro_qty, 0) AS pro_qty, COALESCE(pa.approval_status, "pending") AS approval_status, pa.rejection_reason FROM products p LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y" LEFT JOIN product_approval pa ON pa.pro_id = p.pro_id AND pa.vendor_id = p.vendor_id AND pa.is_active = "Y" WHERE p.vendor_id = ? ORDER BY p.created_on DESC';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $vendor_id);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		$recent_products[] = $row;
	}
	$stmt->close();
}

$profile_status = $vendor['verification_status'] ?? 'not_created';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Vendor Dashboard</title>
	<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
	<link rel="stylesheet" href="assets/dist/css/custom.css">
	<style>
		.metric-card { border-radius: 18px; color: #fff; min-height: 120px; }
		.metric-card .inner { padding: 1.1rem 1.2rem; }
		.soft-panel { border-radius: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); }
		.status-pill { text-transform: uppercase; letter-spacing: .04em; font-size: .72rem; }
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
	<?php include_once('_header.php'); ?>
	<?php include_once('_sidebar.php'); ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-6">
						<h1 class="m-0">Vendor Dashboard</h1>
					</div>
					<div class="col-sm-6">
						<ol class="breadcrumb float-sm-right">
							<li class="breadcrumb-item"><a href="vendor/dashboard">Home</a></li>
							<li class="breadcrumb-item active">Vendor</li>
						</ol>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<?php if ($status === 'profile_saved'): ?>
					<div class="alert alert-success alert-dismissible fade show">Vendor profile saved successfully.<button type="button" class="close" data-dismiss="alert">&times;</button></div>
				<?php elseif ($status === 'product_added'): ?>
					<div class="alert alert-success alert-dismissible fade show">Product submitted for admin approval.<button type="button" class="close" data-dismiss="alert">&times;</button></div>
				<?php endif; ?>

				<?php if ($message): ?>
					<div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
						<?php echo htmlspecialchars($message); ?><button type="button" class="close" data-dismiss="alert">&times;</button>
					</div>
				<?php endif; ?>

				<div class="row">
					<div class="col-lg-3 col-6">
						<div class="small-box bg-info metric-card">
							<div class="inner"><h3><?php echo $total_products; ?></h3><p>Total Products</p></div>
							<div class="icon"><i class="fas fa-box"></i></div>
						</div>
					</div>
					<div class="col-lg-3 col-6">
						<div class="small-box bg-warning metric-card">
							<div class="inner"><h3><?php echo $pending_products; ?></h3><p>Pending Approvals</p></div>
							<div class="icon"><i class="fas fa-hourglass-half"></i></div>
						</div>
					</div>
					<div class="col-lg-3 col-6">
						<div class="small-box bg-success metric-card">
							<div class="inner"><h3><?php echo $approved_products; ?></h3><p>Approved Products</p></div>
							<div class="icon"><i class="fas fa-check-circle"></i></div>
						</div>
					</div>
					<div class="col-lg-3 col-6">
						<div class="small-box bg-secondary metric-card">
							<div class="inner"><h3><?php echo $total_orders; ?></h3><p>Orders / Revenue ₹<?php echo number_format($total_revenue, 2); ?></p></div>
							<div class="icon"><i class="fas fa-rupee-sign"></i></div>
						</div>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-md-4 mb-3">
						<a href="vendor/profile" class="card soft-panel h-100 text-dark text-decoration-none">
							<div class="card-body text-center">
								<i class="fas fa-id-card fa-3x text-primary mb-3"></i>
								<h4>Profile Page</h4>
								<p class="text-muted mb-0">Update company, license, location and phone details.</p>
							</div>
						</a>
					</div>
					<div class="col-md-4 mb-3">
						<a href="vendor/add_product" class="card soft-panel h-100 text-dark text-decoration-none">
							<div class="card-body text-center">
								<i class="fas fa-box-open fa-3x text-success mb-3"></i>
								<h4>Add Product</h4>
								<p class="text-muted mb-0">Submit pesticide products for admin approval.</p>
							</div>
						</a>
					</div>
					<div class="col-md-4 mb-3">
						<a href="vendor/products" class="card soft-panel h-100 text-dark text-decoration-none">
							<div class="card-body text-center">
								<i class="fas fa-clipboard-list fa-3x text-warning mb-3"></i>
								<h4>My Products</h4>
								<p class="text-muted mb-0">Track approvals, stock and recent submissions.</p>
							</div>
						</a>
					</div>
				</div>

				<div class="card soft-panel">
					<div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
						<h3 class="card-title mb-0"><i class="fas fa-clipboard-list"></i> Recent Products</h3>
						<a href="vendor/products" class="btn btn-sm btn-outline-primary">Open full list</a>
					</div>
					<div class="card-body p-0">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Product</th>
									<th>Type</th>
									<th>Price</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($recent_products)): ?>
									<tr><td colspan="4" class="text-center text-muted py-4">No products submitted yet.</td></tr>
								<?php else: ?>
									<?php foreach (array_slice($recent_products, 0, 5) as $product): ?>
										<tr>
											<td><?php echo htmlspecialchars($product['pro_name']); ?></td>
											<td><?php echo htmlspecialchars($product['type']); ?></td>
											<td>₹<?php echo number_format((float)$product['pro_price'], 2); ?></td>
											<td><span class="badge badge-<?php echo $product['approval_status'] === 'approved' ? 'success' : ($product['approval_status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars($product['approval_status']); ?></span></td>
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
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/dist/js/custom.js"></script>

<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
