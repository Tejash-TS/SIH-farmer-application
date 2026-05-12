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

$stmt = $conn->prepare('SELECT * FROM vendors WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();

$vendor_id = $vendor['vendor_id'] ?? 0;
$products = [];

if ($vendor_id) {
	$sql = 'SELECT p.pro_id, p.pro_name, p.pro_image, p.type, p.created_on, COALESCE(pi.pro_price, 0) AS pro_price, COALESCE(pi.pro_qty, 0) AS pro_qty, COALESCE(pa.approval_status, "pending") AS approval_status, pa.rejection_reason FROM products p LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = "Y" LEFT JOIN product_approval pa ON pa.pro_id = p.pro_id AND pa.vendor_id = p.vendor_id AND pa.is_active = "Y" WHERE p.vendor_id = ? ORDER BY p.created_on DESC';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $vendor_id);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		$products[] = $row;
	}
	$stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My Products</title>
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
					<div class="col-sm-6"><h1 class="m-0">My Products</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="vendor/dashboard">Home</a></li><li class="breadcrumb-item active">My Products</li></ol></div>
				</div>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<div class="card shadow-sm">
					<div class="card-body p-0">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Product</th>
									<th>Type</th>
									<th>Price</th>
									<th>Stock</th>
									<th>Status</th>
									<th>Added</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($products)): ?>
									<tr><td colspan="6" class="text-center text-muted py-4">No products submitted yet.</td></tr>
								<?php else: ?>
									<?php foreach ($products as $product): ?>
										<tr>
											<td><?php echo htmlspecialchars($product['pro_name']); ?></td>
											<td><?php echo htmlspecialchars($product['type']); ?></td>
											<td>₹<?php echo number_format((float)$product['pro_price'], 2); ?></td>
											<td><?php echo intval($product['pro_qty']); ?></td>
											<td><span class="badge badge-<?php echo $product['approval_status'] === 'approved' ? 'success' : ($product['approval_status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars($product['approval_status']); ?></span><?php if (!empty($product['rejection_reason'])): ?><div class="small text-muted mt-1"><?php echo htmlspecialchars($product['rejection_reason']); ?></div><?php endif; ?></td>
											<td><?php echo !empty($product['created_on']) ? htmlspecialchars(datetime_format($product['created_on'], 'd M Y, h:i A')) : '-'; ?></td>
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
