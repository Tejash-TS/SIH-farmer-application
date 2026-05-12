

  <aside class="main-sidebar sidebar-light-secondary elevation-4">
    
   <a href="farmer/dashboard" class="brand-link">
      <img src="./assets/dist/img/logos/favicon.ico" alt=" Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">CropIntel</span>
    </a>
    <div class="sidebar">
      
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
			<div class="image">
			  <img src="<?= $_SESSION['user']['image']?>" class="img-circle elevation-2" alt="User Image">
			</div>
		 </div>
        <div class="info">
          <a href="#" class="d-block"><?= $_SESSION['user']['name']?></a>
        </div> 
      </div>

      

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
          <li class="nav-item">
            <a href="farmer/dashboard" class="nav-link ">
			 <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>
          </li>

          <!-- NEW FEATURES: Farmer Products -->
          <li class="nav-item">
            <a href="farmer/add_farm_product" class="nav-link ">
              <i class="nav-icon fas fa-plus-circle"></i>
              <p>
                Add Farm Product
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/farmer_products" class="nav-link ">
              <i class="nav-icon fas fa-list"></i>
              <p>
                My Products
              </p>
            </a>
          </li>

          <li class="nav-item">
            <a href="farmer/diseases_predict" class="nav-link ">
				<i class="fa-solid fa-seedling"></i>
              <p>
                diseases predict
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/video_tutorials" class="nav-link ">
				<i class="fa-solid fa-video"></i>
              <p>
                Video Tutorials
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/crop_price" class="nav-link ">
				<i class="fa-solid fa-store"></i>
              <p>
                Crop Market
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/supsidy" class="nav-link ">
			<i class="fa-solid fa-wallet"></i>
              <p>
                supsidy
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/whether" class="nav-link ">
				<i class="fa-solid fa-cloud"></i>
              <p>
                Weather
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/order_history" class="nav-link ">
				<i class="fas fa-shopping-bag"></i>
              <p>
                Order History
              </p>
            </a>
          </li>

          <!-- NEW FEATURES: Farmer Profile & Sales -->
          <li class="nav-item">
            <a href="farmer/profile" class="nav-link ">
              <i class="nav-icon fas fa-user"></i>
              <p>
                My Profile
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/sales_dashboard" class="nav-link ">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>
                Sales Dashboard
              </p>
            </a>
          </li>

          <!-- Consultancy Services -->
          <li class="nav-item">
            <a href="farmer/browse_consultants" class="nav-link ">
              <i class="nav-icon fas fa-user-tie"></i>
              <p>
                Hire Consultant
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="farmer/my_consultancies" class="nav-link ">
              <i class="nav-icon fas fa-handshake"></i>
              <p>
                My Consultancies
              </p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="farmer/feedback" class="nav-link ">
              <i class="fas fa-comments"></i>
              <p>
                Send Feedback
              </p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="logout" class="nav-link ">
				<i class="fa-solid fa-right-from-bracket"></i>
              <p>
                Logout
              </p>
            </a>
          </li>
          
          
          
        </ul>
      </nav>
    </div>
   
  </aside>