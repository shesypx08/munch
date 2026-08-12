<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";
require_once __DIR__ . "/email_helper.php";
require_once __DIR__ . "/php_user_popup.php";

munch_ensure_enhancements($conn);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: customer-signup.html");
    exit();
}

function back_alert($message) {
    munch_show_user_popup($message);
}

$name = isset($_POST["CUSTNAME"]) ? trim($_POST["CUSTNAME"]) : "";
$username = isset($_POST["CUSTUSERNAME"]) ? trim($_POST["CUSTUSERNAME"]) : "";
$email = isset($_POST["CUSTEMAIL"]) ? strtolower(trim($_POST["CUSTEMAIL"])) : "";
$phone = isset($_POST["PHONENO"]) ? preg_replace("/[^0-9]/", "", $_POST["PHONENO"]) : "";
$password = isset($_POST["CUSTPASSWORD"]) ? $_POST["CUSTPASSWORD"] : "";
$confirm = isset($_POST["CONFIRMPASSWORD"]) ? $_POST["CONFIRMPASSWORD"] : "";

if ($name === "" || $username === "" || $email === "" || $phone === "" || $password === "" || $confirm === "") {
    back_alert("Please complete all fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_alert("Invalid email address. Please enter a valid email such as name@email.com.");
}

if (strlen($phone) < 10 || strlen($phone) > 11) {
    back_alert("Phone number must be 10 to 11 digits.");
}

if (strlen($password) < 8) {
    back_alert("Password must be at least 8 characters.");
}

if ($password !== $confirm) {
    back_alert("Passwords do not match.");
}

$checkSql = "SELECT CUSTUSERNAME FROM customer WHERE CUSTUSERNAME = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "s", $username);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    back_alert("Username already exists.");
}

$emailCheck = mysqli_prepare($conn, "SELECT CUSTUSERNAME FROM customer WHERE CUSTEMAIL = ? LIMIT 1");
mysqli_stmt_bind_param($emailCheck, "s", $email);
mysqli_stmt_execute($emailCheck);
$emailResult = mysqli_stmt_get_result($emailCheck);

if ($emailResult && mysqli_num_rows($emailResult) > 0) {
    back_alert("This email is already registered. Please use another email or login.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$verifyToken = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 day'));

$sql = "INSERT INTO customer (CUSTUSERNAME, CUSTNAME, CUSTPASSWORD, PHONENO, CUSTEMAIL, EMAILVERIFIED, EMAIL_VERIFY_TOKEN, EMAIL_VERIFY_EXPIRES)
        VALUES (?, ?, ?, ?, ?, 0, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    back_alert("Registration is not available right now. Please try again later.");
}

mysqli_stmt_bind_param($stmt, "sssssss", $username, $name, $hashedPassword, $phone, $email, $verifyToken, $expires);

if (mysqli_stmt_execute($stmt)) {
    $verifyUrl = munch_base_url() . "/verify_email.php?token=" . urlencode($verifyToken);
    $message = "<p>Hi " . htmlspecialchars($name) . ",</p>" .
        "<p>Thank you for registering with Munch. Please verify your email before logging in.</p>" .
        "<p>This verification link will expire in 24 hours.</p>" .
        "<p style='font-size:13px;color:#64748b'>If the button does not work, copy this link:<br>" . htmlspecialchars($verifyUrl) . "</p>";

    munch_send_email($email, "Verify your Munch email", munch_email_template("Verify Your Email", $message, "Verify Email", $verifyUrl));

    header("Location: email_verification_notice.php?email=" . urlencode($email));
    exit();
}

back_alert("Registration failed. Please try again.");
?>
