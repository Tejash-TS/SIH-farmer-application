<?php
session_start();
include_once('../_functions.php');
include_once('video_tutorial_dao.php');
global $conn;

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

$user_id = intval($_SESSION['user']['user_id']);
$message = '';
$message_type = 'info';

// FIX #3: Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// FIX #7: Helper to safely format dates with fallback if datetime_format() is unavailable
function safe_datetime_format($datetime, $format = 'd M Y')
{
    if (function_exists('datetime_format')) {
        return datetime_format($datetime, $format);
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $dt ? $dt->format($format) : htmlspecialchars($datetime);
}

// FIX #6: Helper to validate that a file path stays within the allowed upload directory
function is_safe_path($path, $base_dir = 'uploads/')
{
    $real_base = realpath('../' . $base_dir);
    if ($real_base === false) {
        // Fallback: simple prefix check
        return strpos($path, '..') === false && strpos($path, $base_dir) === 0;
    }
    $real_path = realpath('../' . $path);
    return $real_path !== false && strpos($real_path, $real_base) === 0;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // FIX #3: Validate CSRF token before processing any action
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'danger';
    } else {
        $action   = $_POST['action'];

        // FIX #5: Validate video_id is a positive integer before any DB operation
        $video_id = intval($_POST['video_id'] ?? 0);
        if ($video_id <= 0) {
            $message      = 'Invalid video ID.';
            $message_type = 'danger';
        } else {
            if ($action === 'approve') {
                if (update_video_status($video_id, 'approved', $user_id)) {
                    $message      = 'Video approved successfully.';
                    $message_type = 'success';
                } else {
                    $message      = 'Failed to approve video.';
                    $message_type = 'danger';
                }
            } elseif ($action === 'reject') {
                if (update_video_status($video_id, 'rejected', $user_id)) {
                    $message      = 'Video rejected.';
                    $message_type = 'warning';
                } else {
                    $message      = 'Failed to reject video.';
                    $message_type = 'danger';
                }
            } elseif ($action === 'delete') {
                $video = get_video_details($video_id);
                if ($video) {
                    // FIX #4: Delete from DB FIRST, then unlink files only on success
                    // This prevents orphaned filesystem state if the DB delete fails
                    if (delete_video($video_id)) {
                        if (!empty($video['video']) && is_safe_path($video['video']) && file_exists('../' . $video['video'])) {
                            unlink('../' . $video['video']);
                        }
                        if (!empty($video['thumbnail']) && is_safe_path($video['thumbnail']) && file_exists('../' . $video['thumbnail'])) {
                            unlink('../' . $video['thumbnail']);
                        }
                        $message      = 'Video deleted successfully.';
                        $message_type = 'success';
                    } else {
                        $message      = 'Failed to delete video.';
                        $message_type = 'danger';
                    }
                } else {
                    $message      = 'Video not found.';
                    $message_type = 'danger';
                }
            }
        }
    }
}

$pending_videos = get_pending_videos();
$all_videos     = get_all_videos();
$stats          = get_video_stats();

