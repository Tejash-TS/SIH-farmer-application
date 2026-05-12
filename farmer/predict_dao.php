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
		if(isset($_POST['Predict_image']))
		{
			$directory_doc = "../Prediction_images/user" . $user_id;
			if (!is_dir($directory_doc)) {
				mkdir($directory_doc, 0755, true);
			} 

			if(!empty($_FILES["image"]["name"]))
			{
				
				$filename1 = basename($_FILES["image"]["name"]); 
				$filetype1 = strtolower(pathinfo($filename1, PATHINFO_EXTENSION));
				$allowtype1 = array('jpg', 'png', 'jpeg', 'gif'); 
				$image1 = $directory_doc ."/image_predict_".datetime_format($cur_datetime, "d_m_y_H_i_s").".".$filetype1;
				$image = $image1;
			   
				if(in_array($filetype1, $allowtype1)) 
				{       
					if(move_uploaded_file($_FILES["image"]["tmp_name"], $image1)) {
						$statusmsg = "File uploaded.";
						
					} else {
						$statusmsg = "Sorry, there was an error uploading your file.";
						$res = [
								'status_code' => 400,
								'message' => $statusmsg
							];
						echo json_encode($res);
						return;
						exit;
					}
				} else {
					$statusmsg = "Sorry, only 'jpg', 'png', 'jpeg', 'gif' files are allowed to upload.";
					$res = [
							'status_code' => 400,
							'message' => $statusmsg
						];
					echo json_encode($res);
					return;
					
				}
				
			}
			
			$ch = curl_init();
			$cfile = new CURLFile($image1, mime_content_type($image1), basename($image1));
			curl_setopt_array($ch, [
				CURLOPT_URL => $api_url."predict",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => ["image" => $cfile]  
			]);

			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);
			
			$data=NULL;
			$disease_name=NULL;
			$description=NULL;
			$causes=NULL;
			$symptoms=NULL;
			$prevention=NULL;
			$one_line_description=NULL;
			$id=NULL;
			
			$borderColors = [
				"border-left-primary", "border-left-secondary", "border-left-success", 
				"border-left-warning", "border-left-info", "border-left-pink",
				"border-left-purple", "border-left-indigo", "border-left-teal", "border-left-cyan",
				"border-left-orange", "border-left-amber", "border-left-lime", "border-left-emerald",
				"border-left-sky", "border-left-violet", "border-left-rose", "border-left-brown",
				"border-left-silver", "border-left-gold"
			];
			
			if ($error) {
				$res = [
					'status_code' => 400,
					'message' => "FastAPI request failed",
					'error' => $error
				];
			} else {
				$result =json_decode($response, true);
				
				$stml=$conn->prepare("CALL s_pr_add_prediction(?, ?, ?)");
				$stml->bind_param("sis", $image, $user_id, $cur_datetime);
				if($stml->execute())
				{
					$stml_result=$stml->get_result();
					if($res=$stml_result->fetch_object())
					{
						$id=$res->id;
					}
				}
				$stml->close();
				$conn->next_result();
				
				if($id)
				{					
					foreach($result['all_diseases'] as $a)
					{
						$stml=$conn->prepare(" CALL s_pr_add_prediction_details(?, ?, ?, ?, ?)");
						$stml->bind_param("issis", $id, $a['disease'], $a['confidence_percent'], $user_id, $cur_datetime);
						$stml->execute();
						$stml->close();
						$conn->next_result();
					
					}
				}		
				$stml=$conn->prepare("CALL s_pr_get_diseases(?)");
				$stml->bind_param("s", $result["predicted_disease"]);
				if($stml->execute())
				{
					$stml_result=$stml->get_result();
					if($res=$stml_result->fetch_object())
					{
						$disease_name=$res->disease_name;
						$description=$res->description;
						$causes=$res->causes;
						$symptoms=$res->symptoms;
						$prevention=$res->prevention;
						$one_line_description=$res->one_line_description;
					}
				}
				$stml->close();
				$conn->next_result();
				$data='
						<div class="prediction-card">
							<h3><i class="fas fa-brain"></i> AI Analysis Results</h3>
							
							<!-- Loading State -->
							<div class="loading" id="loadingState" style="display: none;">
								<div class="spinner"></div>
								<p>Analyzing your grape leaf image...</p>
							</div>

							<!-- Results -->
							<div class="results" id="results" style="display: block;">
								<div class="prediction-result">
									<div class="disease-status diseased" id="diseaseStatus">
										<h2>'.$result["predicted_disease"].'</h2>
										<p>'.$one_line_description.'</p>
										<div class="severity-badge medium">
											Severity: Medium
										</div>
									</div>
									
									<div class="confidence-meter">
										<h4>Confidence Level</h4>
										<div class="meter">
											<div class="meter-fill" id="confidenceBar" style="width: '.$result["confidence_percent"].';"></div>
										</div>
										<span class="confidence-text" id="confidenceText">'.$result["confidence_percent"].'</span>
									</div>

									<div class="all-predictions">
										<h4>All Predictions</h4>
										<div class="prediction-list" id="predictionList">';
										foreach($result['all_diseases'] as $a)
										{
											$data.='
													<div class="prediction-item downey-mildew '.(($result["confidence_percent"]==$a["confidence_percent"])? "top": $borderColors[array_rand($borderColors)]).' ">
														<span class="prediction-name">'.$a["disease"].'</span>
														<span class="prediction-confidence">'.$a["confidence_percent"].'</span>
													</div>
											';
										}
											
			$data.='					</div>
									</div>
								</div>
							</div>
						</div>
						';
				
				$res = [
					'status_code' => 200,
					'message' => $statusmsg,
					'data'=>$data
				];
			}

			echo json_encode($res);
			exit;
			
		}
		
		
		
	
	}
		
?>