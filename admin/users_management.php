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
$role_filter = $_GET['role'] ?? 'all';
$admin_id = $_SESSION['user']['user_id'];

// Handle user activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'deactivate' && $user_id) {
        $adminDAO->deactivateUser($user_id, $admin_id);
        header("location:admin/users_management.php?role=$role_filter&status=deactivated");
        exit;
    } elseif ($action === 'activate' && $user_id) {
        $adminDAO->activateUser($user_id, $admin_id);
        header("location:admin/users_management.php?role=$role_filter&status=activated");
        exit;
    }
}

// Get users based on filter
if ($role_filter === 'all') {
    $users = $adminDAO->getAllUsers();
} else {
    $users = $adminDAO->getUsersByRole($role_filter);
}
$status_message = $_GET['status'] ?? '';

// Get all roles
$roles_query = "SELECT DISTINCT role FROM users WHERE role != 'admin' ORDER BY role";
$roles_result = $conn->query($roles_query);
$available_roles = [];
while ($row = $roles_result->fetch_assoc()) {
    $available_roles[] = $row['role'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users Management - Admin</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .user-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
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
                        <h1 class="m-0"><i class="fas fa-users"></i> Users Management</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin">Home</a></li>
                            <li class="breadcrumb-item active">Users Management</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <?php if ($status_message === 'deactivated'): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> User deactivated successfully!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php elseif ($status_message === 'activated'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> User activated successfully!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title">
                                    <i class="fas fa-list"></i> 
                                    <?php echo $role_filter === 'all' ? 'All Users' : ucfirst($role_filter) . 's'; ?>
                                    <span class="badge badge-light ml-2"><?php echo count($users); ?></span>
                                </h3>
                                <div class="card-tools">
                                    <form method="GET" class="form-inline" style="display: flex; gap: 10px;">
                                        <label for="role_select">Filter by Role:</label>
                                        <select id="role_select" name="role" class="form-control form-control-sm" onchange="this.form.submit();">
                                            <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>
                                                All Users
                                            </option>
                                            <?php foreach ($available_roles as $role): ?>
                                                <option value="<?php echo $role; ?>" <?php echo $role_filter === $role ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($role); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($users)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No users found for this role.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="usersTable">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>User ID</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                    <th>Joined</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($users as $user): ?>
                                                    <tr>
                                                        <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                                        <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['mb_number'] ?? '-'); ?></td>
                                                        <td>
                                                            <span class="badge badge-info">
                                                                <?php echo ucfirst($user['role']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="user-status <?php echo $user['is_active'] === 'Y' ? 'status-active' : 'status-inactive'; ?>">
                                                                <?php echo $user['is_active'] === 'Y' ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('d M Y', strtotime($user['created_on'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['is_active'] === 'Y'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                                    <input type="hidden" name="action" value="deactivate">
                                                                    <button type="submit" class="btn btn-sm btn-warning" title="Deactivate User">
                                                                        <i class="fas fa-ban"></i> Deactivate
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                                    <input type="hidden" name="action" value="activate">
                                                                    <button type="submit" class="btn btn-sm btn-success" title="Activate User">
                                                                        <i class="fas fa-check"></i> Activate
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
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
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>

<script>
$(function() {
    $('#usersTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "pageLength": 25
    });
});
</script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
