<?php

session_start();

include 'dbconnect.php';

if(isset($_POST['DeleteCustAcc']))
{
	$CUser = $_POST['username'];
	$query = "DELETE FROM CUSTOMER WHERE CUSTUSERNAME = '$CUser'";
	$result = mysqli_query($conn,$query);
	
	if($result)
	{
		echo "Account deleted successfully.";
		session_destroy();
		
		header("Location:Dashboard.html");//tukar ke mainpage
		exit();
	}
	else
	{
		echo "There is an unknown error occured.";
	}
}
?>