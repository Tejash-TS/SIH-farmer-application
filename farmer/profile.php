<?php
session_start();
include_once('../_functions.php');
require_once('farmer_dao.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$status = $_GET['status'] ?? '';
$message = '';
$message_type = 'info';

function handle_farmer_profile_photo_upload($file, $user_id)
{
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $upload_dir = '../assets/dist/img/farmer_profiles/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size must be less than 5MB'];
    }

    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, GIF, and WebP files are allowed'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'farmer_' . $user_id . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    $db_path = 'assets/dist/img/farmer_profiles/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }

    return ['success' => true, 'path' => $db_path];
}

$farmerDAO = new FarmerDAO($conn);
$farmer = $farmerDAO->getFarmerProfile($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farm_name = trim($_POST['farm_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $farm_size = trim($_POST['farm_size'] ?? '');
    $crops_grown = trim($_POST['crops_grown'] ?? '');
    $profile_photo_path = null;

    if ($farm_name === '') {
        $message = 'Farm name is required.';
        $message_type = 'warning';
    } elseif ($phone_number !== '' && !preg_match('/^[0-9]{10}$/', $phone_number)) {
        $message = 'Phone number must be 10 digits.';
        $message_type = 'warning';
    } else {
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload_result = handle_farmer_profile_photo_upload($_FILES['profile_photo'], $user_id);
            if (!$upload_result['success']) {
                $message = $upload_result['message'];
                $message_type = 'warning';
            } else {
                $profile_photo_path = $upload_result['path'];
            }
        }

        if ($message_type === 'warning') {
            $saved = false;
        } elseif ($farmer) {
            $saved = $farmerDAO->updateFarmerProfile(
                intval($farmer['farmer_id']),
                $farm_name,
                $location,
                $phone_number,
                $farm_size,
                $crops_grown
            );
        } else {
            $saved = $farmerDAO->createFarmerProfile(
                $user_id,
                $farm_name,
                $location,
                $phone_number,
                $farm_size,
                $crops_grown
            );
        }

        if ($saved) {
            if ($profile_photo_path) {
                $img_stmt = $conn->prepare('UPDATE users SET image = ? WHERE user_id = ?');
                $img_stmt->bind_param('si', $profile_photo_path, $user_id);
                $img_stmt->execute();
                $img_stmt->close();
                $_SESSION['user']['image'] = $profile_photo_path;
            }
            header('location:profile?status=saved');
            exit;
        }

        if ($message_type !== 'warning') {
            $message = 'Unable to save profile. Please try again.';
            $message_type = 'danger';
        }
    }
}

$farmer = $farmerDAO->getFarmerProfile($user_id);
$verification_status = $farmer['verification_status'] ?? 'not_created';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farmer Profile</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
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
                    <div class="col-sm-6">
                        <h1 class="m-0">Farmer Profile</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Profile</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($status === 'saved'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        Farmer profile saved successfully.
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body text-center">
                                <img src="<?php echo !empty($_SESSION['user']['image']) ? htmlspecialchars($_SESSION['user']['image']) : 'assets/dist/img/user2-160x160.jpg'; ?>" class="img-circle elevation-2 mb-3" alt="User Image" style="width:100px;height:100px;object-fit:cover;">
                                <h4 class="mb-1"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Farmer'); ?></h4>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></p>
                                <span class="badge badge-<?php echo $verification_status === 'approved' ? 'success' : ($verification_status === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($verification_status)); ?>
                                </span>
                                <?php if ($verification_status === 'pending'): ?>
                                    <div class="alert alert-info mt-3 mb-0">
                                        <small>Your profile is pending admin approval.</small>
                                    </div>
                                <?php elseif ($verification_status === 'approved'): ?>
                                    <div class="alert alert-success mt-3 mb-0">
                                        <small>Your profile is approved. You can add products.</small>
                                    </div>
                                <?php elseif ($verification_status === 'rejected'): ?>
                                    <div class="alert alert-danger mt-3 mb-0">
                                        <small>Your profile was rejected. Update details and submit again.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h3 class="card-title mb-0"><?php echo $farmer ? 'Update Farm Details' : 'Complete Farm Profile'; ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="farm_name">Farm Name <span class="text-danger">*</span></label>
                                        <input type="text" id="farm_name" name="farm_name" class="form-control" required value="<?php echo htmlspecialchars($farmer['farm_name'] ?? ''); ?>" placeholder="e.g., Green Valley Farm">
                                    </div>

                                    <div class="form-group">
                                        <label for="location">Location</label>
                                        <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($farmer['location'] ?? ''); ?>" placeholder="Village, District, State">
                                    </div>

                                    <div class="form-group">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="text" id="phone_number" name="phone_number" class="form-control" maxlength="10" value="<?php echo htmlspecialchars($farmer['phone_number'] ?? ''); ?>" placeholder="10-digit mobile number">
                                    </div>

                                    <div class="form-group">
                                        <label for="profile_photo">Profile Photo</label>
                                        <div class="custom-file">
                                            <input type="file" id="profile_photo" name="profile_photo" class="custom-file-input" accept="image/*">
                                            <label class="custom-file-label" for="profile_photo">Choose image</label>
                                        </div>
                                        <small class="form-text text-muted">JPG, PNG, GIF, or WebP. Max size: 5MB.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="farm_size">Farm Size</label>
                                        <input type="text" id="farm_size" name="farm_size" class="form-control" value="<?php echo htmlspecialchars($farmer['farm_size'] ?? ''); ?>" placeholder="e.g., 5 acres">
                                    </div>

                                    <div class="form-group">
                                        <label for="crops_grown">Crops Grown</label>
                                        <textarea id="crops_grown" name="crops_grown" class="form-control" rows="4" placeholder="e.g., Wheat, Rice, Tomatoes, Potatoes"><?php echo htmlspecialchars($farmer['crops_grown'] ?? ''); ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $farmer ? 'Update Profile' : 'Create Profile'; ?>
                                    </button>
                                    <a href="farmer/dashboard" class="btn btn-secondary">Cancel</a>
                                    <a href="farmer/add_farm_product" class="btn btn-success float-right">
                                        <i class="fas fa-plus"></i> Add Product
                                    </a>
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
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.min.js"></script>
<script>
document.getElementById('profile_photo').addEventListener('change', function () {
    const label = document.querySelector('label[for="profile_photo"].custom-file-label');
    if (label && this.files[0]) {
        label.textContent = this.files[0].name;
    }
});
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
