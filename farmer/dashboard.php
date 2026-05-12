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
$current_user_id = intval($_SESSION['user']['user_id']);
$farmerDAO = new FarmerDAO($conn);
$farmer_profile = $farmerDAO->getFarmerProfile($current_user_id);

if (!$farmer_profile) {
    header('location:../login');
    exit;
}

$farmer_id = intval($farmer_profile['farmer_id']);
$farm_location = trim($farmer_profile['location'] ?? '') ?: 'Nashik';
$location_parts = preg_split('/[;,\n]/', $farm_location);
$weather_city = trim($location_parts[0] ?? 'Nashik') ?: 'Nashik';

function fetch_json_data($url): ?array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CropIntel/1.0)',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if (!$errno && $body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CropIntel/1.0)',
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if (!$errno && $body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create(['http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; CropIntel/1.0)',
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $context);
        if ($body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
    }

    return null;
}

function weather_meta(int $code): array
{
    $map = [
        0 => ['label' => 'Clear Sky', 'icon' => 'fas fa-sun', 'tone' => 'sunny'],
        1 => ['label' => 'Mainly Clear', 'icon' => 'fas fa-cloud-sun', 'tone' => 'sunny'],
        2 => ['label' => 'Partly Cloudy', 'icon' => 'fas fa-cloud-sun', 'tone' => 'cloudy'],
        3 => ['label' => 'Overcast', 'icon' => 'fas fa-cloud', 'tone' => 'cloudy'],
        45 => ['label' => 'Foggy', 'icon' => 'fas fa-smog', 'tone' => 'mist'],
        48 => ['label' => 'Rime Fog', 'icon' => 'fas fa-smog', 'tone' => 'mist'],
        51 => ['label' => 'Light Drizzle', 'icon' => 'fas fa-cloud-rain', 'tone' => 'rainy'],
        53 => ['label' => 'Moderate Drizzle', 'icon' => 'fas fa-cloud-rain', 'tone' => 'rainy'],
        55 => ['label' => 'Dense Drizzle', 'icon' => 'fas fa-cloud-rain', 'tone' => 'rainy'],
        61 => ['label' => 'Light Rain', 'icon' => 'fas fa-cloud-showers-heavy', 'tone' => 'rainy'],
        63 => ['label' => 'Moderate Rain', 'icon' => 'fas fa-cloud-showers-heavy', 'tone' => 'rainy'],
        65 => ['label' => 'Heavy Rain', 'icon' => 'fas fa-bolt', 'tone' => 'stormy'],
        80 => ['label' => 'Showers', 'icon' => 'fas fa-cloud-rain', 'tone' => 'rainy'],
        81 => ['label' => 'Showers', 'icon' => 'fas fa-cloud-rain', 'tone' => 'rainy'],
        82 => ['label' => 'Heavy Showers', 'icon' => 'fas fa-bolt', 'tone' => 'stormy'],
        95 => ['label' => 'Thunderstorm', 'icon' => 'fas fa-bolt', 'tone' => 'stormy'],
        96 => ['label' => 'Thunderstorm + Hail', 'icon' => 'fas fa-bolt', 'tone' => 'stormy'],
        99 => ['label' => 'Severe Storm', 'icon' => 'fas fa-bolt', 'tone' => 'stormy'],
    ];

    return $map[$code] ?? ['label' => 'Unknown', 'icon' => 'fas fa-cloud', 'tone' => 'cloudy'];
}

$farmer_stats = $farmerDAO->getFarmerStats($farmer_id);
$recent_orders = $farmerDAO->getFarmerOrders($farmer_id, 5);

$total_predictions = 0;
$latest_prediction = null;
$recent_predictions = [];
$dashboard_suggestions = [];

$stmt = $conn->prepare("SELECT COUNT(*) AS total_predictions FROM prediction_master WHERE created_by = ? AND is_active = 'Y'");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_predictions = (int)($row['total_predictions'] ?? 0);
}
$stmt->close();

$stmt = $conn->prepare("SELECT pre_ms_id, image, created_on FROM prediction_master WHERE created_by = ? AND is_active = 'Y' ORDER BY pre_ms_id DESC LIMIT 5");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $top_disease = null;
    $top_confidence = null;

    $detail_stmt = $conn->prepare("SELECT predicted_disease, confidence_percent FROM prediction_details WHERE pre_ms_id = ? AND is_active = 'Y' ORDER BY CAST(REPLACE(confidence_percent, '%', '') AS DECIMAL(10,2)) DESC, pre_det_id ASC LIMIT 1");
    $detail_stmt->bind_param('i', $row['pre_ms_id']);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();
    if ($detail_row = $detail_result->fetch_assoc()) {
        $top_disease = $detail_row['predicted_disease'];
        $top_confidence = $detail_row['confidence_percent'];
    }
    $detail_stmt->close();

    $row['top_disease'] = $top_disease;
    $row['top_confidence'] = $top_confidence;
    $recent_predictions[] = $row;
}
$stmt->close();

