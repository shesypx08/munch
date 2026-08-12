<?php
include "dbconnect.php";
require_once __DIR__ . "/receipt_helper.php";

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$receipt = munch_get_receipt_data($conn, $type, $ref);

if (!$receipt) {
    http_response_code(404);
    echo 'Receipt not found.';
    exit();
}

$pdf = munch_receipt_pdf_bytes($receipt);
$filename = 'Munch_Receipt_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $ref) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit();
?>
