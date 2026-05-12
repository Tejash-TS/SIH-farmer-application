<?php
// Buyer Navigation Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="main-sidebar sidebar-light-primary elevation-4">
    <a href="buyer/dashboard" class="brand-link">
        <img src="<?=$_SESSION['user']['image']?>" alt="SIH Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">SIH - Buyer</span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="buyer/dashboard" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="buyer/browse_products" class="nav-link <?php echo $current_page == 'browse_products.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-shopping-bag"></i>
                        <p>Browse Products</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="buyer/farmer_products" class="nav-link <?php echo $current_page == 'farmer_products.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-apple-alt"></i>
                        <p>Farm Fresh</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="buyer/vendor_products" class="nav-link <?php echo $current_page == 'vendor_products.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-store"></i>
                        <p>From Vendors</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="buyer/cart" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>My Cart</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="buyer/orders" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-box"></i>
                        <p>My Orders</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="buyer/profile" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user"></i>
                        <p>My Profile</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="buyer/feedback" class="nav-link <?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Send Feedback</p>
                    </a>
                </li>
                
                <li class="nav-divider"></li>
                
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
