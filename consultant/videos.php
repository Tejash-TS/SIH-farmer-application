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
$message = '';
$message_type = 'info';

// Get consultant profile
$consultant = get_consultant_profile($user_id);

if (!$consultant) {
	header('location:profile');
	exit;
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
	$video_id = intval($_POST['video_id'] ?? 0);
	
	if ($video_id > 0) {
		$query = "DELETE FROM video_tutorial WHERE video_tutorial_id = ? AND uploaded_by = ?";
		$stmt = $conn->prepare($query);
		if ($stmt) {
			$stmt->bind_param('ii', $video_id, $user_id);
			if ($stmt->execute()) {
				$message = 'Video deleted successfully.';
				$message_type = 'success';
			}
			$stmt->close();
		}
	}
}

$stats = get_consultant_stats($user_id);
$videos = get_consultant_videos($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My Videos - Consultant</title>
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
					<div class="col-sm-6"><h1 class="m-0">My Videos</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="consultant/dashboard">Home</a></li><li class="breadcrumb-item active">Videos</li></ol></div>
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
								<span class="info-box-text">
									<a href="consultant/upload_video" style="color: inherit; text-decoration: none;">
										<i class="fas fa-plus"></i> Upload
									</a>
								</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Videos Table -->
				<div class="card shadow-sm">
					<div class="card-header">
						<h3 class="card-title">Your Videos</h3>
						<div class="card-tools">
							<a href="consultant/upload_video" class="btn btn-sm btn-primary">
								<i class="fas fa-plus"></i> Upload New Video
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<?php if (empty($videos)): ?>
							<div class="alert alert-info m-3">
								<i class="fas fa-info-circle"></i> You haven't uploaded any videos yet.
								<a href="upload_video" class="btn btn-sm btn-info float-right">Upload Video</a>
							</div>
						<?php else: ?>
							<div class="row">
								<?php foreach ($videos as $video): ?>
								<div class="col-md-4 col-sm-6 col-12 mb-3">
									<div class="card h-100">
										<div class="position-relative">
											<?php if (!empty($video['thumbnail'])): ?>
												<img src="../<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
											<?php else: ?>
												<div class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 200px;">
													<i class="fas fa-video fa-3x"></i>
												</div>
											<?php endif; ?>
											<span class="badge badge-<?php echo $video['approval_status'] === 'approved' ? 'success' : ($video['approval_status'] === 'rejected' ? 'danger' : 'warning'); ?> position-absolute" style="top: 10px; right: 10px;">
												<?php echo htmlspecialchars($video['approval_status']); ?>
											</span>
										</div>
										<div class="card-body">
											<h5 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h5>
											<p class="card-text small text-muted">
												<?php echo htmlspecialchars(substr($video['description'] ?? '', 0, 80)); ?><?php echo strlen($video['description'] ?? '') > 80 ? '...' : ''; ?>
											</p>
											<small class="text-muted d-block">
												<i class="fas fa-calendar"></i> <?php echo !empty($video['created_on']) ? htmlspecialchars(datetime_format($video['created_on'], 'd M Y')) : '-'; ?>
											</small>
										</div>
										<div class="card-footer bg-white border-top">
											<a href="../<?php echo htmlspecialchars($video['video']); ?>" target="_blank" class="btn btn-sm btn-info" title="Watch Video">
												<i class="fas fa-play-circle"></i> Watch
											</a>
											<form method="POST" style="display: inline;">
												<input type="hidden" name="video_id" value="<?php echo $video['video_tutorial_id']; ?>">
												<input type="hidden" name="action" value="delete">
												<button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this video?')">
													<i class="fas fa-trash"></i> Delete
												</button>
											</form>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
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
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script>
$(function() {
	// Card-based layout, no DataTable needed
});
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
