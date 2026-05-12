<?php
session_start();
include_once('../_functions.php');
global $conn;

if (!isset($_SESSION['user'])) {
    header("location:../login");
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

// ─── HTTP Fetch with multiple fallback strategies ──────────────────────────
function fetch_json($url): ?array
{
    // Strategy 1: cURL with SSL verification
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; FarmWeather/1.0)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if (!$errno && $body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }

        // Strategy 2: cURL without SSL peer verification (common on shared hosts)
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CONNECTTIMEOUT  => 6,
            CURLOPT_TIMEOUT         => 12,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; FarmWeather/1.0)',
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if (!$errno && $body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }
    }

    // Strategy 3: file_get_contents (needs allow_url_fopen = On)
    if (ini_get('allow_url_fopen')) {
        $ctx  = stream_context_create(['http' => [
            'timeout'     => 10,
            'user_agent'  => 'Mozilla/5.0 (compatible; FarmWeather/1.0)',
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }
    }

    return null;
}

// ─── Weather code → metadata ───────────────────────────────────────────────
function weather_meta(int $code): array
{
    $map = [
        0  => ['label' => 'Clear Sky',         'icon' => '☀️',  'css' => 'sunny',   'desc' => 'Clear and bright conditions.'],
        1  => ['label' => 'Mainly Clear',       'icon' => '🌤️', 'css' => 'sunny',   'desc' => 'Mostly clear skies.'],
        2  => ['label' => 'Partly Cloudy',      'icon' => '⛅',  'css' => 'cloudy',  'desc' => 'A mix of sun and clouds.'],
        3  => ['label' => 'Overcast',           'icon' => '☁️',  'css' => 'cloudy',  'desc' => 'Complete cloud cover.'],
        45 => ['label' => 'Foggy',              'icon' => '🌫️', 'css' => 'foggy',   'desc' => 'Reduced visibility due to fog.'],
        48 => ['label' => 'Rime Fog',           'icon' => '🌫️', 'css' => 'foggy',   'desc' => 'Fog with frozen droplets.'],
        51 => ['label' => 'Light Drizzle',      'icon' => '🌦️', 'css' => 'rainy',   'desc' => 'Light drizzle throughout.'],
        53 => ['label' => 'Moderate Drizzle',   'icon' => '🌧️', 'css' => 'rainy',   'desc' => 'Moderate persistent drizzle.'],
        55 => ['label' => 'Dense Drizzle',      'icon' => '🌧️', 'css' => 'rainy',   'desc' => 'Dense drizzle affecting visibility.'],
        61 => ['label' => 'Light Rain',         'icon' => '🌦️', 'css' => 'rainy',   'desc' => 'Light rain expected.'],
        63 => ['label' => 'Moderate Rain',      'icon' => '🌧️', 'css' => 'rainy',   'desc' => 'Moderate rain may slow operations.'],
        65 => ['label' => 'Heavy Rain',         'icon' => '⛈️',  'css' => 'stormy',  'desc' => 'Heavy rain — avoid fieldwork.'],
        71 => ['label' => 'Light Snow',         'icon' => '🌨️', 'css' => 'snowy',   'desc' => 'Light snowfall.'],
        73 => ['label' => 'Moderate Snow',      'icon' => '❄️',  'css' => 'snowy',   'desc' => 'Moderate snowfall.'],
        75 => ['label' => 'Heavy Snow',         'icon' => '❄️',  'css' => 'snowy',   'desc' => 'Heavy snow and freezing conditions.'],
        80 => ['label' => 'Rain Showers',       'icon' => '🌦️', 'css' => 'rainy',   'desc' => 'Scattered showers.'],
        81 => ['label' => 'Rain Showers',       'icon' => '🌧️', 'css' => 'rainy',   'desc' => 'Moderate rain showers.'],
        82 => ['label' => 'Heavy Showers',      'icon' => '⛈️',  'css' => 'stormy',  'desc' => 'Heavy shower bursts.'],
        95 => ['label' => 'Thunderstorm',       'icon' => '⛈️',  'css' => 'stormy',  'desc' => 'Active thunderstorm — stay indoors.'],
        96 => ['label' => 'Storm + Hail',       'icon' => '🌩️', 'css' => 'stormy',  'desc' => 'Thunderstorm with hail.'],
        99 => ['label' => 'Heavy Storm + Hail', 'icon' => '🌩️', 'css' => 'stormy',  'desc' => 'Severe storm with heavy hail.'],
    ];
    return $map[$code] ?? ['label' => 'Unknown', 'icon' => '🌡️', 'css' => 'cloudy', 'desc' => 'Weather data unavailable.'];
}

// ─── Resolve location ──────────────────────────────────────────────────────
$city_query = trim($_GET['city'] ?? 'Nashik');
if ($city_query === '') $city_query = 'Nashik';

$place = ['name' => 'Nashik', 'country' => 'India', 'lat' => 19.9975, 'lon' => 73.7898];

$geo = fetch_json('https://geocoding-api.open-meteo.com/v1/search?name=' . urlencode($city_query) . '&count=1&language=en&format=json');
if (!empty($geo['results'][0])) {
    $r = $geo['results'][0];
    $place = [
        'name'    => $r['name']      ?? 'Nashik',
        'country' => $r['country']   ?? 'India',
        'lat'     => $r['latitude']  ?? 19.9975,
        'lon'     => $r['longitude'] ?? 73.7898,
    ];
}

// ─── Fetch weather ─────────────────────────────────────────────────────────
$api_url  = sprintf(
    'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s'
    . '&current=temperature_2m,apparent_temperature,weather_code,wind_speed_10m,relative_humidity_2m,precipitation'
    . '&hourly=temperature_2m,weather_code,precipitation_probability,wind_speed_10m'
    . '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,sunrise,sunset'
    . '&timezone=auto&forecast_days=7',
    $place['lat'], $place['lon']
);

$forecast = fetch_json($api_url);
$fetch_error = empty($forecast)
    ? 'Unable to reach the weather service. Check your server\'s outbound network access (cURL / allow_url_fopen).'
    : null;

$current      = $forecast['current']  ?? [];
$hourly       = $forecast['hourly']   ?? [];
$daily_raw    = $forecast['daily']    ?? [];
$current_meta = weather_meta((int)($current['weather_code'] ?? 0));

// Build hourly cards (next 24 h)
$hourly_cards = [];
$now_ts = time();
if (!empty($hourly['time'])) {
    $count = 0;
    foreach ($hourly['time'] as $i => $t) {
        if (strtotime($t) < $now_ts - 1800) continue;
        if ($count >= 24) break;
        $hourly_cards[] = [
            'time'     => $t,
            'temp'     => $hourly['temperature_2m'][$i] ?? null,
            'code'     => (int)($hourly['weather_code'][$i] ?? 0),
            'precip'   => $hourly['precipitation_probability'][$i] ?? null,
            'wind'     => $hourly['wind_speed_10m'][$i] ?? null,
        ];
        $count++;
    }
}

// Build daily cards
$daily_cards = [];
if (!empty($daily_raw['time'])) {
    foreach ($daily_raw['time'] as $i => $t) {
        $daily_cards[] = [
            'time'    => $t,
            'max'     => $daily_raw['temperature_2m_max'][$i] ?? null,
            'min'     => $daily_raw['temperature_2m_min'][$i] ?? null,
            'code'    => (int)($daily_raw['weather_code'][$i] ?? 0),
            'precip'  => $daily_raw['precipitation_probability_max'][$i] ?? null,
            'sunrise' => $daily_raw['sunrise'][$i] ?? null,
            'sunset'  => $daily_raw['sunset'][$i] ?? null,
        ];
    }
}

$day_names = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weather · <?php echo htmlspecialchars($place['name']); ?></title>

    <!-- AdminLTE deps -->
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    	
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/dist/css/custom.css">
	
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">


    <style>
        /* ── Base ─────────────────────────────────────── */
        :root {
            --sky-1:#f0f6ff; --sky-2:#dbeafe; --sky-3:#2563eb;
            --card-bg:rgba(0,0,0,0.04);
            --card-border:rgba(0,0,0,0.10);
            --text-hi:#0f172a; --text-lo:rgba(15,23,42,.50);
            --accent:#2563eb; --warn:#d97706; --danger:#dc2626;
            --radius:18px; --radius-sm:12px;
        }
        body, .content-wrapper { background:#f0f6ff !important; font-family:'DM Sans',sans-serif; }
        h1,h2,h3,h4,h5,h6 { font-family:'Syne',sans-serif; }

        /* ── Hero ─────────────────────────────────────── */
        .wx-hero {
            border-radius: var(--radius);
            overflow: hidden;
            position: relative;
            color: var(--text-hi);
            border: 1px solid var(--card-border);
        }
        .wx-hero::before {
            content:'';
            position:absolute; inset:0;
            background: radial-gradient(circle at 75% 30%, rgba(56,189,248,.18) 0%, transparent 65%),
                        radial-gradient(circle at 20% 80%, rgba(99,102,241,.12) 0%, transparent 55%);
            pointer-events:none;
        }
        .wx-hero-body { position:relative; z-index:1; padding:2.5rem; }

        /* search bar */
        .wx-search { position:relative; max-width:420px; }
        .wx-search input {
            width:100%; padding:.65rem 1rem;
            background:rgba(114, 92, 92, 0.1); border:1px solid rgba(51, 9, 9, 0.2);
            border-radius: var(--radius-sm); color:#fff; font-size:.95rem;
            font-family:'DM Sans',sans-serif; outline:none;
            transition: background .2s, border-color .2s;
        }
        .wx-search input::placeholder { color:rgba(255,255,255,.5); }
        .wx-search input:focus { background:rgba(255,255,255,.18); border-color:var(--accent); }
        .wx-search button {
            position:absolute; right:6px; top:50%; transform:translateY(-50%);
            background:var(--accent); border:none; color:#0f172a;
            padding:.35rem .85rem; border-radius:8px; cursor:pointer;
            font-weight:600; font-size:.88rem; font-family:'DM Sans',sans-serif;
            transition:background .2s;
        }
        .wx-search button:hover { background:#7dd3fc; }

        /* big temp */
        .wx-big-temp { font-size:5rem; font-weight:800; line-height:1; letter-spacing:-3px; font-family:'Syne',sans-serif; }
        .wx-icon-xl  { font-size:4.5rem; line-height:1; filter:drop-shadow(0 4px 18px rgba(0,0,0,.4)); }

        /* stat pills */
        .wx-pills { display:flex; flex-wrap:wrap; gap:.6rem; margin-top:1.2rem; }
        .wx-pill {
            background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15);
            border-radius:50px; padding:.4rem 1rem; font-size:.85rem; color:var(--text-hi);
            display:flex; align-items:center; gap:.45rem;
        }
        .wx-pill .lbl { color:var(--text-lo); font-size:.78rem; }

        /* ── Section card ─────────────────────────────── */
        .wx-card { background:#ffffff; border:1px solid var(--card-border); border-radius:var(--radius); overflow:hidden; }
        .wx-card-hd {
            padding:1.2rem 1.5rem .8rem;
            border-bottom:1px solid var(--card-border);
            display:flex; align-items:center; gap:.6rem;
            color:var(--text-hi); font-size:.82rem; text-transform:uppercase;
            letter-spacing:.1em; font-weight:600;
        }
        .wx-card-hd .dot { width:8px; height:8px; border-radius:50%; background:var(--accent); }

        /* ── Hourly strip ─────────────────────────────── */
        .hourly-wrap { position:relative; }
        .hourly-strip {
            display:flex; gap:.75rem; padding:1.2rem 1.5rem;
            overflow-x:auto; scroll-behavior:smooth; 
            -webkit-overflow-scrolling:touch; scrollbar-width:none;
        }
        .hourly-strip::-webkit-scrollbar { display:none; }
        .hour-tile {
            flex: 0 0 auto;
            width: clamp(80px, 18vw, 120px); /* responsive width */
            background: #f8faff;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 12px;
            padding: .75rem .5rem;
            text-align: center;
            transition: all .2s ease;
        }
        .hour-tile:hover { background:rgba(37,99,235,.08); border-color:rgba(37,99,235,.3); }
        .hour-tile.now { background:rgba(37,99,235,.1); border-color:var(--accent); }

        .hour-tile .ht   { font-size:.72rem; color:var(--text-lo); margin-bottom:.4rem; }
        .hour-tile .hico { font-size:1.6rem; margin-bottom:.4rem; line-height:1; }
        .hour-tile .htmp { font-size:1rem; font-weight:600; }
        .hour-tile .hpre { font-size:.72rem; color:var(--accent); margin-top:.25rem; }

        /* scroll arrows */
        .scroll-arrow {
            position:absolute; top:50%; transform:translateY(-50%);
            width:34px; height:34px; border-radius:50%;
            background:rgba(15,23,42,.85); border:1px solid var(--card-border);
            color:var(--text-hi); display:flex; align-items:center; justify-content:center;
            cursor:pointer; z-index:5; transition:background .2s;
            font-size:.85rem;
        }
        .scroll-arrow:hover { background:var(--accent); color:#0f172a; }
        .scroll-arrow.left  { left:6px; }
        .scroll-arrow.right { right:6px; }

        /* ── 7-day grid ───────────────────────────────── */
        .day-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr)); gap:.85rem; padding:1.2rem 1.5rem; }
        .day-tile { background:#f8faff; border:1px solid rgba(0,0,0,.08); ... }
        .day-tile:hover { background:rgba(37,99,235,.07); }
        .day-tile.today { border-color:var(--accent); background:rgba(37,99,235,.08); }
        .day-tile .dname { font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; color:var(--text-lo); margin-bottom:.3rem; }
        .day-tile .dico  { font-size:2rem; margin-bottom:.5rem; }
        .day-tile .dlbl  { font-size:.8rem; color:var(--text-lo); margin-bottom:.5rem; }
        .day-tile .dtemps{ display:flex; justify-content:center; gap:.6rem; font-size:.9rem; }
        .day-tile .dmax  { font-weight:700; }
        .day-tile .dmin  { color:var(--text-lo); }
        .day-tile .dprec { font-size:.72rem; color:var(--accent); margin-top:.4rem; }
        .day-tile .dsun  { font-size:.7rem; color:var(--text-lo); margin-top:.35rem; }

        /* ── Alert ────────────────────────────────────── */
        .wx-alert {
            background:rgba(248,113,113,.1); border:1px solid rgba(248,113,113,.3);
            border-radius: var(--radius-sm); padding:1rem 1.2rem;
            color:#fca5a5; display:flex; align-items:flex-start; gap:.75rem;
            margin-bottom:1.25rem;
        }
        .wx-alert .icon { font-size:1.2rem; flex-shrink:0; margin-top:1px; }
        .wx-alert strong { display:block; margin-bottom:.2rem; }
        .wx-alert code {
            display:block; margin-top:.5rem;
            background:rgba(0,0,0,.3); padding:.5rem .75rem;
            border-radius:6px; font-size:.8rem; color:#fde68a;
        }

        /* ── Conditions ───────────────────────────────── */
        .cond-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(130px,1fr)); gap:.85rem; padding:1.2rem 1.5rem; }
        .cond-tile { background:#f8faff; border:1px solid rgba(0,0,0,.08); ... }
        .cond-tile .cico { font-size:1.5rem; margin-bottom:.4rem; }
        .cond-tile .clbl { font-size:.72rem; color:var(--text-lo); margin-bottom:.2rem; text-transform:uppercase; letter-spacing:.06em; }
        .cond-tile .cval { font-size:1.15rem; font-weight:700; }

        /* ── Helpers ──────────────────────────────────── */
        .mt-section { margin-top:1.5rem; }
        .text-lo { color:var(--text-lo) !important; }
        .text-acc { color:var(--accent) !important; }

        /* ── Animations ───────────────────────────────── */
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .wx-hero, .wx-card { animation: fadeUp .5s ease both; }
        .wx-card:nth-child(2) { animation-delay:.08s; }
        .wx-card:nth-child(3) { animation-delay:.16s; }
        .wx-card:nth-child(4) { animation-delay:.24s; }

        /* Breadcrumb / header overrides for dark bg */
        .content-header h1 { color:var(--text-hi) !important; } 
        .breadcrumb-item a, .breadcrumb-item.active { color:var(--text-lo) !important; }
        .breadcrumb-item + .breadcrumb-item::before { color:var(--text-lo) !important; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php include_once('_header.php'); ?>
    <?php include_once('_sidebar.php'); ?>

    <div class="content-wrapper">
        <!-- Page header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-6">
                        <h1 class="m-0">Weather Forecast</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="farmer/dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Weather</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">

                <?php if ($fetch_error): ?>
                <div class="wx-alert">
                    <span class="icon">⚠️</span>
                    <div>
                        <strong>Weather data unavailable</strong>
                        <?php echo htmlspecialchars($fetch_error); ?>
                        <code>
                            Fix options:<br>
                            1. Enable cURL extension in php.ini<br>
                            2. Set allow_url_fopen = On in php.ini<br>
                            3. Ask your host to allow outbound HTTPS to api.open-meteo.com
                        </code>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── HERO ─────────────────────────────────────────── -->
                <div class="wx-hero mb-4">
                    <div class="wx-hero-body">
                        <div class="row align-items-center">

                            <div class="col-lg-7 mb-4 mb-lg-0">
                                <!-- Search -->
                                <form method="GET" class="mb-4">
                                    <div class="wx-search">
                                        <input type="text" name="city"
                                               value="<?php echo htmlspecialchars($city_query); ?>"
                                               placeholder="Search city, village…">
                                        <button type="submit">Search</button>
                                    </div>
                                </form>

                                <!-- Location name -->
                                <div style="font-size:.8rem; color:var(--text-lo); text-transform:uppercase; letter-spacing:.1em; margin-bottom:.3rem;">
                                    📍 <?php echo htmlspecialchars($place['country']); ?>
                                </div>
                                <h2 style="font-size:2.2rem; margin-bottom:.4rem;">
                                    <?php echo htmlspecialchars($place['name']); ?>
                                </h2>
                                <p style="color:var(--text-lo); margin-bottom:0;">
                                    <?php echo htmlspecialchars($current_meta['desc']); ?>
                                </p>

                                <!-- Stat pills -->
                                <div class="wx-pills">
                                    <?php if (isset($current['temperature_2m'])): ?>
                                    <div class="wx-pill">🌡️ <span class="lbl">Feels</span> <?php echo round($current['apparent_temperature']); ?>°C</div>
                                    <?php endif; ?>
                                    <?php if (isset($current['relative_humidity_2m'])): ?>
                                    <div class="wx-pill">💧 <span class="lbl">Humidity</span> <?php echo $current['relative_humidity_2m']; ?>%</div>
                                    <?php endif; ?>
                                    <?php if (isset($current['wind_speed_10m'])): ?>
                                    <div class="wx-pill">💨 <span class="lbl">Wind</span> <?php echo $current['wind_speed_10m']; ?> km/h</div>
                                    <?php endif; ?>
                                    <?php if (isset($current['precipitation'])): ?>
                                    <div class="wx-pill">🌧️ <span class="lbl">Precip</span> <?php echo $current['precipitation']; ?> mm</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-lg-5 text-center">
                                <div class="wx-icon-xl"><?php echo $current_meta['icon']; ?></div>
                                <div class="wx-big-temp">
                                    <?php echo isset($current['temperature_2m']) ? round($current['temperature_2m']) . '°' : '—'; ?>
                                </div>
                                <div style="font-size:1.1rem; color:var(--text-lo); margin-top:.4rem;">
                                    <?php echo htmlspecialchars($current_meta['label']); ?>
                                </div>
                                <div style="font-size:.8rem; color:var(--text-lo); margin-top:.3rem;">
                                    Updated <?php echo date('D, d M · H:i'); ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ── Current Conditions ──────────────────────────── -->
                <?php if (!empty($current)): ?>
                <div class="wx-card mb-4 mt-section">
                    <div class="wx-card-hd"><span class="dot"></span> Current Conditions</div>
                    <div class="cond-grid">
                        <?php
                        $conds = [
                            ['🌡️','Temperature',  isset($current['temperature_2m'])        ? round($current['temperature_2m']).'°C'        : '—'],
                            ['🤔','Feels Like',   isset($current['apparent_temperature'])   ? round($current['apparent_temperature']).'°C'  : '—'],
                            ['💧','Humidity',     isset($current['relative_humidity_2m'])   ? $current['relative_humidity_2m'].'%'          : '—'],
                            ['💨','Wind Speed',   isset($current['wind_speed_10m'])         ? $current['wind_speed_10m'].' km/h'            : '—'],
                            ['🌧️','Precipitation',isset($current['precipitation'])          ? $current['precipitation'].' mm'               : '—'],
                            ['🌤️','Condition',    $current_meta['label'],                                                                       ],
                        ];
                        foreach ($conds as [$ico, $lbl, $val]):
                        ?>
                        <div class="cond-tile">
                            <div class="cico"><?php echo $ico; ?></div>
                            <div class="clbl"><?php echo $lbl; ?></div>
                            <div class="cval"><?php echo htmlspecialchars($val); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── Hourly ──────────────────────────────────────── -->
                <?php if (!empty($hourly_cards)): ?>
                <div class="wx-card mb-4 mt-section">
                    <div class="wx-card-hd"><span class="dot"></span> 24-Hour Forecast</div>
                    <div class="hourly-wrap">
                        <button class="scroll-arrow left" id="sc-left" aria-label="Scroll left">&#8249;</button>
                        <button class="scroll-arrow right" id="sc-right" aria-label="Scroll right">&#8250;</button>
                        <div class="hourly-strip" id="hourly-strip">
                            <?php foreach ($hourly_cards as $i => $h):
                                $hm = weather_meta($h['code']);
                                $is_now = ($i === 0);
                                $htime  = date('h A', strtotime($h['time']));
                            ?>
                            <div class="hour-tile <?php echo $is_now ? 'now' : ''; ?>">
                                <div class="ht"><?php echo $is_now ? 'Now' : $htime; ?></div>
                                <div class="hico"><?php echo $hm['icon']; ?></div>
                                <div class="htmp"><?php echo isset($h['temp']) ? round($h['temp']).'°' : '—'; ?></div>
                                <?php if (isset($h['precip'])): ?>
                                <div class="hpre">💧 <?php echo $h['precip']; ?>%</div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── 7-day ────────────────────────────────────────── -->
                <?php if (!empty($daily_cards)): ?>
                <div class="wx-card mb-4 mt-section">
                    <div class="wx-card-hd"><span class="dot"></span> 7-Day Forecast</div>
                    <div class="day-grid">
                        <?php
                        $today_str = date('Y-m-d');
                        foreach ($daily_cards as $i => $d):
                            $dm       = weather_meta($d['code']);
                            $is_today = ($d['time'] === $today_str);
                            $dow      = $is_today ? 'Today' : date('D', strtotime($d['time']));
                            $ddate    = date('d M', strtotime($d['time']));
                        ?>
                        <div class="day-tile <?php echo $is_today ? 'today' : ''; ?>">
                            <div class="dname"><?php echo $dow; ?> <span style="font-size:.65rem"><?php echo $ddate; ?></span></div>
                            <div class="dico"><?php echo $dm['icon']; ?></div>
                            <div class="dlbl"><?php echo htmlspecialchars($dm['label']); ?></div>
                            <div class="dtemps">
                                <span class="dmax"><?php echo isset($d['max']) ? round($d['max']).'°' : '—'; ?></span>
                                <span class="dmin"><?php echo isset($d['min']) ? round($d['min']).'°' : '—'; ?></span>
                            </div>
                            <?php if (isset($d['precip'])): ?>
                            <div class="dprec">💧 <?php echo $d['precip']; ?>%</div>
                            <?php endif; ?>
                            <?php if (!empty($d['sunrise']) && !empty($d['sunset'])): ?>
                            <div class="dsun">
                                🌅 <?php echo date('h:i A', strtotime($d['sunrise'])); ?> &nbsp;
                                🌇 <?php echo date('h:i A', strtotime($d['sunset'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($hourly_cards) && empty($daily_cards) && !$fetch_error): ?>
                <div class="wx-card mb-4" style="padding:3rem; text-align:center; color:var(--text-lo);">
                    <div style="font-size:3rem; margin-bottom:1rem;">🌐</div>
                    <p>No weather data available for this location.</p>
                </div>
                <?php endif; ?>

            </div><!-- /container-fluid -->
        </section>
    </div><!-- /content-wrapper -->
</div><!-- /wrapper -->

<!-- Scripts -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button)</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/dist/js/custom.js"></script>
<script>
(function () {
    const strip  = document.getElementById('hourly-strip');
    const btnL   = document.getElementById('sc-left');
    const btnR   = document.getElementById('sc-right');

    if (!strip) return;

    const STEP = 280;
    btnL && btnL.addEventListener('click', () => strip.scrollBy({ left: -STEP, behavior: 'smooth' }));
    btnR && btnR.addEventListener('click', () => strip.scrollBy({ left:  STEP, behavior: 'smooth' }));

    // Touch / mouse drag
    let drag = false, startX = 0, scrollStart = 0;
    strip.addEventListener('mousedown',  e => { drag = true; startX = e.pageX; scrollStart = strip.scrollLeft; strip.style.cursor='grabbing'; });
    strip.addEventListener('mouseup',    () => { drag = false; strip.style.cursor=''; });
    strip.addEventListener('mouseleave', () => { drag = false; strip.style.cursor=''; });
    strip.addEventListener('mousemove',  e => { if (!drag) return; e.preventDefault(); strip.scrollLeft = scrollStart - (e.pageX - startX); });
    strip.addEventListener('touchstart', e => { startX = e.touches[0].pageX; scrollStart = strip.scrollLeft; }, { passive:true });
    strip.addEventListener('touchmove',  e => { strip.scrollLeft = scrollStart - (e.touches[0].pageX - startX); }, { passive:true });
})();


const strip = document.getElementById('hourly-strip');

if (strip) {
    const nowCard = strip.querySelector('.hour-tile.now');
    if (nowCard) {
        nowCard.scrollIntoView({
            behavior: 'smooth',
            inline: 'center'
        });
    }
}
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>