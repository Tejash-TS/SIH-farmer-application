

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-light-secondary elevation-4">
    <!-- Brand Logo -->
    <a href="admin/dashboard" class="brand-link">
      <img src="./assets/dist/img/logos/favicon.ico" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">CropIntel</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="<?= $_SESSION['user']['image']?>" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block"><?= $_SESSION['user']['name']?></a>
        </div>
      </div>

      

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
          <!-- Dashboard -->
          <li class="nav-item">
            <a href="admin/dashboard" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <!-- Management Section -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Management
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="admin/video_tutorial.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Video Management</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="admin/vendors_consultants.php?tab=vendors" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Vendors</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="admin/farmers.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Farmers</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="admin/consultants.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Consultants</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="admin/product_approval.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Products</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="admin/users_management.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Users</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Content Section -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>
                Content
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="admin/diseases.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Diseases</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="admin/video_tutorial.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Video Tutorials</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Reports Section -->
          <li class="nav-item">
            <a href="admin/reports.php" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>Reports</p>
            </a>
          </li>

          <!-- Orders Section -->
          <li class="nav-item">
            <a href="admin/orders.php" class="nav-link">
              <i class="nav-icon fas fa-shopping-cart"></i>
              <p>Orders</p>
            </a>
          </li>

          <!-- Announcements Section -->
          <li class="nav-item">
            <a href="admin/announcements.php" class="nav-link">
              <i class="nav-icon fas fa-bullhorn"></i>
              <p>Announcements</p>
            </a>
          </li>

          <!-- Logout -->
          <li class="nav-item">
            <a href="logout" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>
          
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>