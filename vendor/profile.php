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

function vendor_profile_redirect($status)
{
	header('location:profile?status=' . urlencode($status));
	exit;
}

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

$vendor = load_vendor_profile($conn, $user_id);

function handle_profile_photo_upload($file, $user_id)
{
	$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
	$max_size = 5 * 1024 * 1024; // 5MB
	$upload_dir = '../assets/dist/img/vendor_profiles/';

	// Create directory if it doesn't exist
	if (!is_dir($upload_dir)) {
		mkdir($upload_dir, 0755, true);
	}

	// Validate file
	if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
		return ['success' => false, 'message' => 'No file uploaded'];
	}

	if ($file['size'] > $max_size) {
		return ['success' => false, 'message' => 'File size must be less than 5MB'];
	}

	if (!in_array($file['type'], $allowed_types)) {
		return ['success' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed'];
	}

	// Generate unique filename
	$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
	$filename = 'vendor_' . $user_id . '_' . time() . '.' . $ext;
	$filepath = $upload_dir . $filename;
	$db_path = 'assets/dist/img/vendor_profiles/' . $filename;

	// Move uploaded file
	if (!move_uploaded_file($file['tmp_name'], $filepath)) {
		return ['success' => false, 'message' => 'Failed to save file'];
	}

	return ['success' => true, 'path' => $db_path];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$company_name = trim($_POST['company_name'] ?? '');
	$license_no = trim($_POST['license_no'] ?? '');
	$location = trim($_POST['location'] ?? '');
	$phone_number = trim($_POST['phone_number'] ?? '');
	$profile_photo_path = null;

	if ($company_name === '' || $license_no === '') {
		$message = 'Company name and license number are required.';
		$message_type = 'warning';
	} else {
		// Handle photo upload if file is present
		if (!empty($_FILES['profile_photo']['name'])) {
			$upload_result = handle_profile_photo_upload($_FILES['profile_photo'], $user_id);
			if (!$upload_result['success']) {
				$message = $upload_result['message'];
				$message_type = 'warning';
			} else {
				$profile_photo_path = $upload_result['path'];
			}
		}

		// If no upload error, proceed with profile update
		if ($message_type !== 'warning') {
			if ($vendor) {
				if ($profile_photo_path) {
					$stmt = $conn->prepare('UPDATE vendors SET company_name = ?, license_no = ?, location = ?, phone_number = ?, modified_on = NOW(), modified_by = ? WHERE vendor_id = ?');
					$stmt->bind_param('ssssii', $company_name, $license_no, $location, $phone_number, $user_id, $vendor['vendor_id']);
				} else {
					$stmt = $conn->prepare('UPDATE vendors SET company_name = ?, license_no = ?, location = ?, phone_number = ?, modified_on = NOW(), modified_by = ? WHERE vendor_id = ?');
					$stmt->bind_param('ssssii', $company_name, $license_no, $location, $phone_number, $user_id, $vendor['vendor_id']);
				}
			} else {
				$stmt = $conn->prepare('INSERT INTO vendors (user_id, company_name, license_no, location, phone_number, created_on, created_by) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
				$stmt->bind_param('issssi', $user_id, $company_name, $license_no, $location, $phone_number, $user_id);
			}

			if ($stmt && $stmt->execute()) {
				$stmt->close();
				
				// Update user image if photo was uploaded
				if ($profile_photo_path) {
					$update_user = $conn->prepare('UPDATE users SET image = ? WHERE user_id = ?');
					$update_user->bind_param('si', $profile_photo_path, $user_id);
					$update_user->execute();
					$update_user->close();
					$_SESSION['user']['image'] = $profile_photo_path;
				}
				
				vendor_profile_redirect('saved');
			}

			$message = 'Unable to save profile. Please try again.';
			$message_type = 'danger';
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
	<title>Vendor Profile</title>
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
					<div class="col-sm-6"><h1 class="m-0">Vendor Profile</h1></div>
					<div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="vendor/dashboard">Home</a></li><li class="breadcrumb-item active">Profile</li></ol></div>
				</div>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<?php if ($status === 'saved'): ?><div class="alert alert-success alert-dismissible fade show">Profile saved successfully.<button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
				<?php if ($message): ?><div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show"><?php echo htmlspecialchars($message); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
				<div class="row">
					<div class="col-lg-4">
						<div class="card shadow-sm">
							<div class="card-body text-center">
								<img src="<?= !empty($_SESSION['user']['image']) ? $_SESSION['user']['image'] : 'assets/dist/img/user2-160x160.jpg' ?>" class="img-circle elevation-2 mb-3" alt="User Image" style="width:100px;height:100px;object-fit:cover;">
								<h4 class="mb-1"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Vendor'); ?></h4>
								<p class="text-muted mb-2"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></p>
								<span class="badge badge-<?php echo $profile_status === 'approved' ? 'success' : ($profile_status === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars($profile_status); ?></span>
							</div>
						</div>
					</div>
					<div class="col-lg-8">
						<div class="card shadow-sm">
							<div class="card-header bg-white"><h3 class="card-title mb-0">Edit Vendor Details</h3></div>
							<div class="card-body">
								<form method="post" enctype="multipart/form-data">
									<div class="form-group"><label>Company Name</label><input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($vendor['company_name'] ?? ''); ?>" required></div>
									<div class="form-group"><label>License Number</label><input type="text" name="license_no" class="form-control" value="<?php echo htmlspecialchars($vendor['license_no'] ?? ''); ?>" required></div>
									<div class="form-group"><label>Location</label><input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($vendor['location'] ?? ''); ?>"></div>
									<div class="form-group"><label>Phone Number</label><input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($vendor['phone_number'] ?? ''); ?>"></div>
									<div class="form-group">
										<label>Profile Photo</label>
										<div class="input-group">
											<div class="custom-file">
												<input type="file" name="profile_photo" class="custom-file-input" id="profile_photo" accept="image/*">
												<label class="custom-file-label" for="profile_photo">Choose file</label>
											</div>
										</div>
										<small class="form-text text-muted">Max 5MB (JPG, PNG, GIF)</small>
									</div>
									<button type="submit" class="btn btn-primary">Save Profile</button>
								</form>
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
<script src="assets/dist/js/custom.js"></script>
<script>
document.getElementById('profile_photo').addEventListener('change', function() {
	const label = document.querySelector('.custom-file-label');
	const filename = this.files[0]?.name || 'Choose file';
	label.textContent = filename;
});
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
