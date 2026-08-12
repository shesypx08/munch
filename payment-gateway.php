<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";
require_once __DIR__ . "/receipt_helper.php";

munch_ensure_enhancements($conn);
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if (!in_array($type, ['order', 'reservation']) || $ref === '') {
    die('Invalid payment reference.');
}

$payment = munch_fetch_payment($conn, $type, $ref);
if (!$payment) {
    die('Payment record not found. Please create the order/reservation again.');
}

$receipt = munch_get_receipt_data($conn, $type, $ref);
if (!$receipt) {
    die('Related order/reservation not found.');
}

if (strtolower($payment['PAYMENTSTATUS']) === 'approved') {
    header("Location: receipt.php?type=" . urlencode($type) . "&ref=" . urlencode($ref));
    exit();
}

$method = $payment['PAYMENTMETHOD'] ?: 'Card Payment';
$amount = (float)$payment['AMOUNT'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Munch Secure Payment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="munch-clean-ui.css">
    <link rel="stylesheet" href="payment-receipt.css">
</head>
<body class="munch-payment-page">
    <header>
        <nav class="navbar">
            <a href="index.html"><img src="img/footer-logo.png" class="logo" alt="Munch Logo"></a>
            <ul class="links-container">
                <li class="link-items"><a href="index.html" class="links">Home</a></li>
                <li class="link-items"><a href="menu.php" class="links">Menu</a></li>
                <li class="link-items"><a href="order.html" class="links">Order</a></li>
                <li class="link-items"><a href="Reservation.html" class="links">Reservation</a></li>
            </ul>
        </nav>
    </header>

    <main class="payment-shell">
        <section class="payment-panel">
            <aside class="payment-summary-side">
                <span class="mini-label">MunchPay Secure Demo</span>
                <h1>Complete your payment</h1>
                <p>This page simulates a real payment gateway for your academic prototype. No real money will be charged.</p>

                <div class="amount-box">
                    <span>Total payable</span>
                    <strong><?php echo munch_money($amount); ?></strong>
                </div>

                <div class="summary-list">
                    <div class="summary-row"><span>Reference</span><strong><?php echo htmlspecialchars($receipt['reference_label'] . ' ' . $ref); ?></strong></div>
                    <div class="summary-row"><span>Customer</span><strong><?php echo htmlspecialchars($receipt['customer_name']); ?></strong></div>
                    <div class="summary-row"><span>Email</span><strong><?php echo htmlspecialchars($receipt['customer_email'] ?: '-'); ?></strong></div>
                    <div class="summary-row"><span>Payment Method</span><strong><?php echo htmlspecialchars($method); ?></strong></div>
                </div>
            </aside>

            <section class="payment-form-side">
                <div class="secure-badge"><i class="fa-solid fa-lock"></i> Secure encrypted checkout</div>
                <span class="mini-label">Payment Details</span>
                <h2><?php echo htmlspecialchars($method); ?></h2>
                <p class="demo-note">Use any realistic test details. The system will approve the payment, generate a receipt, and save/send the email notification.</p>

                <div class="payment-method-tabs">
                    <div class="method-chip <?php echo stripos($method, 'Card') !== false ? 'active' : ''; ?>">Card</div>
                    <div class="method-chip <?php echo stripos($method, 'Bank') !== false ? 'active' : ''; ?>">Banking</div>
                    <div class="method-chip <?php echo stripos($method, 'Wallet') !== false ? 'active' : ''; ?>">E-Wallet</div>
                    <div class="method-chip <?php echo stripos($method, 'QR') !== false ? 'active' : ''; ?>">QR</div>
                </div>

                <form action="process_payment.php" method="POST" class="payment-form-grid">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref); ?>">

                    <?php if (stripos($method, 'Bank') !== false): ?>
                        <div class="payment-field full">
                            <label>Bank</label>
                            <select name="bank" required>
                                <option value="Maybank2u">Maybank2u</option>
                                <option value="CIMB Clicks">CIMB Clicks</option>
                                <option value="Bank Islam">Bank Islam</option>
                                <option value="RHB Now">RHB Now</option>
                            </select>
                        </div>
                        <div class="payment-field"><label>Account Holder Name</label><input name="payer_name" placeholder="Nurul Aiesyah" required></div>
                        <div class="payment-field"><label>TAC / OTP</label><input name="otp" placeholder="123456" maxlength="6" required></div>
                    <?php elseif (stripos($method, 'Wallet') !== false): ?>
                        <div class="payment-field full"><label>E-Wallet Provider</label><select name="wallet" required><option>Touch 'n Go eWallet</option><option>GrabPay</option><option>Boost</option><option>ShopeePay</option></select></div>
                        <div class="payment-field"><label>Phone Number</label><input name="wallet_phone" placeholder="0123456789" required></div>
                        <div class="payment-field"><label>OTP</label><input name="otp" placeholder="123456" maxlength="6" required></div>
                    <?php elseif (stripos($method, 'QR') !== false): ?>
                        <div class="payment-field full"><label>QR Reference</label><input name="qr_ref" value="MUNCH-QR-<?php echo htmlspecialchars($ref); ?>" readonly></div>
                        <div class="payment-field full"><label>Upload/Enter Payment Reference</label><input name="payment_ref" placeholder="Example: QR123456" required></div>
                    <?php else: ?>
                        <div class="payment-field full"><label>Name on Card</label><input name="card_name" placeholder="NURUL AIESYAH" required></div>
                        <div class="payment-field full"><label>Card Number</label><input name="card_no" placeholder="4242 4242 4242 4242" minlength="12" required></div>
                        <div class="payment-field"><label>Expiry</label><input name="expiry" placeholder="12/29" required></div>
                        <div class="payment-field"><label>CVV</label><input name="cvv" placeholder="123" maxlength="4" required></div>
                    <?php endif; ?>

                    <div class="payment-field full">
                        <label>Email for Receipt</label>
                        <input type="email" name="receipt_email" value="<?php echo htmlspecialchars($receipt['customer_email']); ?>" required>
                    </div>

                    <div class="payment-field full pay-actions">
                        <button class="pay-btn" type="submit"><i class="fa-solid fa-check-circle"></i> Approve Payment</button>
                        <a class="pay-btn secondary" href="<?php echo $type === 'order' ? 'order.html' : 'Reservation.html'; ?>">Cancel</a>
                    </div>
                </form>
            </section>
        </section>
    </main>
</body>
</html>
