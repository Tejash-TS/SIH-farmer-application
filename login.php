<?php
	session_start();
	include_once("./_functions.php");
	
	$error_msg="";
	if(isset($_POST['email']) && isset($_POST['password']))
	{
		$email=$_POST['email'];
		$password=md5($_POST['password']);
		
		$stml=$conn->prepare("CALL s_pr_login(?, ?)");
		$stml->bind_param("ss", $email, $password);
		if($stml->execute())
		{
			$result=$stml->get_result();
			if($result->num_rows==1)
			{
				if ($res = $result->fetch_object()) 
				{
					$_SESSION['user'] = [
						'user_id' => $res->user_id,
						'name'    => $res->user_name,
						'image'    => $res->image,
						'email'   => $res->email,
						'role'    => $res->role
					];
					
					check_role($_SESSION['user']['role'],basename(__DIR__));
				}
				
			}
			else
			{
				$error_msg="Incorrect Email Or Password";
			}
		}
		else
		{
			$error_msg="Unable To Login Right Now. Plese Try Again Later";
		}
	}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@title</title>
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="card card-outline card-primary">
    
    <div class="card-body">
      <p class="login-box-msg">Sign in to start your session</p>
		<?php
			echo "<p class='text-danger'>".$error_msg."</p>";
		?>
      <form action="login.php" method="post">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <p class="mb-1">
				<a href="forgot-password.html">I forgot my password</a>
			</p>
          </div>
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
         </div>
      </form>

      
      <p class="mb-0">
        <a href="register.php" class="text-center">Register a new membership</a>
      </p>
    </div>
   
  </div>
  
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.min.js"></script>
</body>
</html>
