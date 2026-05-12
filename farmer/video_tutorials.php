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
  <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/dist/css/custom.css">
	
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<style>
  .video-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 15px !important;
  }
  .video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  }
  .card-img-top {
    border-radius: 15px 15px 0 0;
    object-fit: cover;
    width: 100%;
    height: 200px;
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
            <h1 class="m-0">Video Tutorials</h1>
          </div>
		</div>
      </div>
    </div>
	<section class="content">
	  <div class="container-fluid">
		<div class="row">
			<?php 
				$stml=$conn->prepare("CALL s_pr_get_all_video_tutorial();");
				if($stml->execute())
				{
					$result=$stml->get_result();
					while($res=$result->fetch_object())
					{
			?>		
					  <div class="col-md-4 mb-4">
						<div class="card shadow-sm border-0 rounded-3 overflow-hidden video-card">
						  <div class="position-relative">
							<a href="farmer/video?video_tutorial_id=<?= ed("en",$res->video_tutorial_id);?>">
							  <img src="<?=str_replace("../", "./",$res->thumbnail)?>" 
								   class="card-img-top rounded-top" alt="Video Thumbnail">
							  <span class="badge bg-dark position-absolute rounded-pill px-2 py-1"
									style="bottom:10px; right:10px; font-size:12px;"></span>
							</a>
						  </div>
						  <div class="card-body row">
							<h6 class="card-title fw-bold text-truncate mb-1 col-12">
							  <?=$res->title?>
							</h6>
							<p class="text-muted small mb-0 col-12"><?= echoWords($res->description,10) ?>...</p>
							<p class="text-muted small col-12"> • 1 hour ago</p>
						  </div>
						</div>
					  </div>
			<?php
					}
				}
				$stml->close();
				$conn->next_result();
				
			?>		


		</div>
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
<link href="assets/plugins/custom/animate/animate.min.css" rel="stylesheet" type="text/css" />
<link href="assets/plugins/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/sweetalert2/sweetalert2.js"></script>
<link href="assets/plugins/custom/enlarge/jquery.fancybox.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/custom/enlarge/jquery.fancybox.min.js"></script>
<script src="assets/dist/js/custom.js"></script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
<?php
	}
?>