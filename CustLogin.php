<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";
require_once __DIR__ . "/email_helper.php";

munch_ensure_enhancements($conn);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: customer-login.html");
    exit();
}

$username = isset($_POST["USERNAME"]) ? trim($_POST["USERNAME"]) : "";
$password = isset($_POST["PASSWORD"]) ? $_POST["PASSWORD"] : "";
$redirect = isset($_POST["redirect"]) ? trim($_POST["redirect"]) : "profile.html";

$allowedRedirects = ["profile.html", "order.html", "Reservation.html", "customerOrder.html", "menu.php", "index.html"];
if (!in_array($redirect, $allowedRedirects)) {
    $redirect = "profile.html";
}

if ($username === "" || $password === "") {
    echo "<script>alert('Please enter username and password.'); window.location.href='customer-login.html';</script>";
    exit();
}

$sql = "SELECT CUSTUSERNAME, CUSTNAME, PHONENO, CUSTPASSWORD, CUSTEMAIL, EMAILVERIFIED
        FROM customer
        WHERE CUSTUSERNAME = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo "<script>alert('Login is not available right now. Please try again later.'); window.location.href='customer-login.html';</script>";
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "<script>alert('Username not found.'); window.location.href='customer-login.html';</script>";
    exit();
}

$row = mysqli_fetch_assoc($result);
$storedPassword = $row["CUSTPASSWORD"];

$passwordOK = password_verify($password, $storedPassword) || $password === $storedPassword;

if (!$passwordOK) {
    echo "<script>alert('Wrong password.'); window.location.href='customer-login.html';</script>";
    exit();
}

if (!empty($row['CUSTEMAIL']) && (int)$row['EMAILVERIFIED'] !== 1) {
    echo "<script>alert('Please verify your email first. Check munch/emails_outbox if you are testing on localhost.'); window.location.href='email_verification_notice.php?email=" . urlencode($row['CUSTEMAIL']) . "';</script>";
    exit();
}

$_SESSION["CUSTUSERNAME"] = $row["CUSTUSERNAME"];
$_SESSION["CUSTNAME"] = $row["CUSTNAME"];
$_SESSION["CUSTEMAIL"] = $row["CUSTEMAIL"] ?? "";

header("Location: " . $redirect);
exit();
?>
