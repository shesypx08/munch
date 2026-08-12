<?php

$servername = "localhost";
$username = "root";
$password = "";
$db = "munchdb";

$conn = mysqli_connect($servername, $username, $password, $db);

if(!$conn)
{
    die("Service is not available right now. Please try again later.");
}

?>
