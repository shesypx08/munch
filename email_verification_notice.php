<?php
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Email | Munch</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="munch-clean-ui.css">
    <link rel="stylesheet" href="payment-receipt.css">
</head>
<body class="munch-status-page">
    <main class="status-card">
        <div class="status-icon success">✉</div>
        <h1>Check Your Email</h1>
        <p>We sent a verification link to <strong><?php echo htmlspecialchars($email ?: 'your email'); ?></strong>.</p>
        <p class="muted">For localhost/XAMPP testing, open the latest HTML file inside <code>munch/emails_outbox</code> and click the verification link.</p>
        <a class="btn" href="customer-login.html">Back to Login</a>
    </main>
</body>
</html>
