<?php
session_start();
include_once('../_functions.php');

if (!isset($_SESSION['user'])) {
    header("location:../login");
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

/* ── RSS / JSON sources ── */
$sources = [
    ['name' => 'PIB Releases',    'url' => 'https://pib.gov.in/RSSNewRelease.aspx', 'type' => 'rss'],
    ['name' => 'National Portal', 'url' => 'https://www.india.gov.in/rss',           'type' => 'rss'],
];

function fetch_url(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; CropIntel/1.0)',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body ?: null;
    }
    if (ini_get('allow_url_fopen')) {
        $ctx  = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body ?: null;
    }
    return null;
}

function parse_rss(string $xml, string $src): array {
    $out = [];
    libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$doc) return [];
    $items = $doc->channel->item ?? $doc->entry ?? [];
    foreach ($items as $it) {
        $out[] = [
            'title'  => trim((string)($it->title ?? '')),
            'link'   => trim((string)($it->link['href'] ?? $it->link ?? '')),
            'date'   => trim((string)($it->pubDate ?? $it->updated ?? $it->published ?? '')),
            'source' => $src,
        ];
    }
    return $out;
}

function get_feed_items(array $sources): array {
    $all = [];
    foreach ($sources as $s) {
        $body = fetch_url($s['url']);
        if (!$body) continue;
        if (($s['type'] ?? 'rss') === 'json') {
            $data = json_decode($body, true);
            if (!is_array($data)) continue;
            $rows = $data['items'] ?? $data['data'] ?? $data;
            foreach ((array)$rows as $it) {
                $all[] = ['title' => $it['title'] ?? '', 'link' => $it['link'] ?? $it['url'] ?? '', 'date' => $it['date'] ?? '', 'source' => $s['name']];
            }
        } else {
            $all = array_merge($all, parse_rss($body, $s['name']));
        }
    }
    usort($all, fn($a,$b) => (strtotime($b['date']??'')?:0) <=> (strtotime($a['date']??'')?:0));
    return array_slice($all, 0, 25);
}

