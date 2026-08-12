<?php
session_start();
include "dbconnect.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: staff-login.html");
    exit();
}

$staffID = isset($_POST["STAFFID"]) ? trim($_POST["STAFFID"]) : "";
$staffPassword = isset($_POST["STAFFPASSWORD"]) ? $_POST["STAFFPASSWORD"] : "";
$redirect = isset($_POST["redirect"]) ? trim($_POST["redirect"]) : "staff-dashboard.html";

if ($redirect === "" || !str_starts_with($redirect, "staff-dashboard.html")) {
    $redirect = "staff-dashboard.html";
}

if ($staffID === "" || $staffPassword === "") {
    echo "<script>alert('Please enter staff ID and password.'); window.location.href='staff-login.html';</script>";
    exit();
}

$sql = "SELECT STAFFID, STAFFNAME, STAFFPHONENO, STAFFPASS, STAFFROLE
        FROM staff
        WHERE STAFFID = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo "<script>alert('Staff login is not available right now. Please try again later.'); window.location.href='staff-login.html';</script>";
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $staffID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "<script>alert('Wrong Staff ID. Please check your Staff ID.'); window.location.href='staff-login.html';</script>";
    exit();
}

$row = mysqli_fetch_assoc($result);
$storedPassword = $row["STAFFPASS"];

$passwordOK = password_verify($staffPassword, $storedPassword) || $staffPassword === $storedPassword;

if (!$passwordOK) {
    echo "<script>alert('Wrong staff password.'); window.location.href='staff-login.html';</script>";
    exit();
}

$_SESSION["STAFFID"] = $row["STAFFID"];
$_SESSION["STAFFNAME"] = $row["STAFFNAME"];
$_SESSION["STAFFROLE"] = $row["STAFFROLE"];

header("Location: " . $redirect);
exit();
?>
