<?php
session_start();
include_once('../_functions.php');
include_once('orders_dao.php');
global $conn;

if (!isset($_SESSION['user'])) {
	header('location:../login');
	exit;
}
  
check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);

// Get vendor info
$stmt = $conn->prepare('SELECT * FROM vendors WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();

if (!$vendor) {
	$vendor_id = 0;
	$orders = [];
	$stats = [];
} else {
	$vendor_id = intval($vendor['vendor_id']);
	$orders = get_vendor_orders($vendor_id);
	$stats = get_vendor_order_stats($vendor_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Orders - Vendor</title>
	<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
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
					<div class="col-sm-6"><h1 class="m-0">Orders</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="vendor/dashboard">Home</a></li><li class="breadcrumb-item active">Orders</li></ol></div>
				</div>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<!-- Statistics Cards -->
				<div class="row">
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-info elevation-3"><i class="fas fa-shopping-cart"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Total Orders</span>
								<span class="info-box-number"><?php echo $stats['total_orders'] ?? 0; ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-success elevation-3"><i class="fas fa-rupee-sign"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Total Revenue</span>
								<span class="info-box-number">₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-warning elevation-3"><i class="fas fa-boxes"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Items Sold</span>
								<span class="info-box-number"><?php echo $stats['total_items_sold'] ?? 0; ?></span>
							</div>
						</div>
					</div>
				</div>

				<!-- Orders Table -->
				<div class="card shadow-sm">
					<div class="card-header">
						<h3 class="card-title">Orders from Customers</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
						</div>
					</div>
					<div class="card-body p-0">
						<?php if (!$vendor_id): ?>
							<div class="alert alert-info m-3">
								<i class="fas fa-info-circle"></i> Please complete your vendor profile to view orders.
							</div>
						<?php elseif (empty($orders)): ?>
							<div class="alert alert-info m-3">
								<i class="fas fa-info-circle"></i> No orders yet. When customers purchase your products, they will appear here.
							</div>
						<?php else: ?>
							<table id="ordersTable" class="table table-striped table-hover mb-0">
								<thead>
									<tr>
										<th>Order ID</th>
										<th>Customer</th>
										<th>Product</th>
										<th>Quantity</th>
										<th>Amount</th>
										<th>Payment Method</th>
										<th>Order Date</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orders as $order): ?>
										<tr>
											<td><strong>#<?php echo htmlspecialchars($order['purchas_id']); ?></strong></td>
											<td>
												<div><?php echo htmlspecialchars($order['user_name']); ?></div>
												<small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
											</td>
											<td><?php echo htmlspecialchars($order['pro_name']); ?></td>
											<td><span class="badge badge-primary"><?php echo intval($order['pro_qty']); ?></span></td>
											<td><strong>₹<?php echo number_format((float)$order['total_amt'], 2); ?></strong></td>
											<td>
												<span class="badge badge-<?php echo strpos($order['payment_method'], 'Online') !== false ? 'info' : 'secondary'; ?>">
													<?php echo htmlspecialchars($order['payment_method']); ?>
												</span>
											</td>
											<td><?php echo !empty($order['created_on']) ? htmlspecialchars(datetime_format($order['created_on'], 'd M Y, h:i A')) : '-'; ?></td>
											<td>
												<a href="javascript:void(0)" onclick="viewOrderDetails(<?php echo $order['purchas_id']; ?>)" class="btn btn-sm btn-info" title="View Details">
													<i class="fas fa-eye"></i> View
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="orderDetailsLabel">Order Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="orderDetailsContent">
				<!-- Content loaded via AJAX -->
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script>
$(function() {
	// Initialize DataTable
	<?php if (!empty($orders)): ?>
	$('#ordersTable').DataTable({
		'paging': true,
		'lengthChange': false,
		'searching': true,
		'ordering': true,
		'info': true,
		'autoWidth': false,
		'responsive': true,
		'pageLength': 10
	});
	<?php endif; ?>
});

function viewOrderDetails(orderId) {
	$('#orderDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
	$('#orderDetailsModal').modal('show');
	
	// Fetch order details via AJAX
	$.ajax({
		url: 'vendor/get_order_details.php',
		type: 'POST',
		data: { order_id: orderId },
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				let order = response.data;
				let html = `
					<div class="row">
						<div class="col-md-6">
							<h6><strong>Order Information</strong></h6>
							<p><strong>Order ID:</strong> #${order.purchas_id}</p>
							<p><strong>Order Date:</strong> ${order.created_on}</p>
							<p><strong>Total Amount:</strong> ₹${parseFloat(order.total_amt).toFixed(2)}</p>
							<p><strong>Payment Method:</strong> ${order.payment_method}</p>
							<p><strong>Transaction ID:</strong> ${order.transaction_id || 'N/A'}</p>
						</div>
						<div class="col-md-6">
							<h6><strong>Product Details</strong></h6>
							<p><strong>Product Name:</strong> ${order.pro_name}</p>
							<p><strong>Type:</strong> ${order.type}</p>
							<p><strong>Quantity:</strong> ${order.pro_qty}</p>
							<p><strong>Description:</strong> ${order.pro_description || 'N/A'}</p>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col-md-12">
							<h6><strong>Customer Information</strong></h6>
							<p><strong>Name:</strong> ${order.user_name}</p>
							<p><strong>Email:</strong> ${order.email}</p>
							<p><strong>Phone:</strong> ${order.mb_number || 'N/A'}</p>
						</div>
					</div>
				`;
				$('#orderDetailsContent').html(html);
			} else {
				$('#orderDetailsContent').html('<div class="alert alert-danger">Failed to load order details</div>');
			}
		},
		error: function() {
			$('#orderDetailsContent').html('<div class="alert alert-danger">Error loading order details</div>');
		}
	});
}
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
