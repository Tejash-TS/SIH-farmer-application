<?php
	session_start();
	include_once("./_functions.php");
	
	$error_msg="";
	if(isset($_POST['add_user']))
	{
		$email=$_POST['email'];
		$user_role=$_POST['user_role'];
		$user_name=$_POST['name'];
		$password=md5($_POST['password']);
		
		$stml=$conn->prepare("CALL s_pr_signup(?, ?, ?, ?, ?, @status_code, @msg)");
		$stml->bind_param("sssss", $user_name, $email, $password, $user_role, $cur_datetime);
		if($stml->execute())
		{
			$result=$conn->query("select @status_code as status_code, @msg as msg");
				
				 if ($result) {
					$row = $result->fetch_assoc();
					$status_code = (int)$row['status_code'];
					$msg = $row['msg'];

					switch ($status_code) {
						case 200:
									$conn->next_result();
									$stmt=$conn->prepare("CALL s_pr_login(?, ?)");
									$stmt->bind_param("ss", $email, $password);
									if($stmt->execute())
									{
										$result_set=$stmt->get_result();
										if($result_set->num_rows==1)
										{
											if ($res = $result_set->fetch_object()) 
											{
												$_SESSION['user'] = [
													'user_id' => $res->user_id,
													'name'    => $res->user_name,
													'image'    => $res->image,
													'email'   => $res->email,
													'role'    => $res->role
												];
												
												// For consultants, redirect to profile completion
												if ($res->role === 'consultant') {
													header('location:consultant/profile');
													exit;
												}
												
												check_role($_SESSION['user']['role'],basename(__DIR__));
											}
											
										}
										else
										{
											$error_msg="Incorrect Email Or Password";
										}
									}
									
						case 409:
									$error_msg= $msg;
									break;
						default:
									$error_msg= 'Unknown error occurred.';
									break;
					}
				} else {
					$error_msg='Failed to Resister User. Please Try Again Later.';
				}
			
		}
		else
		{
			$error_msg="Unable To Resister Right Now. Plese Try Again Later";
		}
	}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>
	<link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="card card-outline card-primary">
    
    <div class="card-body">
      <p class="login-box-msg">Signup in to start your session</p>
		<?php
			echo "<p class='text-danger'>".$error_msg."</p>";
		?>
      <form action="register" method="post">
        <div class="input-group mb-3">
          <input type="Text" name="name" <?=((isset($user_name))?'value='.$user_name:"")?> class="form-control" placeholder="Enter Name" required>
          <input type="hidden" name="add_user" required>
          <div class="input-group-append">
            
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="email" name="email" <?=((isset($email))?'value='.$user_name:"")?> class="form-control" placeholder="Email" required>
          <div class="input-group-append">
            
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append">
            
          </div>
        </div>
        <div class="input-group mb-3">
			<select class="form-control form-select" name="user_role"  required>
				<option value="" selected disabled>Select User Role</option>
				<option <?= ((isset($user_role) && $user_role=="farmer")?"selected":"")?> value="farmer">Farmer</option>
				<option <?= ((isset($user_role) &&$user_role=="vendor")?"selected":"")?> value="vendor">Pesticide Vendor</option>
				<option <?= ((isset($user_role) &&$user_role=="consultant")?"selected":"")?> value="consultant">Consultant</option>
				<option <?= ((isset($user_role) &&$user_role=="buyer")?"selected":"")?> value="buyer">Buyer</option>
			</select>
          <div class="input-group-append">
            
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <p class="mb-1">
				<a href="forgot-password.html">I forgot my password</a>
			</p>
          </div>
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div><script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.min.js"></script>
</body>
</html>