// Shorthand for CSRF token output
$csrf_field = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Video Management - Admin</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .video-thumb { max-width: 100px; height: auto; border-radius: 4px; }
        .status-badge { font-weight: bold; padding: 0.5rem 1rem; border-radius: 4px; }
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
                    <div class="col-sm-6"><h1 class="m-0">Video Management</h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin/dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Videos</li>
                        </ol>
                    </div>
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
                            <span class="info-box-icon bg-danger elevation-3"><i class="fas fa-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Rejected</span>
                                <span class="info-box-number"><?php echo $stats['rejected_videos'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Videos -->
                <?php if (!empty($pending_videos)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">Pending Video Approvals</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="row">
                            <?php foreach ($pending_videos as $video): ?>
                            <div class="col-md-6 col-lg-4 p-3">
                                <div class="card">
                                    <div class="position-relative">
                                        <?php if (!empty($video['thumbnail'])): ?>
                                            <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>"
                                                 alt="<?php echo htmlspecialchars($video['title']); ?>"
                                                 class="card-img-top"
                                                 style="height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 150px;">
                                                <i class="fas fa-video fa-2x"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="badge badge-warning position-absolute" style="top: 10px; right: 10px;">Pending</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($video['title']); ?></h6>
                                        <small class="text-muted d-block mb-2">
                                            By: <?php echo htmlspecialchars($video['user_name']); ?><br>
                                            <?php echo htmlspecialchars(safe_datetime_format($video['created_on'], 'd M Y')); ?>
                                        </small>
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <!-- FIX #3: CSRF token added to all forms -->
                                            <form method="POST" style="flex: 1;">
                                                <?php echo $csrf_field; ?>
                                                <input type="hidden" name="video_id" value="<?php echo intval($video['video_tutorial_id']); ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm w-100" title="Approve">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="flex: 1;">
                                                <?php echo $csrf_field; ?>
                                                <input type="hidden" name="video_id" value="<?php echo intval($video['video_tutorial_id']); ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm w-100" title="Reject"
                                                        onclick="return confirm('Reject this video?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- All Videos -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">All Videos</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($all_videos)): ?>
                            <div class="alert alert-info m-3">
                                <i class="fas fa-info-circle"></i> No videos available.
                            </div>
                        <?php else: ?>
                            <table id="videosTable" class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Thumbnail</th>
                                        <th>Title</th>
                                        <th>Uploaded By</th>
                                        <th>Status</th>
                                        <th>Uploaded On</th>
                                        <th style="width: 160px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_videos as $video): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($video['thumbnail'])): ?>
                                                <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>"
                                                     alt="Thumbnail" class="video-thumb">
                                            <?php else: ?>
                                                <div class="bg-dark text-white d-flex align-items-center justify-content-center"
                                                     style="width: 80px; height: 60px; border-radius: 4px;">
                                                    <i class="fas fa-video"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($video['title']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php
                                                $desc = $video['description'] ?? '';
                                                echo htmlspecialchars(substr($desc, 0, 60)) . (strlen($desc) > 60 ? '...' : '');
                                                ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($video['user_name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $video['approval_status'] === 'approved' ? 'success'
                                                   : ($video['approval_status'] === 'rejected' ? 'danger' : 'warning');
                                            ?>">
                                                <?php echo htmlspecialchars($video['approval_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(safe_datetime_format($video['created_on'], 'd M Y, h:i A')); ?></td>
                                        <td>
                                            <!-- FIX #6: Validate path before rendering as href -->
                                            <?php if (!empty($video['video']) && is_safe_path($video['video'])): ?>
                                                <a href="<?php echo htmlspecialchars($video['video']); ?>"
                                                   target="_blank" class="btn btn-sm btn-info" title="Watch">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled title="Video unavailable">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($video['approval_status'] === 'pending'): ?>
                                                <!-- FIX #3: CSRF token on approve -->
                                                <form method="POST" style="display: inline;">
                                                    <?php echo $csrf_field; ?>
                                                    <input type="hidden" name="video_id" value="<?php echo intval($video['video_tutorial_id']); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <!-- FIX #3: CSRF token on reject -->
                                                <form method="POST" style="display: inline;">
                                                    <?php echo $csrf_field; ?>
                                                    <input type="hidden" name="video_id" value="<?php echo intval($video['video_tutorial_id']); ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Reject"
                                                            onclick="return confirm('Reject this video?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- FIX #3: CSRF token on delete -->
                                            <form method="POST" style="display: inline;">
                                                <?php echo $csrf_field; ?>
                                                <input type="hidden" name="video_id" value="<?php echo intval($video['video_tutorial_id']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Delete this video permanently?')">
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
$(function () {
    // FIX #8: lengthChange enabled so pageLength:25 is actually useful to the user
    $('#videosTable').DataTable({
        'paging'      : true,
        'lengthChange': true,
        'searching'   : true,
        'ordering'    : true,
        'info'        : true,
        'autoWidth'   : false,
        'responsive'  : true,
        'pageLength'  : 25,
        'lengthMenu'  : [10, 25, 50, 100]
    });
});
</script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
