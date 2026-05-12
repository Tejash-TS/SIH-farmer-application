<aside class="main-sidebar sidebar-light-primary elevation-4">
	<a href="vendor/dashboard" class="brand-link">
		<img src="./assets/dist/img/logos/favicon.ico" alt="CropIntel Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
		<span class="brand-text font-weight-light">CropIntel Vendor</span>
	</a>
	<div class="sidebar">
		<div class="user-panel mt-3 pb-3 mb-3 d-flex">
			<div class="image">
				<img src="<?= !empty($_SESSION['user']['image']) ? $_SESSION['user']['image'] : 'assets/dist/img/user2-160x160.jpg' ?>" class="img-circle elevation-2" alt="User Image">
			</div>
			<div class="info">
				<a href="#" class="d-block"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Vendor') ?></a>
			</div>
		</div>
		<nav class="mt-2">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
				<li class="nav-item">
					<a href="vendor/dashboard" class="nav-link">
						<i class="nav-icon fas fa-tachometer-alt"></i>
						<p>Dashboard</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="vendor/profile" class="nav-link">
						<i class="nav-icon fas fa-id-card"></i>
						<p>Profile</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="vendor/add_product" class="nav-link">
						<i class="nav-icon fas fa-box-open"></i>
						<p>Add Product</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="vendor/products" class="nav-link">
						<i class="nav-icon fas fa-clipboard-list"></i>
						<p>My Products</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="vendor/orders" class="nav-link">
						<i class="nav-icon fas fa-shopping-cart"></i>
						<p>Orders</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="vendor/feedback" class="nav-link">
						<i class="nav-icon fas fa-comments"></i>
						<p>Send Feedback</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="logout" class="nav-link">
						<i class="nav-icon fas fa-sign-out-alt"></i>
						<p>Logout</p>
					</a>
				</li>
			</ul>
		</nav>
	</div>
</aside>
