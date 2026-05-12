<?php
session_start();
include_once('../_functions.php');
include_once('./admin_dao.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("location:../login");
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;

$adminDAO = new AdminDAO($conn);

// Handle video approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $video_id = intval($_POST['video_id'] ?? 0);
    $admin_id = $_SESSION['user']['user_id'];

    if ($action === 'approve' && $video_id) {
        $adminDAO->approveVideo($video_id, $admin_id);
        header("location:admin/video_management.php?status=approved");
        exit;
    } elseif ($action === 'reject' && $video_id) {
        $reason = $_POST['reason'] ?? '';
        $adminDAO->rejectVideo($video_id, $reason);
        header("location:admin/video_management.php?status=rejected");
        exit;
    }
}

$pending_videos = $adminDAO->getPendingVideos();
$status_message = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Video Management - Admin</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .video-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .video-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .video-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .badge-pending {
            background-color: #ffc107;
        }
        .action-buttons {
            gap: 10px;
        }
        .approval-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
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
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-video"></i> Video Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin">Home</a></li>
                            <li class="breadcrumb-item active">Video Management</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($status_message === 'approved'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> Video approved successfully!
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php elseif ($status_message === 'rejected'): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-times-circle"></i> Video rejected!
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title">
                                    <i class="fas fa-hourglass-half"></i> Pending Video Approvals
                                    <span class="badge badge-light"><?php echo count($pending_videos); ?></span>
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pending_videos)): ?>
                                    <div class="alert alert-info" role="alert">
                                        <i class="fas fa-info-circle"></i> No pending videos for approval.
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($pending_videos as $video): ?>
                                            <div class="col-md-6 col-lg-4 mb-4">
                                                <div class="video-card">
                                                    <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" 
                                                         alt="<?php echo htmlspecialchars($video['title']); ?>"
                                                         class="video-thumbnail" onerror="this.src='assets/dist/img/placeholder.png'">
                                                    
                                                    <div class="p-3">
                                                        <h5 class="card-title">
                                                            <?php echo htmlspecialchars(substr($video['title'], 0, 50)); ?>
                                                        </h5>
                                                        <p class="card-text text-muted small">
                                                            by <strong><?php echo htmlspecialchars($video['uploaded_by_name']); ?></strong>
                                                        </p>
                                                        <p class="card-text small">
                                                            <?php echo htmlspecialchars(substr($video['description'], 0, 80)); ?>...
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar"></i> 
                                                            <?php echo date('d M Y', strtotime($video['created_on'])); ?>
                                                        </small>
                                                        
                                                        <div class="mt-3 action-buttons d-flex">
                                                            <form method="POST" style="flex: 1;">
                                                                <input type="hidden" name="video_id" value="<?php echo $video['video_tutorial_id']; ?>">
                                                                <input type="hidden" name="action" value="approve">
                                                                <button type="submit" class="btn btn-success btn-sm w-100">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                            </form>
                                                            
                                                            <button type="button" class="btn btn-danger btn-sm ml-2" 
                                                                    onclick="openRejectModal(<?php echo $video['video_tutorial_id']; ?>)">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="approval-modal">
        <div class="modal-content">
            <span class="close" onclick="closeRejectModal()">&times;</span>
            <h4><i class="fas fa-times-circle"></i> Reject Video</h4>
            <form method="POST">
                <input type="hidden" id="reject_video_id" name="video_id">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label for="reason">Rejection Reason (optional):</label>
                    <textarea class="form-control" id="reason" name="reason" rows="4" 
                              placeholder="Enter reason for rejection..."></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Video</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>

<script>
function openRejectModal(videoId) {
    document.getElementById('reject_video_id').value = videoId;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('reason').value = '';
}

window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
