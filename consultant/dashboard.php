<?php
session_start();
include_once('../_functions.php');
include_once('consultant_dao.php');
global $conn;

if (!isset($_SESSION['user'])) {
	header('location:../login');
	exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$consultant = get_consultant_profile($user_id);

if (!$consultant) {
	$stats = [];
	$videos = [];
} else {
	$stats = get_consultant_stats($user_id);
	$videos = get_consultant_videos($user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Dashboard - Consultant</title>
	<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
	<link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
	<link rel="stylesheet" href="assets/dist/css/custom.css">
	<style>
		.status-badge {
			font-weight: bold;
			padding: 0.5rem 1rem;
			border-radius: 4px;
		}
		.status-pending {
			background-color: #fff3cd;
			color: #856404;
		}
		.status-approved {
			background-color: #d4edda;
			color: #155724;
		}
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
	<?php include_once('_header.php'); ?>
	<?php include_once('_sidebar.php'); ?>
	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-6"><h1 class="m-0">Dashboard</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item active">Home</li></ol></div>
				</div>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<?php if (!$consultant): ?>
					<div class="alert alert-info">
						<i class="fas fa-info-circle"></i> Please complete your consultant profile to get started.
						<a href="profile" class="btn btn-sm btn-info float-right">Complete Profile</a>
					</div>
				<?php else: ?>
					<?php if ($consultant['verification_status'] === 'pending'): ?>
					<div class="alert alert-warning alert-dismissible fade show">
						<i class="fas fa-exclamation-triangle"></i> Your profile is pending admin approval. You can view and edit your profile, but video uploads will be available once approved.
						<button type="button" class="close" data-dismiss="alert">&times;</button>
					</div>
					<?php elseif ($consultant['verification_status'] === 'rejected'): ?>
					<div class="alert alert-danger alert-dismissible fade show">
						<i class="fas fa-times-circle"></i> Your profile was rejected. Please review and update your information.
						<a href="profile" class="btn btn-sm btn-danger float-right">Update Profile</a>
						<button type="button" class="close" data-dismiss="alert">&times;</button>
					</div>
					<?php elseif ($consultant['verification_status'] === 'approved'): ?>
					<div class="alert alert-success alert-dismissible fade show">
						<i class="fas fa-check-circle"></i> Your profile is verified! You can now upload educational videos.
						<button type="button" class="close" data-dismiss="alert">&times;</button>
					</div>
					<?php endif; ?>
					<!-- Profile Status -->
					<div class="row">
						<div class="col-md-12 mb-3">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col-md-3 text-center">
											<?php if (!empty($consultant['profile_image'])): ?>
												<img src="<?php echo htmlspecialchars($consultant['profile_image']); ?>" alt="Profile" class="img-circle elevation-2" style="width: 150px; height: 150px; object-fit: cover;">
											<?php else: ?>
												<img src="assets/dist/img/user2-160x160.jpg" alt="Profile" class="img-circle elevation-2" style="width: 150px; height: 150px; object-fit: cover;">
											<?php endif; ?>  
										</div>
										<div class="col-md-9">  
											<h5>Profile Status</h5>
											<p><strong>Name:</strong> <?php echo htmlspecialchars($consultant['user_name']); ?></p>
											<p><strong>Email:</strong> <?php echo htmlspecialchars($consultant['email']); ?></p>
											<p><strong>Specialization:</strong> <?php echo htmlspecialchars($consultant['specialization']); ?></p>
											<p><strong>Degree:</strong> <?php echo htmlspecialchars($consultant['degree']); ?></p>
											<p><strong>License No:</strong> <?php echo htmlspecialchars($consultant['license_no']); ?></p>
											<p><strong>Verification Status:</strong> 
												<span class="status-badge status-<?php echo $consultant['verification_status']; ?>">
													<?php echo ucfirst(htmlspecialchars($consultant['verification_status'])); ?>
												</span>
											</p>
											<div class="mt-3">
												<a href="./consultant/profile" class="btn btn-primary btn-sm">
													<i class="fas fa-edit"></i> Edit Profile
												</a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Statistics -->
					<div class="row">
						<div class="col-md-3 col-sm-6 col-12">
							<div class="info-box">
								<span class="info-box-icon bg-info elevation-3"><i class="fas fa-video"></i></span>
								<div class="info-box-content">
									<span class="info-box-text">Total Videos</span>
									<span class="info-box-number"><?php echo $stats['total_videos'] ?? 0; ?></span>
								</div>
							</div>
						</div>
						<div class="col-md-3 col-sm-6 col-12">
							<div class="info-box">
								<span class="info-box-icon bg-success elevation-3"><i class="fas fa-check"></i></span>
								<div class="info-box-content">
									<span class="info-box-text">Approved</span>
									<span class="info-box-number"><?php echo $stats['approved_videos'] ?? 0; ?></span>
								</div>
							</div>
						</div>
						<div class="col-md-3 col-sm-6 col-12">
							<div class="info-box">
								<span class="info-box-icon bg-warning elevation-3"><i class="fas fa-clock"></i></span>
								<div class="info-box-content">
									<span class="info-box-text">Pending</span>
									<span class="info-box-number"><?php echo $stats['pending_videos'] ?? 0; ?></span>
								</div>
							</div>
						</div>
						<div class="col-md-3 col-sm-6 col-12">
							<div class="info-box">
								<span class="info-box-icon bg-primary elevation-3"><i class="fas fa-upload"></i></span>
								<div class="info-box-content">
									<span class="info-box-text">Upload Video</span>
									<a href="consultant/upload_video" class="info-box-number" style="text-decoration: none; color: inherit;">
										<i class="fas fa-plus"></i>
									</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Recent Videos -->
					<div class="card shadow-sm">
						<div class="card-header">
							<h3 class="card-title">Recent Videos</h3>
							<div class="card-tools">
								<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
							</div>
						</div>
						<div class="card-body p-0">
							<?php if (empty($videos)): ?>
								<div class="alert alert-info m-3">
									<i class="fas fa-info-circle"></i> You haven't uploaded any videos yet.
									<a href="consultant/upload_video" class="btn btn-sm btn-info float-right">Upload Video</a>
								</div>
							<?php else: ?>
								<table class="table table-striped table-hover mb-0">
									<thead>
										<tr>
											<th>Title</th>
											<th>Status</th>
											<th>Uploaded On</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach (array_slice($videos, 0, 10) as $video): ?>
										<tr>
											<td><?php echo htmlspecialchars($video['title']); ?></td>
											<td>
												<span class="badge badge-<?php echo $video['approval_status'] === 'approved' ? 'success' : 'warning'; ?>">
													<?php echo htmlspecialchars($video['approval_status']); ?>
												</span>
											</td>
											<td><?php echo !empty($video['created_on']) ? htmlspecialchars(datetime_format($video['created_on'], 'd M Y, h:i A')) : '-'; ?></td>
											<td>
												<a href="consultant/videos" class="btn btn-sm btn-info">
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
				<?php endif; ?>
			</div>
		</section>
	</div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>

<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
