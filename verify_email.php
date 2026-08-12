<?php
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";

munch_ensure_enhancements($conn);
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$verified = false;
$message = 'Invalid verification link.';

if ($token !== '') {
    $stmt = mysqli_prepare($conn, "SELECT CUSTUSERNAME, CUSTNAME, CUSTEMAIL, EMAIL_VERIFY_EXPIRES FROM customer WHERE EMAIL_VERIFY_TOKEN = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customer = $result ? mysqli_fetch_assoc($result) : null;

    if ($customer) {
        if (!empty($customer['EMAIL_VERIFY_EXPIRES']) && strtotime($customer['EMAIL_VERIFY_EXPIRES']) < time()) {
            $message = 'This verification link has expired. Please register again or request a new link.';
        } else {
            $update = mysqli_prepare($conn, "UPDATE customer SET EMAILVERIFIED = 1, EMAIL_VERIFY_TOKEN = NULL, EMAIL_VERIFY_EXPIRES = NULL WHERE CUSTUSERNAME = ?");
            mysqli_stmt_bind_param($update, 's', $customer['CUSTUSERNAME']);
            mysqli_stmt_execute($update);
            $verified = true;
            $message = 'Your email has been verified successfully. You can login now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | Munch</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="munch-clean-ui.css">
    <link rel="stylesheet" href="payment-receipt.css">
</head>
<body class="munch-status-page">
    <main class="status-card">
        <div class="status-icon <?php echo $verified ? 'success' : 'error'; ?>"><?php echo $verified ? '✓' : '!'; ?></div>
        <h1><?php echo $verified ? 'Email Verified' : 'Verification Failed'; ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a class="btn" href="customer-login.html">Go to Login</a>
    </main>
</body>
</html>
