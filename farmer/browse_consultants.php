<?php
session_start();
include_once('../_functions.php');
require_once('farmer_dao.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;
$farmerDAO = new FarmerDAO($conn);
$user_id = intval($_SESSION['user']['user_id']);

// Get farmer profile
$farmer_profile = $farmerDAO->getFarmerProfile($user_id);
$farmer_id = $farmer_profile['farmer_id'] ?? null;

// Get all available consultants with their services
$sql = "SELECT 
    c.consultant_id,
    c.user_id,
    c.specialization,
    c.degree,
    c.bio,
    c.profile_image,
    u.user_name,
    u.email,
    COUNT(cs.service_id) as total_services,
    AVG(cr.rating) as avg_rating,
    COUNT(DISTINCT fcs.subscription_id) as total_clients
FROM consultants c
JOIN users u ON c.user_id = u.user_id
LEFT JOIN consultancy_services cs ON c.consultant_id = cs.consultant_id AND cs.is_active = 'Y'
LEFT JOIN farmer_consultancy_subscriptions fcs ON c.consultant_id = fcs.consultant_id AND fcs.subscription_status = 'active'
LEFT JOIN consultancy_ratings cr ON c.consultant_id = cr.consultant_id AND cr.is_active = 'Y'
WHERE c.is_active = 'Y' AND c.verification_status = 'approved'
GROUP BY c.consultant_id
ORDER BY avg_rating DESC";

$result = $conn->query($sql);
$consultants = [];
while ($row = $result->fetch_assoc()) {
    $consultants[] = $row;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialty = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';

// Filter consultants
$filtered_consultants = $consultants;
if ($search) {
    $filtered_consultants = array_filter($filtered_consultants, function($c) use ($search) {
        return stripos($c['user_name'], $search) !== false || 
               stripos($c['specialization'], $search) !== false ||
               stripos($c['bio'], $search) !== false;
    });
}

if ($specialty) {
    $filtered_consultants = array_filter($filtered_consultants, function($c) use ($specialty) {
        return stripos($c['specialization'], $specialty) !== false;
    });
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Consultants - Farmer</title>
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
	
    <style>
        .consultant-card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        .consultant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        .consultant-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .consultant-info {
            padding: 20px;
        }
        .consultant-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .consultant-spec {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .rating-stars {
            color: #ffc107;
            margin: 5px 0;
        }
        .badge-info {
            background-color: #17a2b8;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
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
                        <h1 class="m-0"><i class="fas fa-user-tie"></i> Browse Consultants</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Consultants</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label"><i class="fas fa-search"></i> Search Consultants</label>
                            <input type="text" class="form-control" name="search" placeholder="Name, specialization..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label"><i class="fas fa-briefcase"></i> Specialization</label>
                            <input type="text" class="form-control" name="specialty" placeholder="e.g., Organic Farming, Crop Management..." value="<?php echo htmlspecialchars($specialty); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                    <hr class="my-3">
                    <a href="farmer/browse_consultants" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                </div>

                <!-- Consultants Grid -->
                <?php if (!empty($filtered_consultants)): ?>
                    <div class="row">
                        <?php foreach ($filtered_consultants as $consultant): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="consultant-card">
                                    <img src="<?php echo !empty($consultant['profile_image']) ? htmlspecialchars($consultant['profile_image']) : 'assets/dist/img/user-default.png'; ?>" class="consultant-image" alt="<?php echo htmlspecialchars($consultant['user_name']); ?>">
                                    <div class="consultant-info">
                                        <div class="consultant-name"><?php echo htmlspecialchars($consultant['user_name']); ?></div>
                                        <div class="consultant-spec">
                                            <i class="fas fa-certificate"></i> <?php echo htmlspecialchars($consultant['specialization']); ?>
                                        </div>
                                        <div class="consultant-spec text-muted">
                                            <small><?php echo htmlspecialchars(substr($consultant['degree'], 0, 30)); ?></small>
                                        </div>
                                        
                                        <!-- Rating -->
                                        <div class="rating-stars">
                                            <?php 
                                            $rating = round($consultant['avg_rating'] ?? 0);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            echo ' <span class="text-dark">(' . ($consultant['avg_rating'] ? number_format($consultant['avg_rating'], 1) : 'No ratings') . ')</span>';
                                            ?>
                                        </div>

                                        <div class="mt-3 mb-3">
                                            <span class="badge badge-info"><?php echo $consultant['total_services']; ?> Services</span>
                                            <span class="badge badge-success"><?php echo $consultant['total_clients']; ?> Active Clients</span>
                                        </div>

                                        <p class="text-muted" style="font-size: 0.85rem;">
                                            <?php echo htmlspecialchars(substr($consultant['bio'], 0, 80)) . '...'; ?>
                                        </p>

                                        <div class="mt-3">
                                            <a href="farmer/consultant_detail?consultant_id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-primary btn-block">
                                                <i class="fas fa-eye"></i> View Services
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-info-circle"></i> 
                        <strong>No consultants found.</strong> Try adjusting your search filters.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.min.js"></script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
