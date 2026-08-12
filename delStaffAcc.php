<?php

session_start();
include 'dbconnect.php';

if (isset($_POST['DeleteStaffAcc'])) {

    $SID = $_POST['STAFFID'];

    $query = "DELETE FROM STAFF WHERE STAFFID = '$SID'";
    $result = mysqli_query($conn, $query);

    if ($result) {

        echo "Account deleted successfully";

        session_destroy();

        header("Location: stafflogin.html");
        exit();

    } else {
        echo "Failed to delete account";
    }
}
?>