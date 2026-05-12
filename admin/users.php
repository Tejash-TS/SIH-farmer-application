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
	<link rel="icon" href="assets/dist/img/logos/favicon.ico" type="image/x-icon">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="assets/plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="assets/plugins/summernote/summernote-bs4.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  
  
  <link rel="stylesheet" href="assets/dist/css/custom.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
  </div>

  <?php include_once('_header.php'); ?>
  <?php include_once('_sidebar.php'); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">users</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="admin">Home</a></li>
              <li class="breadcrumb-item active">users</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
			
			<div class="card">
                <div class="card-header">
				  <h3 class="card-title">Users</h3>
				  <div class="card-tools">
					<a href="Add_chapter" class="btn bg-primary btn-sm" data-toggle="modal" data-target="#add_modal">Add User</a>
				  </div>
				</div>

              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped dataTable dtr-inline" aria-describedby="example1_info">
					<thead>
						<tr>
							<th>Sr.No.</th>
							<th>User Name</th>
							<th>Email</th>
							<th>Role</th>
							<th>Action</th>
						
						</tr>	
					</thead>
					<tbody>
					<?php
						$sr=1;
						$stml=$conn->prepare("CALL s_pr_get_all_users()");
						if($stml->execute())
						{
							$result=$stml->get_result();
							if($result && $result->num_rows>0)
							{
								while($res=$result->fetch_object())
								{
					?>
									<tr>
										<td><?= $sr ?></td>
										<td><?= $res->user_name?></td>
										<td><?= $res->email ?></td>
										<td><?= $res->role?></td>
										<td>
											<button value="<?= ed("en", $res->user_id) ?>" class="view_btn btn btn-sm btn-primary m-1 ">View</button>
											<button value="<?= ed("en", $res->user_id) ?>" class="edit_btn btn btn-sm btn-info m-1 ">Edit</button>
											<button value="<?= ed("en", $res->user_id) ?>" class="delete_btn btn btn-sm btn-danger m-1 ">Delete</button>
										</td>
										
									</tr>
					<?php
									$sr++;
								}
							}
						}
						$stml->close();
						$conn->next_result();
					?>
				  </tbody>
                 
                </table>
              </div>
              <!-- /.card-body -->
            </div>
			
		
       
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
	
	<div class="modal fade" id="add_modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-xl animate__animated animate__zoomIn">
			<div class="modal-content">
				
				<!-- Modal Header -->
				<div class="modal-header bg-light">
					<h5 class="modal-title text-primary">
						<i class="fas fa-user-shield me-2"></i>
						Add User
					</h5>
					<button type="button" class="btn-close" data-dismiss="modal" >X</button>
				</div>

				<!-- Modal Body -->
				<div class="modal-body">
					<form id="add_form">
						<div class="row">
							<div class="form-group col-sm-6">
								<label for="exampleInputEmail1">Email address</label>
								<input type="email" class="form-control" name="user_email" placeholder="Enter email" required>
								<input type="hidden" name="add"  required>
								<small id="email_exist" class="form-text text-danger"></small>
							</div>
							<div class="form-group col-sm-6">
								<label for="exampleInputEmail1">User Name</label>
								<input type="Text" class="form-control" name="user_name" placeholder="Enter User Name" required>
								<small id="name_exist" class="form-text text-danger"></small>
							</div>
							<div class="form-group col-sm-6">
								<label for="exampleInputEmail1">Mobile Number</label>
								<input type="number" class="form-control" name="user_number" placeholder="Enter User Mobile Number" required>
								<small id="mb_no_exist" class="form-text text-danger"></small>
							</div>
							<div class="form-group col-sm-6">
								<label for="exampleInputEmail1">Select User Role</label>
								<select class="form-control form-select" name="user_role"  required>
									<option value="" selected disabled>Select User Role</option>
									<option value="farmer">Farmer</option>
									<option value="PesticideVendor">Pesticide Vendor</option>
								</select>
								<small id="name_exist" class="form-text text-danger"></small>
							</div>
						</div>	
						
						<!-- Actions -->
						<div class="d-flex justify-content-end pt-4">
							<button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Close</button>
							<button type="submit" class="btn btn-primary">Save</button>
						</div>
					</form>
				</div>

			</div>
		</div>
	</div>
	
	
	<div class="modal fade" id="view_modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-xl animate__animated animate__zoomIn">
			<div class="modal-content" id="modal_body_insert">
				
				

			</div>
		</div>
	</div>
	
	
	



 <?php include_once('_footer.php'); ?>

  <!-- Control Sidebar -->
  <?php include_once('_aside_content.php'); ?>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="assets/plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="assets/plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="assets/plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="assets/plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="assets/plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="assets/plugins/moment/moment.min.js"></script>