/* ── AJAX endpoint ── */
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(get_feed_items($sources));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subsidies &amp; Schemes – CropIntel</title>

    <!-- AdminLTE core (kept for sidebar/header) -->
    <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">


    <style>
    /* ══════════════════════════════════════════
       ROOT TOKENS
    ══════════════════════════════════════════ */
    :root {
        --leaf:      #1e7c3a;
        --leaf-lt:   #27a74f;
        --leaf-pale: #e8f7ed;
        --soil:      #7a4f2d;
        --sky:       #1a4a6b;
        --amber:     #d97706;
        --teal:      #0f766e;
        --purple:    #7c3aed;
        --coral:     #dc4f2e;
        --cream:     #faf8f4;
        --dark:      #111815;
        --card-bg:   #ffffff;
        --radius:    16px;
        --shadow:    0 4px 24px rgba(0,0,0,.08);
        --shadow-lg: 0 12px 40px rgba(0,0,0,.14);
        --font-head: 'Playfair Display', Georgia, serif;
        --font-body: 'DM Sans', sans-serif;
    }

    /* ══════════════════════════════════════════
       GLOBAL OVERRIDES (works inside AdminLTE wrapper)
    ══════════════════════════════════════════ */
    .content-wrapper { background: var(--cream); font-family: var(--font-body); }
    .content-header h1 { font-family: var(--font-head); color: var(--dark); font-size: 2rem; }

    /* ══════════════════════════════════════════
       HERO
    ══════════════════════════════════════════ */
    .ss-hero {
        position: relative;
        overflow: hidden;
        border-radius: var(--radius);
        background: var(--dark);
        padding: 4rem 3rem;
        margin-bottom: 3rem;
        color: #fff;
    }
    .ss-hero::before {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(ellipse 60% 80% at 80% 50%, rgba(30,124,58,.55) 0%, transparent 70%),
            radial-gradient(ellipse 40% 60% at 10% 80%, rgba(26,74,107,.45) 0%, transparent 70%);
    }
    .ss-hero-grain {
        position: absolute; inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.06'/%3E%3C/svg%3E");
        background-size: 180px;
        opacity: .4;
        pointer-events: none;
    }
    .ss-hero-inner { position: relative; z-index: 1; max-width: 600px; }
    .ss-hero-tag {
        display: inline-block;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.2);
        color: #b6f5c8;
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        padding: .3rem .85rem;
        border-radius: 999px;
        margin-bottom: 1.25rem;
    }
    .ss-hero h2 {
        font-family: var(--font-head);
        font-size: clamp(2rem,4vw,3.2rem);
        line-height: 1.15;
        margin: 0 0 1rem;
        color: #fff;
    }
    .ss-hero p { color: rgba(255,255,255,.75); font-size: 1.05rem; max-width: 480px; margin-bottom: 1.75rem; }
    .ss-hero-btn {
        display: inline-block;
        background: var(--leaf-lt);
        color: #fff;
        padding: .75rem 2rem;
        border-radius: 999px;
        font-weight: 600;
        font-size: .95rem;
        text-decoration: none;
        transition: background .25s, transform .2s;
        box-shadow: 0 4px 16px rgba(39,167,79,.35);
    }
    .ss-hero-btn:hover { background: var(--leaf); transform: translateY(-2px); color: #fff; }

    .ss-hero-art {
        position: absolute;
        right: -30px; top: 50%;
        transform: translateY(-50%);
        font-size: 14rem;
        opacity: .07;
        line-height: 1;
        pointer-events: none;
        user-select: none;
    }

    /* ══════════════════════════════════════════
       SECTION TITLE
    ══════════════════════════════════════════ */
    .ss-section-title {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.75rem;
    }
    .ss-section-title h3 {
        font-family: var(--font-head);
        font-size: 1.6rem;
        color: var(--dark);
        margin: 0;
    }
    .ss-section-title .ss-pill {
        background: var(--leaf-pale);
        color: var(--leaf);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        padding: .25rem .75rem;
        border-radius: 999px;
    }

    /* ══════════════════════════════════════════
       SCHEME CARDS
    ══════════════════════════════════════════ */
    .ss-card {
        background: var(--card-bg);
        border-radius: var(--radius);
        padding: 2rem 1.5rem;
        height: 100%;
        box-shadow: var(--shadow);
        border-top: 4px solid var(--accent, var(--leaf));
        transition: transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s ease;
        position: relative;
        overflow: hidden;
    }
    .ss-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 85% 15%, rgba(var(--accent-rgb,.4,.4,.4),.06) 0%, transparent 60%);
        pointer-events: none;
    }
    .ss-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }
    .ss-card-icon {
        width: 56px; height: 56px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 1.1rem;
        background: rgba(var(--accent-rgb), .12);
        color: var(--accent, var(--leaf));
        transition: transform .4s ease;
    }
    .ss-card:hover .ss-card-icon { transform: rotate(15deg) scale(1.1); }
    .ss-card h5 {
        font-family: var(--font-head);
        font-size: 1.1rem;
        margin: 0 0 .5rem;
        color: var(--dark);
    }
    .ss-card p { font-size: .9rem; color: #555; line-height: 1.6; margin: 0; }

    /* colour variants */
    .ss-c-green  { --accent: #1e7c3a; --accent-rgb: 30,124,58; }
    .ss-c-blue   { --accent: #1a4a6b; --accent-rgb: 26,74,107; }
    .ss-c-amber  { --accent: #d97706; --accent-rgb: 217,119,6; }
    .ss-c-purple { --accent: #7c3aed; --accent-rgb: 124,58,237; }
    .ss-c-teal   { --accent: #0f766e; --accent-rgb: 15,118,110; }
    .ss-c-coral  { --accent: #dc4f2e; --accent-rgb: 220,79,46; }

    /* ══════════════════════════════════════════
       BENEFITS STRIP
    ══════════════════════════════════════════ */
    .ss-benefits {
        background: var(--leaf);
        border-radius: var(--radius);
        padding: 2.5rem 2rem;
        margin: 2.5rem 0;
        color: #fff;
    }
    .ss-benefits h3 {
        font-family: var(--font-head);
        font-size: 1.5rem;
        margin: 0 0 1.5rem;
        color: #fff;
    }
    .ss-benefit-item {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .85rem 1.1rem;
        border-radius: 10px;
        background: rgba(255,255,255,.08);
        margin-bottom: .65rem;
        transition: background .25s, transform .2s;
        cursor: default;
    }
    .ss-benefit-item:last-child { margin-bottom: 0; }
    .ss-benefit-item:hover { background: rgba(255,255,255,.16); transform: translateX(4px); }
    .ss-benefit-item i { font-size: 1.1rem; color: #86efac; margin-top: .1rem; flex-shrink: 0; }
    .ss-benefit-item span { font-size: .95rem; color: rgba(255,255,255,.92); }

    /* ══════════════════════════════════════════
       LIVE FEED
    ══════════════════════════════════════════ */
    .ss-feed-card {
        background: var(--card-bg);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 2.5rem;
    }
    .ss-feed-header {
        background: var(--dark);
        padding: 1.1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .ss-feed-header h4 {
        font-family: var(--font-head);
        color: #fff;
        margin: 0;
        font-size: 1.15rem;
    }
    .ss-live-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34,197,94,.4);
        animation: pulse-dot 1.8s infinite;
        flex-shrink: 0;
    }
    @keyframes pulse-dot {
        0%   { box-shadow: 0 0 0 0 rgba(34,197,94,.5); }
        70%  { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
        100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }
    .ss-feed-meta { font-size: .78rem; color: rgba(255,255,255,.45); margin-left: auto; }

    .ss-feed-body { padding: 1rem 1.5rem; }
    .ss-feed-item {
        display: flex;
        gap: 1rem;
        padding: .85rem 0;
        border-bottom: 1px solid #f0f0f0;
        align-items: flex-start;
        animation: fadeInUp .4s ease both;
    }
    .ss-feed-item:last-child { border-bottom: none; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .ss-feed-num {
        width: 28px; height: 28px;
        border-radius: 8px;
        background: var(--leaf-pale);
        color: var(--leaf);
        font-size: .75rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        margin-top: .1rem;
    }
    .ss-feed-content a {
        font-size: .92rem;
        font-weight: 600;
        color: var(--dark);
        text-decoration: none;
        line-height: 1.45;
        display: block;
    }
    .ss-feed-content a:hover { color: var(--leaf); text-decoration: underline; }
    .ss-feed-content .ss-feed-source {
        font-size: .75rem;
        color: #888;
        margin-top: .25rem;
    }
    .ss-feed-source span { font-weight: 600; color: var(--leaf); }

    /* skeleton loader */
    .ss-skeleton { background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 6px; height: 14px; margin-bottom: 6px; }
    @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

    /* ══════════════════════════════════════════
       CONTACT BAND
    ══════════════════════════════════════════ */
    .ss-contact {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 2rem 2rem;
        display: flex;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }
    .ss-contact-icon {
        width: 52px; height: 52px;
        background: var(--leaf-pale);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        color: var(--leaf);
        flex-shrink: 0;
    }
    .ss-contact h5 { font-family: var(--font-head); font-size: 1.1rem; margin: 0 0 .3rem; }
    .ss-contact p  { margin: 0; color: #555; font-size: .9rem; }
    .ss-contact a  { color: var(--leaf); font-weight: 600; text-decoration: none; }
    .ss-contact a:hover { text-decoration: underline; }
    .ss-contact-divider { width: 1px; height: 48px; background: #eee; flex-shrink: 0; }

    /* ══════════════════════════════════════════
       STAGGER ENTRY ANIMATION
    ══════════════════════════════════════════ */
    .ss-stagger { opacity: 0; transform: translateY(20px); transition: opacity .5s ease, transform .5s ease; }
    .ss-stagger.visible { opacity: 1; transform: none; }
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
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Subsidies &amp; Schemes</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Subsidies &amp; Schemes</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
        <div class="container-fluid">

            <!-- ── HERO ── -->
            <div class="ss-hero ss-stagger">
                <div class="ss-hero-grain"></div>
                <div class="ss-hero-art">🌾</div>
                <div class="ss-hero-inner">
                    <span class="ss-hero-tag">Government of India</span>
                    <h2>Protect Your Crops,<br>Empower Your Farm</h2>
                    <p>Explore subsidies and government schemes designed to help farmers prevent crop disease, improve yields, and secure their livelihoods.</p>
                    <a href="javascript:void(0)" onclick="document.getElementById('schemes').scrollIntoView({behavior:'smooth'})" class="ss-hero-btn">Explore Schemes ↓</a>
                </div>
            </div>
  
            <!-- ── SCHEMES GRID ── -->
            <div class="ss-section-title ss-stagger" id="schemes">
                <h3>Government Schemes</h3>
                <span class="ss-pill">6 Active</span>
            </div>

            <div class="row mb-4">
                <?php
                $schemes = [
                    ['c'=>'ss-c-green',  'icon'=>'fa-seedling',           'title'=>'Crop Protection Scheme',    'text'=>'Financial assistance for pesticides and disease-resistant seeds to secure healthy crop yields.'],
                    ['c'=>'ss-c-blue',   'icon'=>'fa-shield-alt',          'title'=>'Agricultural Insurance',    'text'=>'Compensation for crop losses caused by disease, pests, or adverse weather conditions.'],
                    ['c'=>'ss-c-amber',  'icon'=>'fa-chalkboard-teacher',  'title'=>'Training Programs',         'text'=>'Workshops on modern farming techniques, pest management, and disease prevention practices.'],
                    ['c'=>'ss-c-purple', 'icon'=>'fa-flask',               'title'=>'Soil Testing Support',      'text'=>'Free soil health analysis to optimise fertiliser usage and prevent nutrient-linked diseases.'],
                    ['c'=>'ss-c-teal',   'icon'=>'fa-tint',                'title'=>'Irrigation Assistance',     'text'=>'Subsidies for drip and sprinkler systems to ensure proper hydration and disease prevention.'],
                    ['c'=>'ss-c-coral',  'icon'=>'fa-leaf',                'title'=>'Organic Farming Incentive', 'text'=>'Grants for farmers transitioning to organic practices that reduce chemicals and restore soil health.'],
                ];
                foreach ($schemes as $i => $s): ?>
                <div class="col-lg-4 col-md-6 mb-3 ss-stagger" style="transition-delay:<?= $i * 60 ?>ms">
                    <div class="ss-card <?= $s['c'] ?>">
                        <div class="ss-card-icon">
                            <i class="fas <?= $s['icon'] ?>"></i>
                        </div>
                        <h5><?= $s['title'] ?></h5>
                        <p><?= $s['text'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── BENEFITS STRIP ── -->
            <div class="ss-benefits ss-stagger">
                <h3>Subsidy &amp; Benefits at a Glance</h3>
                <div class="row">
                    <div class="col-md-6">
                        <?php
                        $benefits = [
                            'Up to 50% subsidy on crop protection chemicals',
                            'Interest-free loans for disease prevention equipment',
                            'Free soil and plant health diagnostic tests',
                        ];
                        foreach ($benefits as $b): ?>
                        <div class="ss-benefit-item">
                            <i class="fas fa-check-circle"></i>
                            <span><?= $b ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        $benefits2 = [
                            'Grants for irrigation infrastructure upgrades',
                            'Subsidised certified organic-farming training',
                            'Priority credit access for marginal farmers',
                        ];
                        foreach ($benefits2 as $b): ?>
                        <div class="ss-benefit-item">
                            <i class="fas fa-check-circle"></i>
                            <span><?= $b ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ── LIVE FEED ── -->
            <div class="ss-section-title ss-stagger mt-4">
                <h3>Live Government Updates</h3>
                <span class="ss-pill">Auto-refresh 60s</span>
            </div>

            <div class="ss-feed-card ss-stagger">
                <div class="ss-feed-header">
                    <div class="ss-live-dot"></div>
                    <h4>Official News &amp; Advisories</h4>
                    <span class="ss-feed-meta">Updated: <span id="ss-ts">—</span></span>
                </div>
                <div class="ss-feed-body">
                    <div id="ss-feed-list">
                        <!-- skeleton -->
                        <?php for($i=0;$i<5;$i++): ?>
                        <div class="ss-feed-item">
                            <div class="ss-skeleton" style="width:28px;height:28px;border-radius:8px;flex-shrink:0"></div>
                            <div style="flex:1">
                                <div class="ss-skeleton" style="width:90%"></div>
                                <div class="ss-skeleton" style="width:40%;margin-top:6px"></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- ── CONTACT ── -->
            <div class="ss-contact ss-stagger">
                <div class="ss-contact-icon"><i class="fas fa-envelope"></i></div>
                <div>
                    <h5>Email Support</h5>
                    <p>Send us your queries at <a href="mailto:support@cropintel.com">support@cropintel.com</a></p>
                </div>
                <div class="ss-contact-divider d-none d-md-block"></div>
                <div class="ss-contact-icon"><i class="fas fa-phone-alt"></i></div>
                <div>
                    <h5>Helpline (Toll Free)</h5>
                    <p>Call us at <a href="tel:1800123456">1800-123-456</a> — Mon to Sat, 9 AM – 6 PM</p>
                </div>
                <div class="ss-contact-divider d-none d-md-block"></div>
                <div class="ss-contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <h5>Nearest Krishi Kendra</h5>
                    <p>Visit your local agriculture office for in-person assistance</p>
                </div>
            </div>

        </div>
        </section>
    </div><!-- /.content-wrapper -->

</div><!-- /.wrapper -->

<!-- Scripts -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button);</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/dist/js/custom.js"></script>

<script>
/* ── Stagger reveal on scroll ── */
(function () {
    var els = document.querySelectorAll('.ss-stagger');
    var io = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); }
        });
    }, {threshold:.12});
    els.forEach(function(el){ io.observe(el); });
    /* trigger any already visible */
    setTimeout(function(){ els.forEach(function(el){ io.observe(el); }); }, 50);
})();

/* ── Live feed ── */
(function () {
    var container = document.getElementById('ss-feed-list');
    var tsEl = document.getElementById('ss-ts');

    function render(items) {
        if (!container) return;
        container.innerHTML = '';

        if (!items || !items.length) {
            container.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#888"><i class="fas fa-satellite-dish mr-2"></i>No recent government updates found.</div>';
            return;
        }

        items.forEach(function (it, idx) {
            var div = document.createElement('div');
            div.className = 'ss-feed-item';
            div.style.animationDelay = (idx * 40) + 'ms';

            var num = document.createElement('div');
            num.className = 'ss-feed-num';
            num.textContent = idx + 1;

            var body = document.createElement('div');
            body.className = 'ss-feed-content';

            var a = document.createElement('a');
            a.href = it.link || '#';
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.textContent = it.title || '(no title)';

            var meta = document.createElement('div');
            meta.className = 'ss-feed-source';

            var srcSpan = document.createElement('span');
            srcSpan.textContent = it.source || '';
            meta.appendChild(srcSpan);

            if (it.date) {
                var dateStr = document.createTextNode(' · ' + it.date);
                meta.appendChild(dateStr);
            }

            body.appendChild(a);
            body.appendChild(meta);
            div.appendChild(num);
            div.appendChild(body);
            container.appendChild(div);
        });

        if (tsEl) tsEl.textContent = new Date().toLocaleString('en-IN');
    }

    function load() {
        // ← update 'subsidy.php' if your filename differs
        
fetch(window.location.pathname + '?ajax=1')
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(render)
            .catch(function (err) {
                if (container) {
                    container.innerHTML =
                        '<div style="padding:1.2rem 0;color:#dc4f2e"><i class="fas fa-exclamation-circle mr-2"></i>Could not load updates — ' + err.message + '</div>';
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        load();
        setInterval(load, 60000);
    });
})();
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>