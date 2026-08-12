<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/receipt_helper.php";

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$receipt = munch_get_receipt_data($conn, $type, $ref);

if (!$receipt) {
    die('Receipt not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt | Munch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="munch-clean-ui.css">
    <link rel="stylesheet" href="payment-receipt.css">
</head>
<body class="munch-receipt-page">
    <header>
        <nav class="navbar">
            <a href="index.html"><img src="img/footer-logo.png" class="logo" alt="Munch Logo"></a>
            <ul class="links-container">
                <li class="link-items"><a href="index.html" class="links">Home</a></li>
                <li class="link-items"><a href="menu.php" class="links">Menu</a></li>
                <li class="link-items"><a href="customerOrder.html" class="links">My Orders</a></li>
                <li class="link-items"><a href="Reservation.html" class="links">Reservation</a></li>
            </ul>
        </nav>
    </header>

    <main class="receipt-shell">
        <section class="receipt-card">
            <div class="receipt-top">
                <div class="receipt-brand">
                    <img src="img/footer-logo.png" alt="Munch Logo">
                    <div>
                        <span class="receipt-kicker">Official Receipt</span>
                        <h1><?php echo htmlspecialchars($receipt['title']); ?></h1>
                        <p class="muted">Munch Food Ordering System · Jalan 321, Shah Alam</p>
                    </div>
                </div>
                <div class="receipt-status">
                    <strong><?php echo htmlspecialchars($receipt['status']); ?></strong>
                    <p class="muted">Transaction: <?php echo htmlspecialchars($receipt['transaction']); ?></p>
                </div>
            </div>

            <?php if (isset($_GET['paid'])): ?>
                <p class="demo-note" style="margin-top:18px">Payment approved. The receipt email/PDF has been saved in <code>munch/emails_outbox</code> for localhost testing.</p>
            <?php endif; ?>

            <div class="receipt-section receipt-detail-grid">
                <div class="receipt-detail"><span><?php echo htmlspecialchars($receipt['reference_label']); ?></span><strong><?php echo htmlspecialchars($receipt['reference']); ?></strong></div>
                <div class="receipt-detail"><span>Date</span><strong><?php echo htmlspecialchars($receipt['date']); ?></strong></div>
                <div class="receipt-detail"><span>Customer</span><strong><?php echo htmlspecialchars($receipt['customer_name']); ?></strong></div>
                <div class="receipt-detail"><span>Email</span><strong><?php echo htmlspecialchars($receipt['customer_email'] ?: '-'); ?></strong></div>
                <div class="receipt-detail"><span>Phone</span><strong><?php echo htmlspecialchars($receipt['customer_phone'] ?: '-'); ?></strong></div>
                <div class="receipt-detail"><span>Payment Method</span><strong><?php echo htmlspecialchars($receipt['method']); ?></strong></div>
                <?php foreach ($receipt['details'] as $key => $value): ?>
                    <div class="receipt-detail"><span><?php echo htmlspecialchars($key); ?></span><strong><?php echo nl2br(htmlspecialchars($value)); ?></strong></div>
                <?php endforeach; ?>
            </div>

            <div class="receipt-section">
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipt['items'] as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php if (!empty($item['request'])): ?>
                                        <br><small class="muted"><?php echo htmlspecialchars($item['request']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['qty']); ?></td>
                                <td><?php echo munch_money($item['unit']); ?></td>
                                <td><?php echo munch_money($item['subtotal']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="receipt-total-box">
                    <div class="receipt-total-line"><span>Subtotal</span><strong><?php echo munch_money($receipt['subtotal']); ?></strong></div>
                    <?php if ($receipt['delivery'] > 0): ?>
                        <div class="receipt-total-line"><span>Delivery Charge</span><strong><?php echo munch_money($receipt['delivery']); ?></strong></div>
                    <?php endif; ?>
                    <?php if ($receipt['tax'] > 0): ?>
                        <div class="receipt-total-line"><span>Service Tax 6%</span><strong><?php echo munch_money($receipt['tax']); ?></strong></div>
                    <?php endif; ?>
                    <div class="receipt-total-line receipt-grand"><span>Total Paid</span><strong><?php echo munch_money($receipt['total']); ?></strong></div>
                </div>
            </div>

            <div class="receipt-actions">
                <a class="receipt-btn" href="download_receipt_pdf.php?type=<?php echo urlencode($type); ?>&ref=<?php echo urlencode($ref); ?>"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
                <button class="receipt-btn secondary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
                <a class="receipt-btn secondary" href="<?php echo $type === 'order' ? 'customerOrder.html' : 'Reservation.html'; ?>">Continue</a>
            </div>
        </section>
    </main>
</body>
</html>
