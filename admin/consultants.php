<?php
session_start();
include_once('../_functions.php');
include_once('consultants_dao.php');
global $conn;

if (!isset($_SESSION['user'])) {
	header('location:../login');
	exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$message = '';
$message_type = 'info';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	$action = $_POST['action'];
	$consultant_id = intval($_POST['consultant_id'] ?? 0);
	
	if ($action === 'approve') {
		if (update_consultant_status($consultant_id, 'approved', $user_id)) {
			$message = 'Consultant approved successfully.';
			$message_type = 'success';
		} else {
			$message = 'Failed to approve consultant.';
			$message_type = 'danger';
		}
	} elseif ($action === 'reject') {
		if (update_consultant_status($consultant_id, 'rejected', $user_id)) {
			$message = 'Consultant rejected.';
			$message_type = 'warning';
		} else {
			$message = 'Failed to reject consultant.';
			$message_type = 'danger';
		}
	} elseif ($action === 'delete') {
		if (delete_consultant($consultant_id)) {
			$message = 'Consultant deleted successfully.';
			$message_type = 'success';
		} else {
			$message = 'Failed to delete consultant.';
			$message_type = 'danger';
		}
	} elseif ($action === 'toggle_status') {
		$status = $_POST['status'] ?? 'Y';
		if (toggle_consultant_status($consultant_id, $status)) {
			$message = 'Consultant status updated.';
			$message_type = 'success';
		} else {
			$message = 'Failed to update consultant status.';
			$message_type = 'danger';
		}
	}
}

$consultants = get_all_consultants();
$stats = get_consultant_stats();
$pending = get_pending_consultants();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Consultants Management - Admin</title>
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
					<div class="col-sm-6"><h1 class="m-0">Consultants Management</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="admin/dashboard">Home</a></li><li class="breadcrumb-item active">Consultants</li></ol></div>
				</div>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<?php if ($message): ?>
					<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
						<?php echo htmlspecialchars($message); ?>
						<button type="button" class="close" data-dismiss="alert">&times;</button>
					</div>
				<?php endif; ?>

				<!-- Statistics Cards -->
				<div class="row">
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-info elevation-3"><i class="fas fa-users"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Total Consultants</span>
								<span class="info-box-number"><?php echo $stats['total'] ?? 0; ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-success elevation-3"><i class="fas fa-check"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Approved</span>
								<span class="info-box-number"><?php echo $stats['approved'] ?? 0; ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-warning elevation-3"><i class="fas fa-clock"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Pending</span>
								<span class="info-box-number"><?php echo $stats['pending'] ?? 0; ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-12">
						<div class="info-box">
							<span class="info-box-icon bg-danger elevation-3"><i class="fas fa-times"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Rejected</span>
								<span class="info-box-number"><?php echo $stats['rejected'] ?? 0; ?></span>
							</div>
						</div>
					</div>
				</div>

				<!-- Pending Consultants -->
				<?php if (!empty($pending)): ?>
				<div class="card shadow-sm mb-4">
					<div class="card-header bg-warning">
						<h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Pending Approvals (<?php echo count($pending); ?>)</h3>
					</div>
					<div class="card-body p-0">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Name</th>
									<th>Specialization</th>
									<th>Degree</th>
									<th>License No</th>
									<th>Email</th>
									<th>Applied On</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($pending as $consultant): ?>
								<tr>
									<td><?php echo htmlspecialchars($consultant['user_name']); ?></td>
									<td><?php echo htmlspecialchars($consultant['specialization']); ?></td>
									<td><?php echo htmlspecialchars($consultant['degree']); ?></td>
									<td><?php echo htmlspecialchars($consultant['license_no']); ?></td>
									<td><?php echo htmlspecialchars($consultant['email']); ?></td>
									<td><?php echo !empty($consultant['created_on']) ? htmlspecialchars(datetime_format($consultant['created_on'], 'd M Y')) : '-'; ?></td>
									<td>
										<form method="POST" style="display: inline;">
											<input type="hidden" name="consultant_id" value="<?php echo $consultant['consultant_id']; ?>">
											<button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Approve">
												<i class="fas fa-check"></i>
											</button>
											<button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Are you sure?')">
												<i class="fas fa-times"></i>
											</button>
										</form>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>

				<!-- All Consultants Table -->
				<div class="card shadow-sm">
					<div class="card-header">
						<h3 class="card-title">All Consultants</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
						</div>
					</div>
					<div class="card-body p-0">
						<?php if (empty($consultants)): ?>
							<div class="alert alert-info m-3">
								<i class="fas fa-info-circle"></i> No consultants found.
							</div>
						<?php else: ?>
							<table id="consultantsTable" class="table table-striped table-hover mb-0">
								<thead>
									<tr>
										<th>Name</th>
										<th>Specialization</th>
										<th>Degree</th>
										<th>License No</th>
										<th>Email</th>
										<th>Status</th>
										<th>Active</th>
										<th>Registered</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($consultants as $consultant): ?>
									<tr>
										<td><strong><?php echo htmlspecialchars($consultant['user_name']); ?></strong></td>
										<td><?php echo htmlspecialchars($consultant['specialization']); ?></td>
										<td><?php echo htmlspecialchars($consultant['degree']); ?></td>
										<td><?php echo htmlspecialchars($consultant['license_no']); ?></td>
										<td><?php echo htmlspecialchars($consultant['email']); ?></td>
										<td>
											<span class="badge badge-<?php echo $consultant['verification_status'] === 'approved' ? 'success' : ($consultant['verification_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
												<?php echo htmlspecialchars($consultant['verification_status']); ?>
											</span>
										</td>
										<td>
											<span class="badge badge-<?php echo $consultant['is_active'] === 'Y' ? 'success' : 'secondary'; ?>">
												<?php echo $consultant['is_active'] === 'Y' ? 'Yes' : 'No'; ?>
											</span>
										</td>
										<td><?php echo !empty($consultant['created_on']) ? htmlspecialchars(datetime_format($consultant['created_on'], 'd M Y')) : '-'; ?></td>
										<td>
											<a href="javascript:void(0)" onclick="viewConsultant(<?php echo $consultant['consultant_id']; ?>)" class="btn btn-sm btn-info" title="View">
												<i class="fas fa-eye"></i>
											</a>
											<?php if ($consultant['verification_status'] !== 'approved'): ?>
											<form method="POST" style="display: inline;">
												<input type="hidden" name="consultant_id" value="<?php echo $consultant['consultant_id']; ?>">
												<button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Approve">
													<i class="fas fa-check"></i>
												</button>
											</form>
											<?php endif; ?>
											<form method="POST" style="display: inline;">
												<input type="hidden" name="consultant_id" value="<?php echo $consultant['consultant_id']; ?>">
												<button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
													<i class="fas fa-trash"></i>
												</button>
											</form>
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

<!-- Consultant Details Modal -->
<div class="modal fade" id="consultantModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Consultant Details</h5>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body" id="consultantDetails">
				<!-- Content loaded via AJAX -->
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script>
$(function() {
	$('#consultantsTable').DataTable({
		'paging': true,
		'lengthChange': false,
		'searching': true,
		'ordering': true,
		'info': true,
		'autoWidth': false,
		'responsive': true,
		'pageLength': 25
	});
});

function viewConsultant(consultantId) {
	$('#consultantDetails').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
	$('#consultantModal').modal('show');
}
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
