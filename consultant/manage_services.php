<?php
session_start();
include_once('../_functions.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;
$user_id = intval($_SESSION['user']['user_id']);

// Get consultant profile
$sql = "SELECT * FROM consultants WHERE user_id = ? AND is_active = 'Y'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$consultant = $result->fetch_assoc();
$stmt->close();

if (!$consultant) {
    die('Consultant profile not found. Please contact admin.');
}

$consultant_id = $consultant['consultant_id'];
$message = '';
$message_type = '';

// Handle add service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_service') {
    $service_name = trim($_POST['service_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_per_month = floatval($_POST['price_per_month'] ?? 0);
    $duration_months = intval($_POST['duration_months'] ?? 1);
    $max_consultations = intval($_POST['max_consultations'] ?? 4);
    $consultation_duration = intval($_POST['consultation_duration'] ?? 30);
    $expertise_areas = trim($_POST['expertise_areas'] ?? '');

    if (!$service_name || !$description || $price_per_month <= 0) {
        $message = 'Please fill all required fields with valid values.';
        $message_type = 'error';
    } else {
        $sql = "INSERT INTO consultancy_services 
                (consultant_id, service_name, description, price_per_month, duration_months, 
                 max_consultations, consultation_duration_mins, expertise_areas, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issdiiiis', 
            $consultant_id, $service_name, $description, $price_per_month, 
            $duration_months, $max_consultations, $consultation_duration, 
            $expertise_areas, $user_id
        );
        
        if ($stmt->execute()) {
            $message = 'Service created successfully!';
            $message_type = 'success';
            $_POST = [];
        } else {
            $message = 'Failed to create service. Please try again.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Get consultant's services
$sql = "SELECT * FROM consultancy_services 
        WHERE consultant_id = ? 
        ORDER BY created_on DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$result = $stmt->get_result();
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Services - Consultant</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <style>
        .service-card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .service-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
        }
        .service-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #28a745;
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
                        <h1 class="m-0"><i class="fas fa-briefcase"></i> My Services</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="consultant/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Services</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Message Alert -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-12">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addServiceModal">
                            <i class="fas fa-plus-circle"></i> Add New Service
                        </button>
                    </div>
                </div>

                <!-- Services List -->
                <div class="row">
                    <div class="col-12">
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <div class="card service-card">
                                    <div class="service-header">
                                        <h5 class="m-0"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p class="text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                                                <div class="row mt-3">
                                                    <div class="col-6 col-md-3">
                                                        <small class="text-muted">Duration</small>
                                                        <div><strong><?php echo $service['duration_months']; ?> Month(s)</strong></div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <small class="text-muted">Consultations</small>
                                                        <div><strong><?php echo $service['max_consultations']; ?> Sessions</strong></div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <small class="text-muted">Session Duration</small>
                                                        <div><strong><?php echo $service['consultation_duration_mins']; ?> mins</strong></div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <small class="text-muted">Status</small>
                                                        <div>
                                                            <?php if ($service['is_active'] === 'Y'): ?>
                                                                <span class="badge badge-success">Active</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-secondary">Inactive</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <small class="text-muted">Expertise Areas:</small>
                                                    <p class="text-muted"><?php echo htmlspecialchars($service['expertise_areas'] ?? 'General'); ?></p>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-right">
                                                <div class="service-price">
                                                    ₹<?php echo number_format($service['price_per_month'], 2); ?>
                                                </div>
                                                <small class="text-muted">per month</small>
                                                <div class="mt-3">
                                                    <a href="consultant/edit_service?service_id=<?php echo $service['service_id']; ?>" class="btn btn-warning btn-sm mb-2 d-block">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm d-block" onclick="deleteService(<?php echo $service['service_id']; ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You haven't created any services yet. Start by adding your first service!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Service</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_service">
                    
                    <div class="mb-3">
                        <label class="form-label">Service Name *</label>
                        <input type="text" name="service_name" class="form-control" placeholder="e.g., Organic Farming Consultation" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe what this service includes..." required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Price per Month (₹) *</label>
                                <input type="number" name="price_per_month" class="form-control" placeholder="0.00" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Duration (Months) *</label>
                                <input type="number" name="duration_months" class="form-control" value="1" min="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Max Consultations *</label>
                                <input type="number" name="max_consultations" class="form-control" value="4" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Duration per Session (mins) *</label>
                                <input type="number" name="consultation_duration" class="form-control" value="30" min="15" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Expertise Areas</label>
                        <textarea name="expertise_areas" class="form-control" rows="2" placeholder="e.g., Soil Management, Crop Rotation, Pest Control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.min.js"></script>

<script>
    function deleteService(serviceId) {
        if (confirm('Are you sure you want to delete this service?')) {
            // TODO: Implement delete functionality
            alert('Delete functionality coming soon');
        }
    }
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