if (!empty($recent_predictions)) {
    $latest_prediction = $recent_predictions[0];
    $latest_top_disease = $latest_prediction['top_disease'];
    if (!empty($latest_top_disease)) {
        $disease_stmt = $conn->prepare("SELECT disease_name, one_line_description, prevention, causes, symptoms FROM diseases WHERE is_active = 'Y' AND disease_name = ? LIMIT 1");
        $disease_stmt->bind_param('s', $latest_top_disease);
        $disease_stmt->execute();
        $disease_result = $disease_stmt->get_result();
        if ($disease_row = $disease_result->fetch_assoc()) {
            $dashboard_suggestions[] = $disease_row;
        }
        $disease_stmt->close();
    }
}

$place = ['name' => $weather_city, 'country' => 'India', 'lat' => 19.9975, 'lon' => 73.7898];
$geo = fetch_json_data('https://geocoding-api.open-meteo.com/v1/search?name=' . urlencode($weather_city) . '&count=1&language=en&format=json');
if (!empty($geo['results'][0])) {
    $geo_row = $geo['results'][0];
    $place = [
        'name' => $geo_row['name'] ?? $weather_city,
        'country' => $geo_row['country'] ?? 'India',
        'lat' => $geo_row['latitude'] ?? 19.9975,
        'lon' => $geo_row['longitude'] ?? 73.7898,
    ];
}

$weather_url = sprintf(
    'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current=temperature_2m,apparent_temperature,weather_code,wind_speed_10m,relative_humidity_2m,precipitation&hourly=temperature_2m,weather_code,precipitation_probability,wind_speed_10m&timezone=auto&forecast_days=2',
    $place['lat'],
    $place['lon']
);

$weather = fetch_json_data($weather_url);
$current_weather = $weather['current'] ?? [];
$hourly_weather = $weather['hourly'] ?? [];
$weather_meta_current = weather_meta((int)($current_weather['weather_code'] ?? 0));
$weather_hours = [];
if (!empty($hourly_weather['time'])) {
    $max_hours = 8;
    foreach ($hourly_weather['time'] as $i => $time_value) {
        if (count($weather_hours) >= $max_hours) {
            break;
        }
        if (strtotime($time_value) < time() - 1800) {
            continue;
        }
        $weather_hours[] = [
            'time' => $time_value,
            'temp' => $hourly_weather['temperature_2m'][$i] ?? null,
            'code' => (int)($hourly_weather['weather_code'][$i] ?? 0),
            'precip' => $hourly_weather['precipitation_probability'][$i] ?? null,
            'wind' => $hourly_weather['wind_speed_10m'][$i] ?? null,
        ];
    }
}

$product_type_rows = [];
$stmt = $conn->prepare("SELECT COALESCE(type, 'Other') AS label, COUNT(*) AS total FROM products WHERE farmer_id = ? AND product_source = 'farmer' AND is_active = 'Y' GROUP BY COALESCE(type, 'Other') ORDER BY total DESC LIMIT 6");
$stmt->bind_param('i', $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $product_type_rows[] = [$row['label'], (int)$row['total']];
}
$stmt->close();

if (empty($product_type_rows)) {
    $product_type_rows = [['No Products', 1]];
}

$prediction_month_rows = [['Month', 'Predictions']];
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("first day of -$i month"));
    $month_label = date('M', strtotime("first day of -$i month"));
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM prediction_master WHERE created_by = ? AND is_active = 'Y' AND DATE_FORMAT(created_on, '%Y-%m') = ?");
    $stmt->bind_param('is', $current_user_id, $month_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = (int)($result->fetch_assoc()['total'] ?? 0);
    $stmt->close();
    $prediction_month_rows[] = [$month_label, $count];
}

$sales_month_rows = [['Month', 'Sales']];
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("first day of -$i month"));
    $month_label = date('M', strtotime("first day of -$i month"));
    $stmt = $conn->prepare("SELECT COALESCE(SUM(pp.total_amt), 0) AS total FROM purchase_product pp INNER JOIN products p ON pp.pro_id = p.pro_id WHERE p.farmer_id = ? AND p.product_source = 'farmer' AND DATE_FORMAT(pp.created_on, '%Y-%m') = ?");
    $stmt->bind_param('is', $farmer_id, $month_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales_total = (float)($result->fetch_assoc()['total'] ?? 0);
    $stmt->close();
    $sales_month_rows[] = [$month_label, $sales_total];
}

