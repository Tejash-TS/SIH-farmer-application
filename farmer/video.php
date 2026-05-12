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
  .ratio {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* 16:9 ratio */
  }
  .ratio video {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 15px 15px 0 0;
    object-fit: cover;
  }
  .card {
    border-radius: 15px !important;
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
            <h1 class="m-0">Video</h1>
          </div>
		</div>
      </div>
    </div>
<section class="content">
  <div class="container-fluid">
    <div class="row">
		<section class="content col-12">
		  <div class="container-fluid">
			<div class="row justify-content-center">
			  <div class="col-lg-10 col-md-10">

				<div class="card shadow-sm border-0 rounded-3">
				  <div class="ratio ratio-16x9">
					<?php 
						$id=ed("de", $_GET['video_tutorial_id']);
						$stml=$conn->prepare("CALL s_pr_get_video_tutorial(?);");
						$stml->bind_param("i", $id);
						if($stml->execute())
						{
							$result=$stml->get_result();
							$res=$result->fetch_object();
					?>	
							<video class="rounded-top w-100 h-100" controls>
							  <source src="<?= str_replace("../","./", $res->video)?>" type="video/mp4">
							  Your browser does not support the video tag.
							</video>
						  </div>
						  <div class="card-body">
							<h5 class="fw-bold mb-2"><?=$res->title?></h5>
							<hr>
							<p>
							  <?=$res->description?>
							</p>
						  </div>		
				  
					<?php
						}
						$stml->close();
						$conn->next_result();
						
					?>	
				</div>

			  </div>
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