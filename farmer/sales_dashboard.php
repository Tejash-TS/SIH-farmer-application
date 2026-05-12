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

if (!$farmer_id) {
    header('location:../login');
    exit;
}

// Get sales data
$stats = $farmerDAO->getFarmerStats($farmer_id);
$orders = $farmerDAO->getFarmerOrders($farmer_id, 100);

// Calculate additional metrics
$total_orders = count($orders);
$average_order_value = $total_orders > 0 ? round($stats['total_sales'] / $total_orders, 2) : 0;

// Calculate monthly sales data for chart
$monthly_sales = [];
$current_month = date('Y-m');
foreach ($orders as $order) {
    $order_month = substr($order['created_on'], 0, 7); // YYYY-MM format
    if (!isset($monthly_sales[$order_month])) {
        $monthly_sales[$order_month] = 0;
    }
    $monthly_sales[$order_month] += $order['total_amt'];
}
ksort($monthly_sales);

// Get last 12 months data
$months_data = [];
$sales_data = [];
$current_date = new DateTime();
for ($i = 11; $i >= 0; $i--) {
    $date = clone $current_date;
    $date->modify("-$i month");
    $month_key = $date->format('Y-m');
    $months_data[] = $date->format('M Y');
    $sales_data[] = isset($monthly_sales[$month_key]) ? $monthly_sales[$month_key] : 0;
}

// Get product-wise sales
$product_sales = [];
foreach ($orders as $order) {
    $product_name = $order['pro_name'];
    if (!isset($product_sales[$product_name])) {
        $product_sales[$product_name] = [
            'quantity' => 0,
            'amount' => 0
        ];
    }
    $product_sales[$product_name]['quantity'] += $order['pro_qty'];
    $product_sales[$product_name]['amount'] += $order['total_amt'];
}
arsort($product_sales);
$top_products = array_slice($product_sales, 0, 5);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Dashboard - Farmer</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/plugins/chart.js/Chart.min.css">
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
        .sales-card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .order-row {
            border-bottom: 1px solid #e9ecef;
            padding: 15px 0;
        }
        .order-row:last-child {
            border-bottom: none;
        }
        .badge-success {
            background-color: #28a745;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
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
                        <h1 class="m-0"><i class="fas fa-chart-line"></i> Sales Dashboard</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Sales Report</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <!-- Total Sales -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card sales-card bg-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">Total Sales</div>
                                        <div class="stat-value">₹<?php echo number_format($stats['total_sales'], 2); ?></div>
                                    </div>
                                    <div style="font-size: 3rem; color: #007bff; opacity: 0.3;">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Orders -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card sales-card bg-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">Total Orders</div>
                                        <div class="stat-value"><?php echo $total_orders; ?></div>
                                    </div>
                                    <div style="font-size: 3rem; color: #28a745; opacity: 0.3;">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Average Order Value -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card sales-card bg-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">Avg Order Value</div>
                                        <div class="stat-value">₹<?php echo number_format($average_order_value, 2); ?></div>
                                    </div>
                                    <div style="font-size: 3rem; color: #ffc107; opacity: 0.3;">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Products -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card sales-card bg-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">Products Listed</div>
                                        <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                                    </div>
                                    <div style="font-size: 3rem; color: #17a2b8; opacity: 0.3;">
                                        <i class="fas fa-box"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Sales Chart -->
                    <div class="col-lg-8">
                        <div class="card sales-card">
                            <div class="card-header bg-primary">
                                <h5 class="m-0"><i class="fas fa-chart-line"></i> Sales Trend (Last 12 Months)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="col-lg-4">
                        <div class="card sales-card">
                            <div class="card-header bg-success">
                                <h5 class="m-0"><i class="fas fa-star"></i> Top Products</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($top_products)): ?>
                                    <?php foreach ($top_products as $product_name => $data): ?>
                                        <div class="mb-3 pb-3" style="border-bottom: 1px solid #e9ecef;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars(substr($product_name, 0, 20)); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-cube"></i> <?php echo $data['quantity']; ?> units sold
                                                    </small>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-weight-bold text-success">
                                                        ₹<?php echo number_format($data['amount'], 2); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No sales data available yet
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="row">
                    <div class="col-12">
                        <div class="card sales-card">
                            <div class="card-header bg-info">
                                <h5 class="m-0"><i class="fas fa-list"></i> Recent Orders</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($orders)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Customer</th>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Amount</th>
                                                    <th>Payment Method</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td><strong>#<?php echo $order['purchas_id']; ?></strong></td>
                                                        <td>
                                                            <div><?php echo htmlspecialchars($order['user_name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars(substr($order['pro_name'], 0, 25)); ?></td>
                                                        <td><span class="badge badge-primary"><?php echo $order['pro_qty']; ?></span></td>
                                                        <td><strong>₹<?php echo number_format($order['total_amt'], 2); ?></strong></td>
                                                        <td>
                                                            <small><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></small>
                                                        </td>
                                                        <td>
                                                            <small><?php echo date('d M Y H:i', strtotime($order['created_on'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <a href="farmer/get_order_details?order_id=<?php echo $order['purchas_id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> No orders found. Start selling your products!
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

<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="assets/plugins/chart.js/Chart.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.min.js"></script>

<script>
    // Sales Chart
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months_data); ?>,
            datasets: [{
                label: 'Monthly Sales (₹)',
                data: <?php echo json_encode($sales_data); ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#007bff',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
