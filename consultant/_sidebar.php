<aside class="main-sidebar sidebar-light-info elevation-4">
	<a href="consultant/dashboard" class="brand-link">
		<img src="./assets/dist/img/logos/favicon.ico" alt="CropIntel Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
		<span class="brand-text font-weight-light">CropIntel Consultant</span>
	</a>
	<div class="sidebar">
		<div class="user-panel mt-3 pb-3 mb-3 d-flex">
			<div class="image">
				<img src="<?= !empty($_SESSION['user']['image']) ? $_SESSION['user']['image'] : 'assets/dist/img/user2-160x160.jpg' ?>" class="img-circle elevation-2" alt="User Image">
			</div>
			<div class="info">
				<a href="#" class="d-block"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Consultant') ?></a>
			</div>
		</div>
		<nav class="mt-2">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
				<li class="nav-item">
					<a href="consultant/dashboard" class="nav-link">
						<i class="nav-icon fas fa-tachometer-alt"></i>
						<p>Dashboard</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="consultant/profile" class="nav-link">
						<i class="nav-icon fas fa-user-tie"></i>
						<p>Profile</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="consultant/videos" class="nav-link">
						<i class="nav-icon fas fa-video"></i>
						<p>My Videos</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="consultant/upload_video" class="nav-link">
						<i class="nav-icon fas fa-upload"></i>
						<p>Upload Video</p>
					</a>
				</li>
				
				<!-- Consultancy Services -->
				<li class="nav-item">
					<a href="consultant/manage_services" class="nav-link">
						<i class="nav-icon fas fa-briefcase"></i>
						<p>My Services</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="consultant/my_clients" class="nav-link">
						<i class="nav-icon fas fa-users"></i>
						<p>My Clients</p>
					</a>
				</li>
				
				<li class="nav-item">
					<a href="consultant/feedback" class="nav-link">
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
