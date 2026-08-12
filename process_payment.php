<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";
require_once __DIR__ . "/email_helper.php";
require_once __DIR__ . "/receipt_helper.php";

munch_ensure_enhancements($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit();
}

$type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : '';
$ref = isset($_POST['ref']) ? trim($_POST['ref']) : '';
$receiptEmail = isset($_POST['receipt_email']) ? strtolower(trim($_POST['receipt_email'])) : '';

if (!in_array($type, ['order', 'reservation']) || $ref === '') {
    die('Invalid payment request.');
}

if (!filter_var($receiptEmail, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Please enter a valid receipt email address.'); window.history.back();</script>";
    exit();
}

$payment = munch_fetch_payment($conn, $type, $ref);
if (!$payment) {
    die('Payment record not found.');
}

$transactionNo = 'MCHTXN' . date('ymd') . random_int(100000, 999999);

mysqli_begin_transaction($conn);
try {
    $updatePayment = mysqli_prepare($conn, "UPDATE payments SET PAYMENTSTATUS='Approved', TRANSACTIONNO=?, PAYEREMAIL=?, PAID_AT=NOW() WHERE RELATEDTYPE=? AND RELATEDID=?");
    mysqli_stmt_bind_param($updatePayment, 'ssss', $transactionNo, $receiptEmail, $type, $ref);
    if (!mysqli_stmt_execute($updatePayment)) {
        throw new Exception("Payment could not be updated right now. Please try again.");
    }

    if ($type === 'order') {
        $status = 'Paid';
        $updateOrder = mysqli_prepare($conn, "UPDATE orders SET ORDERSTATUS=? WHERE ORDERID=?");
        mysqli_stmt_bind_param($updateOrder, 'ss', $status, $ref);
        if (!mysqli_stmt_execute($updateOrder)) {
            throw new Exception("Order status could not be updated right now. Please try again.");
        }

        $saleCheck = mysqli_prepare($conn, "SELECT SALESID FROM sales WHERE ORDERID=? LIMIT 1");
        mysqli_stmt_bind_param($saleCheck, 's', $ref);
        mysqli_stmt_execute($saleCheck);
        $saleExists = mysqli_stmt_get_result($saleCheck);

        if (!$saleExists || mysqli_num_rows($saleExists) === 0) {
            $salesID = 'SA' . random_int(10000, 99999);
            $staffID = 'S001';
            $methodShort = substr($payment['PAYMENTMETHOD'], 0, 10);
            $amount = (float)$payment['AMOUNT'];
            $saleStmt = mysqli_prepare($conn, "INSERT INTO sales (SALESID, SALESDATE, SALESTOTAL, SALESPAYMETHOD, ORDERID, STAFFID) VALUES (?, CURDATE(), ?, ?, ?, ?)");
            mysqli_stmt_bind_param($saleStmt, 'sdsss', $salesID, $amount, $methodShort, $ref, $staffID);
            if (!mysqli_stmt_execute($saleStmt)) {
                throw new Exception("Payment record could not be completed right now. Please try again.");
            }
        }
    } else {
        $status = 'Confirmed';
        $updateReservation = mysqli_prepare($conn, "UPDATE reservation SET STATUS=?, EMAIL=?, PAYMENTMETHOD=? WHERE RESERVEID=?");
        mysqli_stmt_bind_param($updateReservation, 'ssss', $status, $receiptEmail, $payment['PAYMENTMETHOD'], $ref);
        if (!mysqli_stmt_execute($updateReservation)) {
            throw new Exception("Reservation status could not be updated right now. Please try again.");
        }
    }

    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Payment failed: " . htmlspecialchars($e->getMessage());
    exit();
}

$receipt = munch_get_receipt_data($conn, $type, $ref);
if ($receipt) {
    $pdf = munch_receipt_pdf_bytes($receipt);
    $receiptUrl = munch_base_url() . "/receipt.php?type=" . urlencode($type) . "&ref=" . urlencode($ref);
    $html = munch_receipt_email_html($receipt, $receiptUrl);
    $filename = 'Munch_Receipt_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $ref) . '.pdf';
    munch_send_email($receiptEmail, 'Munch Payment Approved - Receipt ' . $ref, $html, $pdf, $filename);
}

header("Location: receipt.php?type=" . urlencode($type) . "&ref=" . urlencode($ref) . "&paid=1");
exit();
?>