$disease_rows = [['Disease', 'Cases']];
$stmt = $conn->prepare("SELECT COALESCE(pd.predicted_disease, 'Unknown') AS disease_name, COUNT(*) AS total FROM prediction_master pm INNER JOIN prediction_details pd ON pm.pre_ms_id = pd.pre_ms_id WHERE pm.created_by = ? AND pm.is_active = 'Y' AND pd.is_active = 'Y' GROUP BY COALESCE(pd.predicted_disease, 'Unknown') ORDER BY total DESC LIMIT 5");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $disease_rows[] = [$row['disease_name'], (int)$row['total']];
}
$stmt->close();

if (count($disease_rows) === 1) {
    $disease_rows[] = ['No Data', 1];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
	
    <style>
        /* Make weather card text scale on smaller screens */
        .card-img-overlay {
            padding: 1.5rem !important;
        }
        @media (max-width: 768px) {
            .display-2 {
                font-size: 2rem !important;
            }
            .small-box h3 {
                font-size: 1.5rem;
            }
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
                        <h1 class="m-0">Dashboard</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">

                <!-- Small boxes -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="small-box bg-info">
                            <div class="inner"><h3><?php echo (int)($farmer_stats['total_products'] ?? 0); ?></h3><p>Total Products</p></div>
                            <div class="icon"><i class="fas fa-seedling"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="small-box bg-success">
                            <div class="inner"><h3><?php echo (int)($farmer_stats['approved_products'] ?? 0); ?></h3><p>Approved Products</p></div>
                            <div class="icon"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="small-box bg-warning">
                            <div class="inner"><h3><?php echo (int)($farmer_stats['pending_products'] ?? 0); ?></h3><p>Pending Approvals</p></div>
                            <div class="icon"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="small-box bg-danger">
                            <div class="inner"><h3>₹<?php echo number_format((float)($farmer_stats['total_sales'] ?? 0), 0); ?></h3><p>Total Sales</p></div>
                            <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Weather & Disease Table -->
                <div class="row">
                    <div class="col-xl-4 col-lg-6 col-md-12 mb-3">
                        <div class="card bg-dark text-white" style="border-radius: 28px; overflow: hidden; min-height: 330px; background: linear-gradient(135deg, rgba(10, 31, 68, 0.95), rgba(19, 108, 160, 0.88));">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1 text-uppercase" style="letter-spacing: .08em; opacity: .75;">Live Weather</p>
                                        <h4 class="mb-1"><?php echo htmlspecialchars($place['name'] . ', ' . $place['country']); ?></h4>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="<?php echo htmlspecialchars($weather_meta_current['icon']); ?> mr-2" style="font-size: 2rem;"></i>
                                            <span class="h5 mb-0"><?php echo htmlspecialchars($weather_meta_current['label']); ?></span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="display-4 mb-0" style="font-weight: 800; line-height: 1;">
                                            <?php echo isset($current_weather['temperature_2m']) ? htmlspecialchars(number_format((float)$current_weather['temperature_2m'], 1)) . '°C' : 'N/A'; ?>
                                        </div>
                                        <small>Feels like <?php echo isset($current_weather['apparent_temperature']) ? htmlspecialchars(number_format((float)$current_weather['apparent_temperature'], 1)) . '°C' : 'N/A'; ?></small>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-4">
                                        <div class="p-3 rounded" style="background: rgba(255,255,255,.08);">
                                            <small class="d-block text-uppercase" style="opacity:.7;">Humidity</small>
                                            <strong><?php echo isset($current_weather['relative_humidity_2m']) ? htmlspecialchars(number_format((float)$current_weather['relative_humidity_2m'], 0)) . '%' : 'N/A'; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-3 rounded" style="background: rgba(255,255,255,.08);">
                                            <small class="d-block text-uppercase" style="opacity:.7;">Wind</small>
                                            <strong><?php echo isset($current_weather['wind_speed_10m']) ? htmlspecialchars(number_format((float)$current_weather['wind_speed_10m'], 1)) . ' km/h' : 'N/A'; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-3 rounded" style="background: rgba(255,255,255,.08);">
                                            <small class="d-block text-uppercase" style="opacity:.7;">Rain</small>
                                            <strong><?php echo isset($current_weather['precipitation']) ? htmlspecialchars(number_format((float)$current_weather['precipitation'], 1)) . ' mm' : 'N/A'; ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($weather_hours)): ?>
                                    <div class="mt-4">
                                        <small class="text-uppercase d-block mb-2" style="opacity:.7;">Next Hours</small>
                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                            <?php foreach (array_slice($weather_hours, 0, 5) as $hour): ?>
                                                <?php $hour_meta = weather_meta((int)$hour['code']); ?>
                                                <div class="p-2 rounded text-center" style="background: rgba(255,255,255,.08); min-width: 86px;">
                                                    <div style="font-size:.8rem; opacity:.8;"><?php echo date('g A', strtotime($hour['time'])); ?></div>
                                                    <i class="<?php echo htmlspecialchars($hour_meta['icon']); ?> my-1"></i>
                                                    <div style="font-weight:700;"><?php echo isset($hour['temp']) ? htmlspecialchars(number_format((float)$hour['temp'], 0)) . '°' : 'N/A'; ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8 col-lg-6 col-md-12 mb-3">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Crop Disease Detection Report</h3></div>
                            <div class="card-body table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Disease</th>
                                            <th>Crop</th>
                                            <th>Detected Region</th>
                                            <th>Solution</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_predictions)): ?>
                                            <?php foreach ($recent_predictions as $index => $prediction): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($prediction['top_disease'] ?? 'N/A'); ?></td>
                                                    <td>Grape</td>
                                                    <td>Nashik</td>
                                                    <td><?php echo !empty($dashboard_suggestions[0]['prevention']) ? htmlspecialchars(echoWords($dashboard_suggestions[0]['prevention'], 5)) . '...' : 'Review latest guidance'; ?></td>
                                                    <td><span class="badge badge-<?php echo !empty($prediction['top_disease']) ? 'danger' : 'secondary'; ?>"><?php echo !empty($prediction['top_confidence']) ? htmlspecialchars($prediction['top_confidence']) : 'Pending'; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No predictions found yet.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suggestions based on recent predictions -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-lightbulb"></i> Suggestions from Your Latest Prediction</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($dashboard_suggestions)): ?>
                                    <h5 class="mb-2"><?php echo htmlspecialchars($dashboard_suggestions[0]['disease_name']); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($dashboard_suggestions[0]['one_line_description']); ?></p>
                                    <div class="row">
                                        <div class="col-md-4 mb-2"><strong>Causes:</strong><br><?php echo htmlspecialchars(echoWords($dashboard_suggestions[0]['causes'], 18)); ?>...</div>
                                        <div class="col-md-4 mb-2"><strong>Symptoms:</strong><br><?php echo htmlspecialchars(echoWords($dashboard_suggestions[0]['symptoms'], 18)); ?>...</div>
                                        <div class="col-md-4 mb-2"><strong>Prevention:</strong><br><?php echo htmlspecialchars(echoWords($dashboard_suggestions[0]['prevention'], 18)); ?>...</div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Upload a leaf image in Disease Detection to get personalized suggestions here.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-lg-6 col-md-12 mb-3"><div id="soilChart" style="height:400px;"></div></div>
                    <div class="col-lg-6 col-md-12 mb-3"><div id="cropChart" style="height:400px;"></div></div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-12 mb-3"><div id="rainfallChart" style="height:400px;"></div></div>
                    <div class="col-lg-6 col-md-12 mb-3"><div id="diseaseChart" style="height:400px;"></div></div>
                </div>

            </div>
        </section>
    </div>
