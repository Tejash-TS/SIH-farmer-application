<?php
	session_start();
	include_once('../_functions.php');
	if(!isset($_SESSION['user']))
	{
		header("location:../login");
		exit;
	}
	else
	{
		check_role($_SESSION['user']['role'],basename(__DIR__));

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>
	<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  
  <link rel="stylesheet" href="assets/dist/css/custom.css">
  
<style>
/* Carousel */
.carousel-container {
  position: relative;
  overflow: hidden;
}
.hourly-carousel {
  display: flex;
  gap: 10px;
  transition: transform 0.3s ease;
  flex-wrap: nowrap;
  overflow-x: hidden; /* hide scrollbar */
  white-space: nowrap;
  scroll-behavior: smooth;
}

/* Hour cards */
.hour-card {
  flex: 0 0 auto;
  min-width: 100px;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(6px);
  border-radius: 15px;
  color: #fff;
  transition: transform 0.3s ease, background 0.3s ease;
  text-align: center;
  padding: 10px;
  transform-origin: bottom center;
}
.hour-card:hover {
  transform: scale(1.15);
  background: rgba(255,255,255,0.3);
}

/* Buttons */
.carousel-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
  background: rgba(0,0,0,0.3);
  border: none;
  color: #fff;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  cursor: pointer;
}
.carousel-btn.left { left: 5px; }
.carousel-btn.right { right: 5px; }
.carousel-btn:hover { background: rgba(0,0,0,0.6); }

/* Icons & Animations */
.icon-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0 auto 8px auto;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  font-size: 1.5rem;
}
.sun-spin { animation: spin 6s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
.cloud-move { animation: move 5s ease-in-out infinite alternate; }
@keyframes move { 0% { transform: translateY(0px); } 100% { transform: translateY(5px); } }
.rain-drop { animation: rain 0.5s infinite; }
@keyframes rain { 0% { transform: translateY(0); } 50% { transform: translateY(4px); } 100% { transform: translateY(0); } }
.temp { font-weight: 600; font-size: 1.1rem; }
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
            <h1 class="m-0">Weather</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="admin">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
	
    <section class="content">
      <div class="container-fluid">
		<div class="row">
		  <div class="col-lg-12 position-relative">
			<div class="card text-white shadow-lg" style="background: linear-gradient(135deg, #4facfe 0%, #6a11cb 100%);">
			  <div class="card-header border-0 bg-transparent">
				<h3 class="card-title">
				  <i class="fas fa-cloud-sun"></i>
				  Hourly Weather
				</h3>
			  </div>
			  <div class="card-body">
				<div class="current-weather mb-4 text-center">
				  <h2 class="mb-1" style="font-weight:700;"><i class="fas fa-thermometer-half"></i> 29°C</h2>
				  <p class="mb-0" style="font-size:1.1rem;">Sunny | Nashik, India</p>
				</div>

				<div class="carousel-container position-relative">
				  <button class="carousel-btn left"><i class="fas fa-chevron-left"></i></button>
				  <button class="carousel-btn right"><i class="fas fa-chevron-right"></i></button>

				  <div class="hourly-carousel d-flex">
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-sun sun-spin"></i></div>
					  <h6>10 AM</h6>
					  <p class="mb-0 temp">30°C</p>
					  <small>Sunny</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-sun sun-spin"></i></div>
					  <h6>11 AM</h6>
					  <p class="mb-0 temp">32°C</p>
					  <small>Sunny</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-info text-white mb-2"><i class="fas fa-cloud-sun cloud-move"></i></div>
					  <h6>12 PM</h6>
					  <p class="mb-0 temp">31°C</p>
					  <small>Partly Cloudy</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-secondary text-white mb-2"><i class="fas fa-cloud cloud-move"></i></div>
					  <h6>1 PM</h6>
					  <p class="mb-0 temp">29°C</p>
					  <small>Cloudy</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-primary text-white mb-2"><i class="fas fa-cloud-rain rain-drop"></i></div>
					  <h6>2 PM</h6>
					  <p class="mb-0 temp">27°C</p>
					  <small>Rain</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
					<div class="hour-card text-center mx-2 p-3">
					  <div class="icon-circle bg-gradient-warning text-white mb-2"><i class="fas fa-bolt"></i></div>
					  <h6>3 PM</h6>
					  <p class="mb-0 temp">26°C</p>
					  <small>Storm</small>
					</div>
				  </div>
				</div>
			  </div>
			</div>
		  </div>
		  
		</div>

<script>
const carousel = document.querySelector('.hourly-carousel');
const leftBtn = document.querySelector('.carousel-btn.left');
const rightBtn = document.querySelector('.carousel-btn.right');
const cardWidth = document.querySelector('.hour-card').offsetWidth + 10;


leftBtn.addEventListener('click', () => { carousel.scrollLeft -= cardWidth; });
rightBtn.addEventListener('click', () => { carousel.scrollLeft += cardWidth; });

let isDown = false;
let startX;
let scrollLeft;

carousel.addEventListener('mousedown', e => {
  isDown = true;
  startX = e.pageX - carousel.offsetLeft;
  scrollLeft = carousel.scrollLeft;
});
carousel.addEventListener('mouseup', () => isDown = false);
carousel.addEventListener('mouseleave', () => isDown = false);
carousel.addEventListener('mousemove', e => {
  if(!isDown) return;
  e.preventDefault();
  const x = e.pageX - carousel.offsetLeft;
  carousel.scrollLeft = scrollLeft - (x - startX);
});

carousel.addEventListener('touchstart', e => {
  startX = e.touches[0].pageX;
  scrollLeft = carousel.scrollLeft;
});
carousel.addEventListener('touchmove', e => {
  const x = e.touches[0].pageX;
  carousel.scrollLeft = scrollLeft - (x - startX);
});
</script>

       
      </div>
    </section>
  </div>
</div>
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/dist/js/custom.js"></script>


</body>
</html>
<?php
	}
?>