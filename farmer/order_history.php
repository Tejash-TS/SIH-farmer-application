<?php
session_start();
include_once('../_functions.php');
include_once('order_history_dao.php');
global $conn;

if (!isset($_SESSION['user'])) {
	header('location:../login');
	exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$orders = get_farmer_order_history($user_id);
$stats = get_farmer_order_stats($user_id);
$recent_orders = get_farmer_recent_orders($user_id, 5);
$favorite_vendors = get_farmer_favorite_vendors($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Order History - Farmer</title>
	<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
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
					<div class="col-sm-6"><h1 class="m-0">Order History</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="farmer/dashboard">Home</a></li><li class="breadcrumb-item active">Order History</li></ol></div>
				</div>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<!-- Statistics Cards -->
				<div class="row">
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-info elevation-3"><i class="fas fa-shopping-bag"></i></span>
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
								<span class="info-box-text">Total Spent</span>
								<span class="info-box-number">₹<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-warning elevation-3"><i class="fas fa-box"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Items Purchased</span>
								<span class="info-box-number"><?php echo $stats['total_items_purchased'] ?? 0; ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-primary elevation-3"><i class="fas fa-chart-bar"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Average Order Value</span>
								<span class="info-box-number">₹<?php echo number_format($stats['average_order_value'] ?? 0, 2); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- Order History Table -->
					<div class="col-md-8">
						<div class="card shadow-sm">
							<div class="card-header">
								<h3 class="card-title">Your Orders</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
								</div>
							</div>
							<div class="card-body p-0">
								<?php if (empty($orders)): ?>
									<div class="alert alert-info m-3">
										<i class="fas fa-info-circle"></i> You haven't placed any orders yet. Browse our products to get started!
									</div>
								<?php else: ?>
									<table id="ordersTable" class="table table-striped table-hover mb-0">
										<thead>
											<tr>
												<th>Order ID</th>
												<th>Product</th>
												<th>Vendor</th>
												<th>Qty</th>
												<th>Amount</th>
												<th>Date</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($orders as $order): ?>
												<tr>
													<td><strong>#<?php echo htmlspecialchars($order['purchas_id']); ?></strong></td>
													<td><?php echo htmlspecialchars($order['pro_name']); ?></td>
													<td>
														<?php if (!empty($order['vendor_name'])): ?>
															<small><?php echo htmlspecialchars($order['vendor_name']); ?></small>
														<?php else: ?>
															<small class="text-muted">N/A</small>
														<?php endif; ?>
													</td>
													<td><span class="badge badge-primary"><?php echo intval($order['pro_qty']); ?></span></td>
													<td><strong>₹<?php echo number_format((float)$order['total_amt'], 2); ?></strong></td>
													<td><?php echo !empty($order['created_on']) ? htmlspecialchars(datetime_format($order['created_on'], 'd M Y, h:i A')) : '-'; ?></td>
													<td>
														<a href="javascript:void(0)" onclick="viewOrderDetails(<?php echo $order['purchas_id']; ?>)" class="btn btn-sm btn-info" title="View Details">
															<i class="fas fa-eye"></i>
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

					<!-- Favorite Vendors -->
					<div class="col-md-4">
						<div class="card shadow-sm">
							<div class="card-header">
								<h3 class="card-title">Favorite Vendors</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
								</div>
							</div>
							<div class="card-body">
								<?php if (empty($favorite_vendors)): ?>
									<p class="text-muted text-center">No vendors yet</p>
								<?php else: ?>
									<?php foreach ($favorite_vendors as $vendor): ?>
										<div class="user-block mb-3 pb-3" style="border-bottom: 1px solid #dee2e6;">
											<div class="d-flex justify-content-between align-items-start">
												<div>
													<h6 class="mb-0"><?php echo htmlspecialchars($vendor['company_name'] ?? $vendor['vendor_name']); ?></h6>
													<small class="text-muted">
														<i class="fas fa-shopping-bag"></i> <?php echo $vendor['order_count']; ?> orders
													</small><br>
													<small class="text-success">
														<i class="fas fa-rupee-sign"></i> ₹<?php echo number_format((float)$vendor['total_spent'], 2); ?>
													</small>
												</div>
											</div>
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
	// Fetch order details via AJAX
	$.ajax({
		url: 'farmer/get_order_details.php',
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
						</div>
						<div class="col-md-6">
							<h6><strong>Product Details</strong></h6>
							<p><strong>Product:</strong> ${order.pro_name}</p>
							<p><strong>Type:</strong> ${order.type}</p>
							<p><strong>Quantity:</strong> ${order.pro_qty}</p>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col-md-12">
							<h6><strong>Vendor Information</strong></h6>
							<p><strong>Vendor:</strong> ${order.vendor_name || 'N/A'}</p>
							<p><strong>Email:</strong> ${order.vendor_email || 'N/A'}</p>
							<p><strong>Phone:</strong> ${order.vendor_phone || 'N/A'}</p>
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
	
	$('#orderDetailsModal').modal('show');
}
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