</div>

<!-- JS -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/dist/js/custom.js"></script>
<script src="assets/plugins/chart/loader.js"></script>

<script>
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawAll);

function drawAll() {
    var soilData = google.visualization.arrayToDataTable(<?php echo json_encode(array_merge([['Category', 'Count']], $product_type_rows)); ?>);
    new google.visualization.PieChart(document.getElementById('soilChart')).draw(soilData, {
        title: 'My Product Mix',
        pieHole: 0.42,
        height: 400,
        width: '100%',
        backgroundColor: 'transparent',
        legend: { position: 'right' }
    });

    var cropData = google.visualization.arrayToDataTable(<?php echo json_encode($prediction_month_rows); ?>);
    new google.visualization.ComboChart(document.getElementById('cropChart')).draw(cropData, {
        title: 'Prediction Activity (Last 6 Months)',
        seriesType: 'bars',
        series: {0: {type: 'bars'}},
        height: 400,
        width: '100%',
        backgroundColor: 'transparent',
        legend: { position: 'none' },
        colors: ['#28a745']
    });

    var rainData = google.visualization.arrayToDataTable(<?php echo json_encode($sales_month_rows); ?>);
    new google.visualization.LineChart(document.getElementById('rainfallChart')).draw(rainData, {
        title: 'Sales Trend (Last 6 Months)',
        curveType: 'function',
        legend: { position: 'bottom' },
        height: 400,
        width: '100%',
        backgroundColor: 'transparent',
        colors: ['#dc3545']
    });

    var diseaseData = google.visualization.arrayToDataTable(<?php echo json_encode($disease_rows); ?>);
    new google.visualization.PieChart(document.getElementById('diseaseChart')).draw(diseaseData, {
        title: 'Detected Disease Distribution',
        height: 400,
        width: '100%',
        backgroundColor: 'transparent',
        legend: { position: 'right' }
    });
}
</script>

<?php include_once('../_chat_widget.php'); ?>
</body>
</html>