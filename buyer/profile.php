<?php
session_start();
include_once('../_functions.php');
require_once('buyer_dao.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$status = $_GET['status'] ?? '';
$message = '';
$message_type = 'info';

function handle_buyer_profile_photo_upload($file, $user_id)
{
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $upload_dir = '../assets/dist/img/buyer_profiles/';

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
    $filename = 'buyer_' . $user_id . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    $db_path = 'assets/dist/img/buyer_profiles/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }

    return ['success' => true, 'path' => $db_path];
}

$buyerDAO = new BuyerDAO($conn);
$buyer = $buyerDAO->getBuyerProfile($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $profile_photo_path = null;

    if ($phone_number !== '' && !preg_match('/^[0-9]{10}$/', $phone_number)) {
        $message = 'Phone number must be 10 digits.';
        $message_type = 'warning';
    } else {
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload_result = handle_buyer_profile_photo_upload($_FILES['profile_photo'], $user_id);
            if (!$upload_result['success']) {
                $message = $upload_result['message'];
                $message_type = 'warning';
            } else {
                $profile_photo_path = $upload_result['path'];
            }
        }

        if ($message_type === 'warning') {
            $saved = false;
        } elseif ($buyer) {
            $saved = $buyerDAO->updateBuyerProfile(intval($buyer['buyer_id']), $address, $phone_number);
        } else {
            $saved = $buyerDAO->createBuyerProfile($user_id, $address, $phone_number);
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

$buyer = $buyerDAO->getBuyerProfile($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buyer Profile</title>
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
                    <div class="col-sm-6"><h1 class="m-0">My Profile</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="buyer/dashboard">Home</a></li>
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
                        Profile updated successfully.
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
                                <h4 class="mb-1"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Buyer'); ?></h4>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></p>
                                <span class="badge badge-info">Buyer</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white"><h3 class="card-title mb-0">Update Buyer Details</h3></div>
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="text" id="phone_number" name="phone_number" class="form-control" maxlength="10" value="<?php echo htmlspecialchars($buyer['phone_number'] ?? ''); ?>" placeholder="10-digit mobile number">
                                    </div>

                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea id="address" name="address" class="form-control" rows="4" placeholder="Enter your delivery address"><?php echo htmlspecialchars($buyer['address'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="profile_photo">Profile Photo</label>
                                        <div class="custom-file">
                                            <input type="file" id="profile_photo" name="profile_photo" class="custom-file-input" accept="image/*">
                                            <label class="custom-file-label" for="profile_photo">Choose image</label>
                                        </div>
                                        <small class="form-text text-muted">JPG, PNG, GIF, or WebP. Max size: 5MB.</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
                                    <a href="buyer/dashboard" class="btn btn-secondary">Cancel</a>
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
