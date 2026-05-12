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
$consultant = get_consultant_profile($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = [
		'specialization' => trim($_POST['specialization'] ?? ''),
		'degree' => trim($_POST['degree'] ?? ''),
		'bio' => trim($_POST['bio'] ?? ''),
		'license_no' => trim($_POST['license_no'] ?? '')
	];

	// Handle profile image upload
	$profile_image = null;
	if (!empty($_FILES['profile_image']['name'])) {
		$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$max_size = 5 * 1024 * 1024; // 5MB

		if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
			$message = 'Only JPG, PNG, GIF, and WebP images are allowed.';
			$message_type = 'warning';
		} elseif ($_FILES['profile_image']['size'] > $max_size) {
			$message = 'Image size must not exceed 5MB.';
			$message_type = 'warning';
		} else {
			// Create directory if it doesn't exist
			$upload_dir = '../assets/dist/img/consultant_profiles/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0755, true);
			}

			$file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
			$filename = 'consultant_' . $user_id . '_' . time() . '.' . $file_ext;
			$filepath = $upload_dir . $filename;

			if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
				$profile_image = 'assets/dist/img/consultant_profiles/' . $filename;
				$data['profile_image'] = $profile_image;
			} else {
				$message = 'Failed to upload profile image.';
				$message_type = 'warning';
			}
		}
	}

	if (empty($data['specialization']) || empty($data['degree']) || empty($data['license_no'])) {
		$message = 'Specialization, Degree, and License Number are required.';
		$message_type = 'warning';
	} else {
		if (!$consultant) {
			// Create new consultant profile
			$consultant_id = create_consultant_profile($user_id, $data);
			if ($consultant_id) {
				$message = 'Profile created successfully! Waiting for admin verification.';
				$message_type = 'success';
				// Refresh consultant data
				$consultant = get_consultant_profile($user_id);
			} else {
				$message = 'Failed to create profile.';
				$message_type = 'danger';
			}
		} else {
			// Update existing profile
			if (update_consultant_profile($consultant['consultant_id'], $data)) {
				$message = 'Profile updated successfully!';
				$message_type = 'success';
				$consultant = get_consultant_profile($user_id);
			} else {
				$message = 'Failed to update profile.';
				$message_type = 'danger';
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
	<title>Consultant Profile</title>
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
					<div class="col-sm-6"><h1 class="m-0">Consultant Profile</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="consultant/dashboard">Home</a></li><li class="breadcrumb-item active">Profile</li></ol></div>
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
								<h3 class="card-title"><?php echo $consultant ? 'Update Profile' : 'Complete Profile'; ?></h3>
							</div>
							<div class="card-body">
								<form method="POST" enctype="multipart/form-data">
									<div class="form-group">
										<label for="profile_image">Profile Image</label>
										<div class="custom-file">
											<input type="file" class="custom-file-input" id="profile_image" name="profile_image" accept="image/*">
											<label class="custom-file-label" for="profile_image">Choose image</label>
										</div>
										<small class="form-text text-muted">JPG, PNG, GIF, or WebP. Max size: 5MB. Optional.</small>
										<?php if ($consultant && !empty($consultant['profile_image'])): ?>
											<div class="mt-2">
												<img src="<?php echo htmlspecialchars($consultant['profile_image']); ?>" alt="Profile" style="max-width: 150px; border-radius: 8px;">
											</div>
										<?php endif; ?>
									</div>

									<div class="form-group">
										<label for="specialization">Specialization</label>
										<input type="text" class="form-control" id="specialization" name="specialization" placeholder="e.g., Crop Management, Plant Diseases" required value="<?php echo $consultant ? htmlspecialchars($consultant['specialization']) : ''; ?>">
										<small class="form-text text-muted">Your area of expertise</small>
									</div>

									<div class="form-group">
										<label for="degree">Degree</label>
										<input type="text" class="form-control" id="degree" name="degree" placeholder="e.g., B.Sc Agriculture, M.Sc Plant Pathology" required value="<?php echo $consultant ? htmlspecialchars($consultant['degree']) : ''; ?>">
										<small class="form-text text-muted">Your highest qualification</small>
									</div>

									<div class="form-group">
										<label for="license_no">License Number</label>
										<input type="text" class="form-control" id="license_no" name="license_no" placeholder="Enter your professional license/registration number" required value="<?php echo $consultant ? htmlspecialchars($consultant['license_no']) : ''; ?>">
										<small class="form-text text-muted">Professional registration or license number</small>
									</div>

									<div class="form-group">
										<label for="bio">Bio/About</label>
										<textarea class="form-control" id="bio" name="bio" rows="5" placeholder="Tell us about your experience and expertise..."><?php echo $consultant ? htmlspecialchars($consultant['bio']) : ''; ?></textarea>
										<small class="form-text text-muted">Optional: Professional background and experience</small>
									</div>

									<div class="form-group">
										<button type="submit" class="btn btn-primary">
											<i class="fas fa-save"></i> <?php echo $consultant ? 'Update Profile' : 'Create Profile'; ?>
										</button>
										<a href="consultant/dashboard" class="btn btn-secondary">Cancel</a>
									</div>
								</form>
							</div>
						</div>
					</div>

					<div class="col-md-4">
						<div class="card shadow-sm">
							<div class="card-header">
								<h3 class="card-title">Account Information</h3>
							</div>
							<div class="card-body">
								<p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user']['name']); ?></p>
								<p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>
								<p><strong>Role:</strong> <span class="badge badge-info">Consultant</span></p>
								<?php if ($consultant): ?>
									<hr>
									<p><strong>Profile Status:</strong></p>
									<span class="badge badge-<?php echo $consultant['verification_status'] === 'approved' ? 'success' : ($consultant['verification_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
										<?php echo ucfirst(htmlspecialchars($consultant['verification_status'])); ?>
									</span>
									<?php if ($consultant['verification_status'] === 'pending'): ?>
										<div class="alert alert-info mt-2 mb-0">
											<small><i class="fas fa-info-circle"></i> Your profile is awaiting admin approval.</small>
										</div>
									<?php elseif ($consultant['verification_status'] === 'approved'): ?>
										<div class="alert alert-success mt-2 mb-0">
											<small><i class="fas fa-check"></i> Your profile is verified! You can now upload videos.</small>
										</div>
									<?php endif; ?>
								<?php endif; ?>
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
