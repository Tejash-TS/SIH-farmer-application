<?php
	session_start();
	include_once("../_functions.php");
	
	if(!isset($_SESSION['user']))
	{
		Header("location:../login");
		exit();
	}
	else
	{
		header('Content-Type: application/json');
		
		if(isset($_POST['add']))
		{
			$disease_name=$_POST['disease_name'];
			$one_line_description=$_POST['one_line_description'];
			$description=$_POST['description'];
			$causes=$_POST['causes'];
			$symptoms=$_POST['symptoms'];
			$prevention=$_POST['prevention'];
			
			if($disease_name==NULL || $one_line_description==NULL || $description==NULL || $causes==NULL || $symptoms==NULL || $prevention==NUll)
			{
				$res = [
					'status_code' => 400,
					'message' => 'All fields Mandatory'
				];
				echo json_encode($res);
				return;
			}
			
			$stml=$conn->prepare("CALL s_pr_add_disease(?, ?, ?, ?, ?, ?, ?, ?)");
			$stml->bind_param("ssssssis", $disease_name, $one_line_description, $description, $causes, $symptoms, $prevention, $user_id, $cur_datetime);
			if($stml->execute())
			{
				$res = [
					'status_code' => 200,
					'message' => 'Disease Added Successfuly.'
				];
			}
			$stml->close();
			$conn->next_result();
			echo json_encode($res);
			return;
		}
		else if(isset($_POST['delete']))
		{
			$id=ed("de", $_POST['delete']);
			if($id==NULL)
			{
				$res = [
					'status_code' => 400,
					'message' => 'ID Required'
				];
				echo json_encode($res);
				return;
			}
			$stml=$conn->prepare("UPDATE `diseases` SET is_active='N', `modified_by`=?, `modified_on`=? WHERE `diseases_id`=?; ");
			$stml->bind_param("isi", $user_id, $cur_datetime, $id);
			if($stml->execute())
			{
				$res = [
					'status_code' => 200,
					'message' => 'Disease Deleted Successfuly.'
				];
			}
			$stml->close();
			$conn->next_result();
			echo json_encode($res);
			return;
		}
		else if(isset($_POST['view']))
		{
			$data=NULL;
			
			$id=ed("de", $_POST['view']);
			if($id==NULL)
			{
				$res = [
					'status_code' => 400,
					'message' => 'ID Required'
				];
				echo json_encode($res);
				return;
			}
			$stml=$conn->prepare("CALL s_pr_get_disease(?); ");
			$stml->bind_param("i", $id);
			if($stml->execute())
			{
				$result=$stml->get_result();
				if($result && $result->num_rows==1)
				{
					if($res=$result->fetch_object())
					{
						$data='
								<!-- Modal Header -->
								<div class="modal-header bg-light">
									<h5 class="modal-title text-primary">
										<i class="fas fa-user-shield me-2"></i>
										View Disease
									</h5>
									<button type="button" class="btn-close" data-dismiss="modal" >X</button>
								</div>

								<!-- Modal Body -->
								<div class="modal-body">
									<table class="table table-bordered table-striped dataTable dtr-inline">
										<tbody>
											<tr>
												<th>disease Name</th>
												<td>'.$res->disease_name.'</td>
											</tr>
											<tr>
												<th>one line description</th>
												<td>'.$res->one_line_description.'</td>
											</tr>
											<tr>
												<th>description</th>
												<td>'.$res->description.'</td>
											</tr>
											<tr>
												<th>causes</th>
												<td>'.$res->causes.'</td>
											</tr>
											<tr>
												<th>symptoms</th>
												<td>'.$res->symptoms.'</td>
											</tr>
											<tr>
												<th>prevention</th>
												<td>'.$res->prevention.'</td>
											</tr>
											
										</tbody>
									</table>
										
									<!-- Actions -->
									<div class="d-flex justify-content-end pt-4">
										<button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Close</button>
									</div>
								</div>
								';
						$res = [
							'status_code' => 200,
							'message' => 'Disease Deleted Successfuly.',
							'data'=>$data
						];
					}
					else
					{
						$res = [
							'status_code' => 400,
							'message' => 'Unable to Retrive Result.'
						];
					}
				}
				else
				{
					$res = [
							'status_code' => 400,
							'message' => 'Unable to Get Result .'
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
			$data=NULL;
			
			$id=ed("de", $_POST['edit']);
			if($id==NULL)
			{
				$res = [
					'status_code' => 400,
					'message' => 'ID Required'
				];
				echo json_encode($res);
				return;
			}
			$stml=$conn->prepare("CALL s_pr_get_disease(?); ");
			$stml->bind_param("i", $id);
			if($stml->execute())
			{
				$result=$stml->get_result();
				if($result && $result->num_rows==1)
				{
					if($res=$result->fetch_object())
					{
						$data='
								<!-- Modal Header -->
								<div class="modal-header bg-light">
									<h5 class="modal-title text-primary">
										<i class="fas fa-user-shield me-2"></i>
										Edit disease
									</h5>
									<button type="button" class="btn-close" data-dismiss="modal" >X</button>
								</div>

								<!-- Modal Body -->
								<div class="modal-body">
									<form id="update_form">
										<div class="row">
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">disease name</label>
												<input type="text" class="form-control" name="disease_name" value="'.$res->disease_name.'" placeholder="Enter Disease Name" required>
												<input type="hidden" name="update"  required>
												<input type="hidden" name="id" value="'.ed("en", $res->diseases_id).'" required>
												<small id="add_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">one line description</label>
												<input type="Text" class="form-control" name="one_line_description" value="'.$res->one_line_description.'" placeholder="Enter one line description" required>
												<small id="name_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">description</label>
												<textarea class="form-control" name="description" placeholder="Enter description" required>'.$res->description.'</textarea>
												<small id="mb_no_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">causes</label>
												<textarea class="form-control" name="causes" placeholder="Enter causes" required>'.$res->causes.'</textarea>
												<small id="mb_no_exist" class="form-text text-danger"></small>
											</div>
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">symptoms</label>
												<textarea class="form-control" name="symptoms" placeholder="Enter symptoms" required>'.$res->symptoms.'</textarea>
												<small id="mb_no_exist" class="form-text text-danger"></small>
											</div>
											
											<div class="form-group col-sm-6">
												<label for="exampleInputEmail1">prevention</label>
												<textarea class="form-control" name="prevention" placeholder="Enter prevention" required>'.$res->prevention.'</textarea>
												<small id="mb_no_exist" class="form-text text-danger"></small>
											</div>
											
										</div>	
										
										<!-- Actions -->
										<div class="d-flex justify-content-end pt-4">
											<button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Close</button>
											<button type="submit" class="btn btn-primary">Save</button>
										</div>
									</form>
								</div>
								';
						$res = [
							'status_code' => 200,
							'message' => 'Disease Deleted Successfuly.',
							'data'=>$data
						];
					}
					else
					{
						$res = [
							'status_code' => 400,
							'message' => 'Unable to Retrive Result.'
						];
					}
				}
				else
				{
					$res = [
							'status_code' => 400,
							'message' => 'Unable to Get Result .'
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
			$id=ed("de" ,$_POST['id']);
			$disease_name=$_POST['disease_name'];
			$one_line_description=$_POST['one_line_description'];
			$description=$_POST['description'];
			$causes=$_POST['causes'];
			$symptoms=$_POST['symptoms'];
			$prevention=$_POST['prevention'];
			
			if($disease_name==NULL || $one_line_description==NULL || $description==NULL || $causes==NULL || $symptoms==NULL || $prevention==NUll || $id==NULL)
			{
				$res = [
					'status_code' => 400,
					'message' => 'All fields Mandatory'
				];
				echo json_encode($res);
				return;
			}
			
			$stml=$conn->prepare("CALL s_pr_update_disease(?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stml->bind_param("issssssis", $id, $disease_name, $one_line_description, $description, $causes, $symptoms, $prevention, $user_id, $cur_datetime);
			if($stml->execute())
			{
				$res = [
					'status_code' => 200,
					'message' => 'Disease Updated Successfuly.'
				];
			}
			$stml->close();
			$conn->next_result();
			echo json_encode($res);
			return;
		}
		
		
		
	
	}
		
?>