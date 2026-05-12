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

if ($consultant['verification_status'] !== 'approved') {
	$message = 'Your profile must be approved by admin before uploading videos.';
	$message_type = 'warning';
	$can_upload = false;
} else {
	$can_upload = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_upload) {
	$title = trim($_POST['title'] ?? '');
	$description = trim($_POST['description'] ?? '');

	if (empty($title)) {
		$message = 'Video title is required.';
		$message_type = 'warning';
	} else {
		$video_file = null;
		$thumbnail_file = null;
		$errors = [];

		// Handle video file upload
		if (empty($_FILES['video_file']['name'])) {
			$errors[] = 'Video file is required.';
		} else {
			$allowed_video_types = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
			$max_video_size = 500 * 1024 * 1024; // 500MB

			if (!in_array($_FILES['video_file']['type'], $allowed_video_types)) {
				$errors[] = 'Only MP4, WebM, MOV, and AVI video formats are allowed.';
			} elseif ($_FILES['video_file']['size'] > $max_video_size) {
				$errors[] = 'Video size must not exceed 500MB.';
			} else {
				$upload_dir = '../Video_tutorials/';
				if (!is_dir($upload_dir)) {
					mkdir($upload_dir, 0755, true);
				}

				$file_ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
				$filename = 'video_' . $user_id . '_' . time() . '.' . $file_ext;
				$filepath = $upload_dir . $filename;

				if (move_uploaded_file($_FILES['video_file']['tmp_name'], $filepath)) {
					$video_file = 'Video_tutorials/' . $filename;
				} else {
					$errors[] = 'Failed to upload video file.';
				}
			}
		}

		// Handle thumbnail upload
		if (!empty($_FILES['thumbnail']['name'])) {
			$allowed_thumb_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
			$max_thumb_size = 5 * 1024 * 1024; // 5MB

			if (!in_array($_FILES['thumbnail']['type'], $allowed_thumb_types)) {
				$errors[] = 'Thumbnail must be JPG, PNG, GIF, or WebP format.';
			} elseif ($_FILES['thumbnail']['size'] > $max_thumb_size) {
				$errors[] = 'Thumbnail size must not exceed 5MB.';
			} else {
				$thumb_upload_dir = '../Thumbnail/';
				if (!is_dir($thumb_upload_dir)) {
					mkdir($thumb_upload_dir, 0755, true);
				}

				$thumb_ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
				$thumb_filename = 'thumb_' . $user_id . '_' . time() . '.' . $thumb_ext;
				$thumb_filepath = $thumb_upload_dir . $thumb_filename;

				if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumb_filepath)) {
					$thumbnail_file = 'Thumbnail/' . $thumb_filename;
				} else {
					$errors[] = 'Failed to upload thumbnail.';
				}
			}
		} else {
			// Generate default thumbnail (optional - set to null for now)
			$thumbnail_file = null;
		}

		// If no errors, insert into database
		if (empty($errors) && $video_file) {
			$query = "INSERT INTO video_tutorial (title, description, video, thumbnail, uploaded_by, approval_status, is_active, created_on, created_by)
			VALUES (?, ?, ?, ?, ?, 'pending', 'Y', NOW(), ?)";
			
			$stmt = $conn->prepare($query);
			if ($stmt) {
				$stmt->bind_param('ssssii', $title, $description, $video_file, $thumbnail_file, $user_id, $user_id);
				if ($stmt->execute()) {
					$message = 'Video uploaded successfully! Waiting for admin approval.';
					$message_type = 'success';
					$title = '';
					$description = '';
				} else {
					$message = 'Failed to save video to database.';
					$message_type = 'danger';
				}
				$stmt->close();
			}
		} elseif (!empty($errors)) {
			$message = implode('<br>', $errors);
			$message_type = 'warning';
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
	<title>Upload Video - Consultant</title>
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
					<div class="col-sm-6"><h1 class="m-0">Upload Video</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="consultant/dashboard">Home</a></li><li class="breadcrumb-item active">Upload Video</li></ol></div>
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

				<div class="row">
					<div class="col-md-8">
						<div class="card shadow-sm">
							<div class="card-header">
								<h3 class="card-title">Upload Educational Video</h3>
							</div>
							<div class="card-body">
								<?php if (!$can_upload): ?>
									<div class="alert alert-warning">
										<i class="fas fa-exclamation-triangle"></i> Your profile is pending approval. You can upload videos once your profile is verified by the admin.
									</div>
								<?php else: ?>
							<form method="POST" enctype="multipart/form-data">
										<div class="form-group">
											<label for="title">Video Title *</label>
											<input type="text" class="form-control" id="title" name="title" placeholder="Enter video title" required value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
											<small class="form-text text-muted">A clear, descriptive title for your video</small>
										</div>

										<div class="form-group">
											<label for="description">Description</label>
											<textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe the video content, learning objectives, and key topics..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
											<small class="form-text text-muted">Optional: Provide details about what viewers will learn</small>
										</div>

										<div class="form-group">
											<label for="video_file">Video File * (MP4, WebM, MOV, AVI)</label>
											<div class="custom-file">
												<input type="file" class="custom-file-input" id="video_file" name="video_file" accept="video/*" required>
												<label class="custom-file-label" for="video_file">Choose video file</label>
											</div>
											<small class="form-text text-muted">Max size: 500MB. Supported: MP4, WebM, MOV, AVI</small>
										</div>

										<div class="form-group">
											<label for="thumbnail">Thumbnail Image (JPG, PNG, GIF, WebP)</label>
											<div class="custom-file">
												<input type="file" class="custom-file-input" id="thumbnail" name="thumbnail" accept="image/*">
												<label class="custom-file-label" for="thumbnail">Choose thumbnail image</label>
											</div>
											<small class="form-text text-muted">Optional: Max size: 5MB. Recommended size: 1280x720px</small>
										</div>

										<div class="form-group">
											<button type="submit" class="btn btn-primary">
												<i class="fas fa-upload"></i> Upload Video
											</button>
											<a href="videos" class="btn btn-secondary">My Videos</a>
										</div>
									</form>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="col-md-4">
						<div class="card shadow-sm">
							<div class="card-header">
								<h3 class="card-title">Guidelines</h3>
							</div>
							<div class="card-body">
								<h6>Video Upload Tips:</h6>
								<ul class="small">
									<li>Keep videos clear and focused</li>
									<li>Ensure good audio quality</li>
									<li>Provide accurate, relevant content</li>
									<li>Use descriptive titles and descriptions</li>
									<li>Include subtitles if possible</li>
									<li>Keep videos between 5-30 minutes</li>
								</ul>
								<hr>
								<p class="small text-muted">
									All videos will be reviewed and approved by admin before being published to farmers.
								</p>
							</div>
						</div>
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
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
