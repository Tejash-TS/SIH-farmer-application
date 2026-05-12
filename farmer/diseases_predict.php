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
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/dist/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  
	<style>
	  .upload-zone:hover,
	  .upload-zone.drag-over {
		border-color: #4CAF50 !important;
		background-color: #f0f8f0 !important;
	  }
	  #cancel-preview:hover {
		  background: #b02a37 !important; /* darker red on hover */
		  transform: scale(1.15);
		}

	</style>
	
  <link rel="stylesheet" href="assets/dist/css/custom.css">
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
            <h1 class="m-0"> Disease Detection</h1>
          </div>
          
        </div>
      </div>
    </div>
	<section class="content">
      <div class="container-fluid">		
			<div class="card mx-auto shadow  "  style="border-radius: 20px; background-color: #fff; border: none;">
			  <div class="card-body text-center">
				  <h3 class="mb-3" style="color: #2e7d32;">
					<i class="fas fa-leaf"></i>  Disease Detection
				  </h3>

				  <p class="text-muted mb-4">AI-powered plant health monitoring for grapevines</p>

				 
				<div id="drop-zone" class="upload-zone text-center p-5 position-relative"
					 style="cursor: pointer; border: 2px dashed #ccc; border-radius: 15px; background-color: #fff; transition: all 0.3s ease;">

				  <div id="upload-icon" class="mb-3">
					<i class="fas fa-cloud-upload-alt fa-3x" style="color: #28a745;"></i>
				  </div>

				  <h5 id="upload-title" class="font-weight-bold">Upload  Leaf Image</h5>

				  <p id="upload-subtitle" class="text-muted">Drag & drop your image here or click to browse</p>

				  <div id="upload-button" class="btn btn-success px-4 py-2 rounded-pill shadow-sm">
					Choose Image
				  </div>

				  <div id="preview-container" class="position-relative d-none" style="display: inline-block;">
					<img id="preview-image" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 10px;"/>
					<button id="cancel-preview" type="button"
							style="position: absolute; top: 5px; right: 5px; 
								   background: #dc3545; /* red */
								   border: none; 
								   color: #fff; 
								   border-radius: 50%; 
								   width: 30px; 
								   height: 30px; 
								   font-size: 20px; 
								   line-height: 1; 
								   cursor: pointer; 
								   transition: all 0.2s ease;">
					  &times;
					</button>
				  </div>

				  <input type="file" id="uploadInput" name="image_predict" accept="image/*" hidden>
				</div>
			  </div>
			</div>
			
			
			
      </div>
    </section>
	
	<section class="content mt-4 d-none" id="predictionSection" >
		<div class="container-fluid" id="predicted_info">			
			
		</div>		
	</section>
	<br>
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

<script>
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('uploadInput');

  const uploadIcon = document.getElementById('upload-icon');
  const uploadTitle = document.getElementById('upload-title');
  const uploadSubtitle = document.getElementById('upload-subtitle');
  const uploadButton = document.getElementById('upload-button');

  const previewContainer = document.getElementById('preview-container');
  const previewImage = document.getElementById('preview-image');
  const cancelPreview = document.getElementById('cancel-preview');

  dropZone.addEventListener('click', (e) => {
	if(e.target === cancelPreview) return; 
	fileInput.click();
  });

 
  fileInput.addEventListener('change', () => {
	if(fileInput.files.length) {
	  handleFiles(fileInput.files);
	}
  });

  function handleFiles(files) {
	const file = files[0];
	if(!file.type.startsWith('image/')) {
	  alert('Please select an image file');
	  resetPreview();
	  return;
	}

	const reader = new FileReader();
	reader.onload = function(e) {
	  previewImage.src = e.target.result;
	  previewContainer.classList.remove('d-none');

	  uploadIcon.style.display = 'none';
	  uploadTitle.style.display = 'none';
	  uploadSubtitle.style.display = 'none';
	  uploadButton.style.display = 'none';
	};
	reader.readAsDataURL(file);
  }

  cancelPreview.addEventListener('click', (e) => {
	e.stopPropagation();
	resetPreview();
	$("#predicted_info").html("");
	$("#predictionSection").addClass("d-none");
  });

  function resetPreview() {
	fileInput.value = ''; 
	previewImage.src = '';
	previewContainer.classList.add('d-none');

	uploadIcon.style.display = 'block';
	uploadTitle.style.display = 'block';
	uploadSubtitle.style.display = 'block';
	uploadButton.style.display = 'inline-block';
  }
  
  
  
  $(document).ready(function() {
    $('#uploadInput').on('change', function() {
        var file = this.files[0];
        
        var allowedExtensions = ['image/png', 'image/gif', 'image/jpeg', 'image/jpg'];
        var fileExtension = file.type;

        if (!allowedExtensions.includes(fileExtension)) {
            alert('Invalid file type. Please select a .png, .gif, .jpg, or .jpeg image.');
            return;
        }

        var formData = new FormData();
        formData.append('image', file);
        formData.append('Predict_image', true);

        $.ajax({
            url: '/farmer/predict_dao', 
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
               if(response.status_code==200)
			   {
				   $("#predicted_info").html(response.data);
				   $("#predictionSection").removeClass("d-none");
			   }
			   else if(response.status_code==400)
			   {
					  
					$("#predicted_info").html("");
					$("#predictionSection").addClass("d-none");
			   }
				
            },
            error: function(xhr, status, error) {
                
				$("#predictionSection").addClass("d-none");
                console.log('Error uploading image');
                console.log(error);
            }
        });
    });
});

</script>
</body>
</html>
<?php
	}
?>