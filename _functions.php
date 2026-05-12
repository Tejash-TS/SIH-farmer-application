<?php
	
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}  
	
	$host = "mysql";
	$user = "root";
	$password = "root";
	$dbname = "sih";

	$conn = new mysqli($host, $user, $password, $dbname);

	$url="http://".$_SERVER['HTTP_HOST']."/";
	$api_url="http://aiprediction:8001/";

	if (!$conn) {
		die("Connection failed: " . mysqli_connect_error());
	}

	$cur_datetime = DATE('Y-m-d H:i:s');
	if(!empty($_SESSION['user']))
	{
		$user_id=$_SESSION['user']['user_id'];
	}
	
	
	function datetime_format($date,$format)
	{
		$dateTime = new DateTime($date);
    	return $dateTime->format($format);
	}
	
	function check_role($role,$cur_dir)
	{
		global $url;
		
		switch($role)
		{
			case "admin" :
							if($cur_dir!="admin")
							{
								header('Location: '.$url.'admin/dashboard');
							}
							break;
			
			case "farmer" :
							if($cur_dir!="farmer")
							{
								header('Location: '.$url.'farmer/dashboard');
							}
							break;
			case "vendor" :
			case "PesticideVendor" :
							if($cur_dir!="vendor")
							{
								header('Location: '.$url.'vendor/dashboard');
							}
							break;
			case "consultant" :
							if($cur_dir!="consultant")
							{
								header('Location: '.$url.'consultant/dashboard');
							}
							break;
			
			case "buyer" :
			case "customer" :
							if($cur_dir!="buyer")
							{
								header('Location: '.$url.'buyer/dashboard');
							}
							break;
							
			default:header("location:".$url."login");					
							
		}
	}
	
	function ed($action, $string) {
		$secret_key = 'Pr@n@v#En&cr1pt*';
		$secret_iv  = 'GE@T&De*Cript_0';
		$encrypt_method = 'AES-256-CBC';

		$key = hash('sha256', $secret_key);
		$iv  = substr(hash('sha256', $secret_iv), 0, 16);

		if ($action === "en") {
			return base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
		} elseif ($action === "de") {
			return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		} else {
			return false; 
		}
	}
	
	function echoWords($text, $no) {
		$words = explode(' ', $text);
		$first_words = array_slice($words, 0, $no);
		$result = implode(' ', $first_words);
		return $result;
	}
?>
