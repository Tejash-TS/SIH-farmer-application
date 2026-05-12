<?php
session_start();
include_once './_functions.php';


if (!empty($_SESSION['user']))
{   
    session_destroy();
    header('location:./login'); 
}
else 
{
	header("Location:./login");
}

?>