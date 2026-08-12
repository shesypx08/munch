<?php

session_start();
include 'dbconnect.php';

if (!isset($_SESSION['CUSTUSERNAME'])) {
    exit();
}

$username = $_SESSION['CUSTUSERNAME'];

$name = $_POST['name'];
$phone = $_POST['phone'];

$sql = "UPDATE customer
        SET
        CUSTNAME='$name',
        PHONENO='$phone'
        WHERE CUSTUSERNAME='$username'";

if(mysqli_query($conn,$sql))
{
    echo "success";
}
else
{
    echo "failed";
}
?>