<script src="assets/plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="assets/plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.js"></script>

<link href="assets/plugins/custom/animate/animate.min.css" rel="stylesheet" type="text/css" />
<link href="assets/plugins/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/sweetalert2/sweetalert2.js"></script>
<link href="assets/plugins/custom/enlarge/jquery.fancybox.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/custom/enlarge/jquery.fancybox.min.js"></script>




<!-- DataTables  & Plugins -->
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="assets/plugins/jszip/jszip.min.js"></script>
<script src="assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<script src="assets/dist/js/custom.js"></script>

<script>
$(function () {
  $("#example1").DataTable({
    responsive: true,
    lengthChange: false,
    autoWidth: false,         // Vertical scroll
    scrollX: true,              // ✅ Horizontal scroll
    scrollCollapse: true,
    paging: false,              // Optional: remove pagination if you want all rows in scroll
    buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"]
  }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
});


$(document).on('submit', '#add_form', async function(event) {
	event.preventDefault();

	
	var formData = new FormData(this);
	$.ajax({
		url: 'admin/users_dao', 
		type: 'POST',
		data: formData,
		contentType: false, 
		processData: false, 
		success: function(response) {
			if (response.status_code == 400) {
				error_alert(response.message);
			} else if (response.status_code == 200) {             
				success_alert(response.message);
				$('#add_form')[0].reset();         
				$("#email_exist").text(""); 
			}else if (response.status_code == 409) {             
				$("#email_exist").text(response.message); 
				$("#add_modal").modal("show");
			}
		},
		error: function(xhr, status, error) {
			error_alert("AJAX Error: " + error);
			$("#add_modal").modal("show");
		}
	});
});

$(document).on('click', '.view_btn', async function(event) {
	event.preventDefault();
	

	var btn = $(this);
	var btn_value = btn.val();
	var formData = new FormData();
	formData.append("view",btn_value);
	$.ajax({
		url: 'admin/users_dao', 
		type: 'POST',
		data: formData,
		contentType: false, 
		processData: false, 
		success: function(response) {
			if (response.status_code == 400) {
				error_alert(response.message);
			} else if (response.status_code == 200) {             
				$("#modal_body_insert").html(response.data)
				$("#view_modal").modal("show");
			}
		},
		error: function(xhr, status, error) {
			error_alert("AJAX Error: " + error); 
		}
	});
	
});

$(document).on('click', '.edit_btn', async function(event) {
	event.preventDefault();
	
	if (await confirmation_alert("Do you really want to Edit this User? ")) 
	{
		var btn = $(this);
		var btn_value = btn.val();
		var formData = new FormData();
		formData.append("edit",btn_value);
		$.ajax({
			url: 'admin/users_dao', 
			type: 'POST',
			data: formData,
			contentType: false, 
			processData: false, 
			success: function(response) {
				if (response.status_code == 400) {
					error_alert(response.message);
				} else if (response.status_code == 200) {             
					$("#modal_body_insert").html(response.data)
					$("#view_modal").modal("show");
				}
			},
			error: function(xhr, status, error) {
				error_alert("AJAX Error: " + error); 
			}
		});
	}
});


$(document).on('submit', '#update_form', async function(event) {
	event.preventDefault();
	
	if (await confirmation_alert("Do you really want to Update this User? ")) 
	{
		var formData = new FormData(this);
		$.ajax({
			url: 'admin/users_dao', 
			type: 'POST',
			data: formData,
			contentType: false, 
			processData: false, 
			success: function(response) {
				if (response.status_code == 400) {
					error_alert(response.message);
					$("#view_modal").modal("show");
				} else if (response.status_code == 200) {             
					success_alert(response.message);
					$("#update_email_exist").text(""); 
					$('#update_form')[0].reset();
				} else if (response.status_code == 409) {             
					$("#update_email_exist").text(response.message); 
					$("#view_modal").modal("show");
				}
			},
			error: function(xhr, status, error) {
				error_alert("AJAX Error: " + error);
			}
		});
	}
});


$(document).on('click', '.delete_btn', async function(event) {
	event.preventDefault();
	
	if (await delete_confirmation("Do you really want to delete this User? This action cannot be undone.")) 
	{
		var btn = $(this);
		var btn_value = btn.val();
		var formData = new FormData();
		formData.append("delete",btn_value);
		$.ajax({
			url: 'admin/users_dao', 
			type: 'POST',
			data: formData,
			contentType: false, 
			processData: false, 
			success: function(response) {
				if (response.status_code == 400) {
					error_alert(response.message);
				} else if (response.status_code == 200) {             
					success_alert(response.message);
				}
			},
			error: function(xhr, status, error) {
				error_alert("AJAX Error: " + error); 
			}
		});
	}
});

<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
<?php
	}
?>