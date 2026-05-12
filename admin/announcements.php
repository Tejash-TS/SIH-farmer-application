<?php
session_start();
include_once('../_functions.php');
require_once('admin_dao.php');
require_once('../ChatAnnouncementDAO.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;
$user_id = intval($_SESSION['user']['user_id']);
$adminDAO = new AdminDAO($conn);
$chatDAO = new ChatAnnouncementDAO($conn);

$message = '';
$message_type = 'info';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $target_role = trim($_POST['target_role'] ?? 'all');
        
        if (empty($title) || empty($description)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'warning';
        } else {
            $result = $chatDAO->createAnnouncement($title, $description, $target_role, $user_id);
            if ($result['status']) {
                $message = 'Announcement created successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to create announcement: ' . ($result['error'] ?? 'Unknown error');
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'delete') {
        $announcement_id = intval($_POST['announcement_id'] ?? 0);
        if ($announcement_id > 0) {
            if ($chatDAO->deleteAnnouncement($announcement_id)) {
                $message = 'Announcement deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to delete announcement.';
                $message_type = 'danger';
            }
        }
    }
}

// Get all announcements
$announcements = $chatDAO->getAllAnnouncements(100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Announcements Management</title>
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
                    <div class="col-sm-6">
                        <h1 class="m-0">Announcements</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Announcements</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Create Announcement</h3>
                            </div>
                            <form method="POST" class="form-horizontal">
                                <div class="card-body">
                                    <input type="hidden" name="action" value="create">
                                    
                                    <div class="form-group">
                                        <label for="title">Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" placeholder="Announcement title" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description *</label>
                                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Announcement details" required></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="target_role">Target Audience</label>
                                        <select class="form-control" id="target_role" name="target_role">
                                            <option value="all">All Users</option>
                                            <option value="farmer">Farmers Only</option>
                                            <option value="buyer">Buyers Only</option>
                                            <option value="consultant">Consultants Only</option>
                                            <option value="vendor">Vendors Only</option>
                                            <option value="farmer,buyer">Farmers & Buyers</option>
                                            <option value="farmer,consultant">Farmers & Consultants</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Post Announcement
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">All Announcements</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($announcements)): ?>
                                    <div class="alert alert-info">No announcements yet.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Audience</th>
                                                    <th>Posted By</th>
                                                    <th>Created On</th>
                                                    <th>Read Count</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($announcements as $ann): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars(substr($ann['title'], 0, 30)); ?></strong>
                                                            <?php if (strlen($ann['title']) > 30): ?>...<?php endif; ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars(substr($ann['description'], 0, 50)); ?></small>
                                                            <?php if (strlen($ann['description']) > 50): ?>...<?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                                $roles = $ann['target_role'] === 'all' ? 'All' : $ann['target_role'];
                                                                echo htmlspecialchars($roles);
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($ann['sender_name'] ?? 'System'); ?></td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($ann['created_on'])); ?></td>
                                                        <td>
                                                            <span class="badge badge-primary"><?php echo $ann['read_count']; ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($ann['is_active']): ?>
                                                                <span class="badge badge-success">Active</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-danger">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="announcement_id" value="<?php echo $ann['announcement_id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include_once('_footer.php'); ?>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.min.js"></script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
