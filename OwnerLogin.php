<?php
session_start();
include "dbconnect.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: owner-login.html");
    exit();
}

$staffID = isset($_POST["STAFFID"]) ? strtoupper(trim($_POST["STAFFID"])) : "";
$password = isset($_POST["STAFFPASS"]) ? $_POST["STAFFPASS"] : "";
$redirect = isset($_POST["redirect"]) && trim($_POST["redirect"]) !== "" ? trim($_POST["redirect"]) : "owner-dashboard.html";

if ($staffID === "" || $password === "") {
    echo "<script>alert('Please enter owner staff ID and password.'); window.location.href='owner-login.html';</script>";
    exit();
}

$sql = "SELECT 
            s.STAFFID,
            s.STAFFNAME,
            s.STAFFPHONENO,
            s.STAFFPASS,
            s.STAFFROLE,
            o.EQUITYTYPE,
            o.CONTRACTDURATION
        FROM staff s
        INNER JOIN owner o ON s.STAFFID = o.STAFFID
        WHERE s.STAFFID = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo "<script>alert('Owner login is not available right now. Please try again later.'); window.location.href='owner-login.html';</script>";
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $staffID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "<script>alert('Owner account not found. This STAFFID must exist in both staff and owner table.'); window.location.href='owner-login.html';</script>";
    exit();
}

$row = mysqli_fetch_assoc($result);
$storedPassword = $row["STAFFPASS"];

$passwordOK = false;

if (password_verify($password, $storedPassword)) {
    $passwordOK = true;
} elseif ($password === $storedPassword) {
    $passwordOK = true;
}

if (!$passwordOK) {
    echo "<script>alert('Wrong owner password.'); window.location.href='owner-login.html';</script>";
    exit();
}

$_SESSION["OWNERID"] = $row["STAFFID"];
$_SESSION["OWNERSTAFFID"] = $row["STAFFID"];
$_SESSION["OWNERNAME"] = $row["STAFFNAME"];
$_SESSION["OWNEREQUITYTYPE"] = $row["EQUITYTYPE"];
$_SESSION["OWNERCONTRACTDURATION"] = $row["CONTRACTDURATION"];

echo "<script>alert('Owner login successful!'); window.location.href='" . addslashes($redirect) . "';</script>";
exit();
?>
