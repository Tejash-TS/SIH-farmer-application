<?php
// Buyer Navigation Header
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <?php include_once(__DIR__ . '/../_announcement_notification.php'); ?>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="buyer/profile" class="dropdown-item">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="buyer/orders" class="dropdown-item">
                    <i class="fas fa-box"></i> My Orders
                </a>
                <div class="dropdown-divider"></div>
                <a href="../logout.php" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="buyer/cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="badge badge-danger navbar-badge">0</span>
            </a>
        </li>
    </ul>
</nav>
