<?php
	session_start();
	include_once("../_functions.php");
	if(!isset($_SESSION['user']))
	{
		header("location:../login");
		exit();
	}
	else{
		check_role($_SESSION['user']['role'],basename(__DIR__));
		header('Content-Type: application/json');
		$data="";
		
		if(isset($_POST['add']))
		{
			if($_POST['user_email']==NULL || $_POST['user_name']==NULL || $_POST['user_number']==NULL || $_POST['user_role']==NULL )
			{
				$res = [
					'status_code' => 400,
					'message' => 'All Fields Are Mandatory.'
				];
				echo json_encode($res);
				return;
			}
			$user_email=$_POST['user_email'];
			$user_name=$_POST['user_name'];
			$user_number=$_POST['user_number'];
			$user_role=$_POST['user_role'];
			$password=md5($user_number);
			
			$stml=$conn->prepare("CALL s_pr_add_user(?, ?, ?, ?, ?, ?, ?, @status_code, @msg)");
			$stml->bind_param("sssssis", $user_email, $user_name, $password, $user_number, $user_role, $user_id, $cur_datetime);
			if($stml->execute())
			{
				$result=$conn->query("select @status_code as status_code, @msg as msg");
				
				 if ($result) {
					$row = $result->fetch_assoc();
					$status_code = (int)$row['status_code'];
					$msg = $row['msg'];

					switch ($status_code) {
						case 200:
							$res = [
								'status_code' => 200,
								'message' => $msg
							];
							break;
						case 409:
							$res = [
								'status_code' => 409,
								'message' => $msg
							];
							break;
						default:
							$res = [
								'status_code' => 400,
								'message' => 'Unknown error occurred.'
							];
							break;
					}
				} else {
					$res = [
						'status_code' => 400,
						'message' => 'Failed to retrieve output parameter.'
					];
				}
			}
			$stml->close();
			$conn->next_result();
			
			echo json_encode($res);
			return;
			
		}
		else if(isset($_POST['delete']))
		{
			$id=ed("de", $_POST['delete']);
			
			$stml=$conn->prepare("Update users set is_active='N', modified_by =?, modified_on=? where user_id=?");
			$stml->bind_param("isi", $user_id, $cur_datetime, $id);
			if($stml->execute())
			{
				$res = [
					'status_code' => 200,
					'message' => 'User Deleted Succssfuly.'
				];
			}
			$stml->close();
			$conn->next_result();
			
			echo json_encode($res);
			return;
		}
		else if(isset($_POST['view']))
		{
			$id=ed("de",$_POST['view']);
			
			$stml=$conn->prepare("CALL s_pr_get_user(?)");
			$stml->bind_param("i", $id);
			if($stml->execute())
			{
				$result =$stml->get_result();
				if($result && $result->num_rows>0)
				{
					if($res=$result->fetch_object())
					{
						$data='
								<!-- Modal Header -->
								<div class="modal-header bg-light">
									<h5 class="modal-title text-primary">
										<i class="fas fa-user-shield me-2"></i>
										View User
									</h5>
									<button type="button" class="btn-close" data-dismiss="modal" >X</button>
								</div>

								<!-- Modal Body -->
								<div class="modal-body">
									<table class="table table-bordered table-striped dataTable dtr-inline">
										<tbody>
											<tr>
												<th>User Name</th>
												<td>'.$res->user_name.'</td>
											</tr>
											<tr>
												<th>User Email</th>
												<td>'.$res->email.'</td>
											</tr>
											<tr>
												<th>User Role</th>
												<td>'.$res->role.'</td>
											</tr>
											<tr>
												<th>User Mobile Number</th>
												<td>'.$res->mb_number.'</td>
											</tr>
											<tr>
												<th>Image</th>
												<td><img src="'.$res->image.'" width="100%" ></td>
											</tr>
										</tbody>
									</table>
										
									<!-- Actions -->
									<div class="d-flex justify-content-end pt-4">
										<button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Close</button>
									</div>
								</div>
								';
					}
					
					$res = [
						'status_code' => 200,
						'message' => 'Data Fetch Successfuly.',
						'data'=>$data
					];
					
				}
				else
				{
					$res = [
						'status_code' => 400,
						'message' => 'Invalid User Id.'
					];
				}
			}
			$stml->close();
			$conn->next_result();
			echo json_encode($res);
			return;
			
		}
		else if(isset($_POST['edit']))
		{
			$id=ed("de",$_POST['edit']);
			
			$stml=$conn->prepare("CALL s_pr_get_user(?)");
			$stml->bind_param("i", $id);
			if($stml->execute())
			{
				$result =$stml->get_result();
				if($result && $result->num_rows>0)
				{
					if($res=$result->fetch_object())
					{
						$data='
								<!-- Modal Header -->
								<div class="modal-header bg-light">
									<h5 class="modal-title text-primary">
										<i class="fas fa-user-shield me-2"></i>
										Edit User
									</h5>
									<button type="button" class="btn-close" data-dismiss="modal" >X</button>
								</div>

								<!-- Modal Body -->
								<div class="modal-body">
									<form id="update_form">
										<div class="row">
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">Email address</label>
												<input type="email" class="form-control" name="user_email" value="'.$res->email.'" placeholder="Enter email" required>
												<input type="hidden" name="update"  required>
												<input type="hidden" name="user_id" value="'.ed("en", $res->user_id).'"  required>
												<small id="update_email_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">User Name</label>
												<input type="Text" class="form-control" name="user_name" value="'.$res->user_name.'" placeholder="Enter User Name" required>
												<small id="name_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">Mobile Number</label>
												<input type="number" class="form-control" name="user_number" value="'.$res->mb_number.'" placeholder="Enter User Mobile Number" required>
												<small id="mb_no_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">Select User Role</label>
												<select class="form-control form-select" name="user_role"  required>
													<option '.(($res->role=="farmer")?"selected":"").' value="farmer">Farmer</option>
													<option '.(($res->role=="PesticideVendor")?"selected":"").' value="PesticideVendor">Pesticide Vendor</option>
												</select>
												<small id="name_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">Password</label>
												<input type="password" class="form-control" name="password"  placeholder="password">
												<small  class="form-text text-muted">Leave blank if you don’t want to change the password.</small>
											</div>
										</div>	
										
										<!-- Actions -->
										<div class="d-flex justify-content-end pt-4">
											<button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Close</button>
											<button type="submit" class="btn btn-primary">Update</button>
										</div>
									</form>
								</div>
								';
					}
					
					$res = [
						'status_code' => 200,
						'message' => 'Data Fetch Successfuly.',
						'data'=>$data
					];
					
				}
				else
				{
					$res = [
						'status_code' => 400,
						'message' => 'Invalid User Id.'
					];
				}
			}
			$stml->close();
			$conn->next_result();
			echo json_encode($res);
			return;
			
		}
		else if(isset($_POST['update']))
		{
			if($_POST['user_email']==NULL || $_POST['user_name']==NULL || $_POST['user_number']==NULL || $_POST['user_role']==NULL || $_POST['user_id']==NULL )
			{
				$res = [
					'status_code' => 400,
					'message' => 'All Fields Are Mandatory.'
				];
				echo json_encode($res);
				return;
			}
			$id=ed("de", $_POST['user_id']);
			$user_email=$_POST['user_email'];
			$user_name=$_POST['user_name'];
			$user_number=$_POST['user_number'];
			$user_role=$_POST['user_role'];
			$password=((isset($_POST['password']) && !empty($_POST['password']) && $_POST['password'] != NULL)? md5($_POST['password']) :NULL);
			
			$stml=$conn->prepare("CALL s_pr_update_user(?, ?, ?, ?, ?, ?, ?, ?, @status_code, @msg)");
			$stml->bind_param("isssssis", $id, $user_email, $user_name, $password, $user_number, $user_role, $user_id, $cur_datetime);
			if($stml->execute())
			{
				$result=$conn->query("select @status_code as status_code, @msg as msg");
				
				 if ($result) {
					$row = $result->fetch_assoc();
					$status_code = (int)$row['status_code'];
					$msg = $row['msg'];

					switch ($status_code) {
						case 200:
							$res = [
								'status_code' => 200,
								'message' => $msg
							];
							break;
						case 409:
							$res = [
								'status_code' => 409,
								'message' => $msg
							];
							break;
						default:
							$res = [
								'status_code' => 400,
								'message' => 'Unknown error occurred.'
							];
							break;
					}
				} else {
					$res = [
						'status_code' => 400,
						'message' => 'Failed to retrieve output parameter.'
					];
				}
			}
			$stml->close();
			$conn->next_result();
			
			echo json_encode($res);
			return;
			
		}
		else
		{
			$res = [
				'status_code' => 400,
				'message' => 'Invalid Parameter.'
			];
			echo json_encode($res);
			return;
		}
		
	}

?>  