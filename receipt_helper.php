<?php
require_once __DIR__ . '/system_init.php';

function munch_money($amount) {
    return 'RM' . number_format((float)$amount, 2);
}

function munch_fetch_payment($conn, $type, $ref) {
    munch_ensure_enhancements($conn);
    $stmt = mysqli_prepare($conn, "SELECT * FROM payments WHERE RELATEDTYPE = ? AND RELATEDID = ? ORDER BY CREATED_AT DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $type, $ref);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function munch_get_receipt_data($conn, $type, $ref) {
    munch_ensure_enhancements($conn);
    $type = strtolower(trim((string)$type));
    $payment = munch_fetch_payment($conn, $type, $ref);

    if ($type === 'order') {
        $stmt = mysqli_prepare($conn, "SELECT o.*, c.CUSTNAME, c.CUSTEMAIL, c.PHONENO FROM orders o LEFT JOIN customer c ON o.CUSTID = c.CUSTUSERNAME WHERE o.ORDERID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $ref);
        mysqli_stmt_execute($stmt);
        $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$order) return null;

        $itemsStmt = mysqli_prepare($conn, "SELECT om.*, m.MENUNAME, m.MENUPRICE FROM ordermenu om JOIN menu m ON om.MENUID = m.MENUID WHERE om.ORDERID = ? ORDER BY m.MENUNAME");
        mysqli_stmt_bind_param($itemsStmt, 's', $ref);
        mysqli_stmt_execute($itemsStmt);
        $itemsResult = mysqli_stmt_get_result($itemsStmt);

        $items = [];
        $subtotal = 0.0;
        while ($row = mysqli_fetch_assoc($itemsResult)) {
            $line = (float)$row['SUBTOTAL'];
            $subtotal += $line;
            $items[] = [
                'name' => $row['MENUNAME'],
                'qty' => (int)$row['QUANTITY'],
                'unit' => (float)$row['MENUPRICE'],
                'subtotal' => $line,
                'request' => $row['REQUEST'] ?? ''
            ];
        }

        $delivery = strtolower($order['ORDERTYPE'] ?? '') === 'delivery' ? 6.00 : 0.00;
        $tax = ($subtotal + $delivery) * 0.06;
        $total = $payment ? (float)$payment['AMOUNT'] : ($subtotal + $delivery + $tax);

        return [
            'type' => 'order',
            'title' => 'Food Order Receipt',
            'reference_label' => 'Order ID',
            'reference' => $ref,
            'date' => $payment && $payment['PAID_AT'] ? $payment['PAID_AT'] : ($order['ORDERDATE'] ?? date('Y-m-d')),
            'customer_name' => $order['CUSTNAME'] ?: $order['CUSTID'],
            'customer_email' => $payment['PAYEREMAIL'] ?? ($order['CUSTEMAIL'] ?? ''),
            'customer_phone' => $order['PHONENO'] ?? '',
            'method' => $payment['PAYMENTMETHOD'] ?? 'Payment',
            'status' => $payment['PAYMENTSTATUS'] ?? $order['ORDERSTATUS'],
            'transaction' => $payment['TRANSACTIONNO'] ?? '-',
            'details' => [
                'Order Type' => $order['ORDERTYPE'] ?? '-',
                'Delivery Address' => $order['Address'] ?: '-',
                'Special Request' => $order['ORDERREMARK'] ?: '-'
            ],
            'items' => $items,
            'subtotal' => $subtotal,
            'delivery' => $delivery,
            'tax' => $tax,
            'total' => $total
        ];
    }

    if ($type === 'reservation') {
        $stmt = mysqli_prepare($conn, "SELECT r.*, c.CUSTEMAIL, c.CUSTNAME AS ACCOUNTNAME FROM reservation r LEFT JOIN customer c ON r.CUSTID = c.CUSTUSERNAME WHERE r.RESERVEID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $ref);
        mysqli_stmt_execute($stmt);
        $reservation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$reservation) return null;

        $isEvent = strtolower($reservation['SESSION']) === 'catering';
        $label = $isEvent ? 'Catering / Event Booking Deposit' : 'Table Reservation Deposit';
        $amount = $payment ? (float)$payment['AMOUNT'] : (float)$reservation['DEPOSIT'];

        return [
            'type' => 'reservation',
            'title' => $isEvent ? 'Event Booking Receipt' : 'Reservation Receipt',
            'reference_label' => 'Reservation ID',
            'reference' => $ref,
            'date' => $payment && $payment['PAID_AT'] ? $payment['PAID_AT'] : ($reservation['RESERVEDATE'] ?? date('Y-m-d')),
            'customer_name' => $reservation['FULLNAME'] ?: ($reservation['ACCOUNTNAME'] ?? $reservation['CUSTID']),
            'customer_email' => $payment['PAYEREMAIL'] ?? ($reservation['EMAIL'] ?? ''),
            'customer_phone' => $reservation['PHONENO'] ?? '',
            'method' => $payment['PAYMENTMETHOD'] ?? ($reservation['PAYMENTMETHOD'] ?? 'Payment'),
            'status' => $payment['PAYMENTSTATUS'] ?? ($reservation['STATUS'] ?? '-'),
            'transaction' => $payment['TRANSACTIONNO'] ?? '-',
            'details' => [
                'Booking Date' => $reservation['RESERVEDATE'] ?? '-',
                'Session' => $reservation['SESSION'] ?? '-',
                'Time Slot' => $reservation['TIMESLOT'] ?? '-',
                'Guest Count' => $reservation['guestCount'] ?? '-',
                'Occasion' => $reservation['OCCASION'] ?: '-',
                'Special Request' => $reservation['SPECIALREQ'] ?: '-'
            ],
            'items' => [[
                'name' => $label,
                'qty' => 1,
                'unit' => $amount,
                'subtotal' => $amount,
                'request' => ''
            ]],
            'subtotal' => $amount,
            'delivery' => 0,
            'tax' => 0,
            'total' => $amount
        ];
    }

    return null;
}

function munch_receipt_pdf_bytes($receipt) {
    $lines = [];
    $lines[] = 'MUNCH FOOD ORDERING SYSTEM';
    $lines[] = $receipt['title'];
    $lines[] = str_repeat('-', 42);
    $lines[] = $receipt['reference_label'] . ': ' . $receipt['reference'];
    $lines[] = 'Date: ' . $receipt['date'];
    $lines[] = 'Customer: ' . $receipt['customer_name'];
    $lines[] = 'Email: ' . ($receipt['customer_email'] ?: '-');
    $lines[] = 'Phone: ' . ($receipt['customer_phone'] ?: '-');
    $lines[] = 'Payment Method: ' . $receipt['method'];
    $lines[] = 'Transaction No: ' . $receipt['transaction'];
    $lines[] = 'Payment Status: ' . $receipt['status'];
    $lines[] = str_repeat('-', 42);

    foreach ($receipt['details'] as $key => $value) {
        $value = str_replace(["\r", "\n"], ' | ', (string)$value);
        if (strlen($value) > 82) $value = substr($value, 0, 79) . '...';
        $lines[] = $key . ': ' . $value;
    }

    $lines[] = str_repeat('-', 42);
    $lines[] = 'Items';
    foreach ($receipt['items'] as $item) {
        $lines[] = $item['qty'] . ' x ' . $item['name'] . ' - ' . munch_money($item['subtotal']);
        if (!empty($item['request'])) {
            $req = str_replace(["\r", "\n"], ' ', $item['request']);
            if (strlen($req) > 70) $req = substr($req, 0, 67) . '...';
            $lines[] = '   Note: ' . $req;
        }
    }

    $lines[] = str_repeat('-', 42);
    $lines[] = 'Subtotal: ' . munch_money($receipt['subtotal']);
    if ($receipt['delivery'] > 0) $lines[] = 'Delivery Charge: ' . munch_money($receipt['delivery']);
    if ($receipt['tax'] > 0) $lines[] = 'Service Tax 6%: ' . munch_money($receipt['tax']);
    $lines[] = 'TOTAL PAID: ' . munch_money($receipt['total']);
    $lines[] = '';
    $lines[] = 'Thank you for choosing Munch.';

    return munch_simple_pdf($lines);
}

function munch_pdf_escape($text) {
    $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', (string)$text);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function munch_simple_pdf($lines) {
    $content = "BT\n/F1 12 Tf\n50 790 Td\n14 TL\n";
    $first = true;
    foreach ($lines as $line) {
        $line = preg_replace('/\s+/', ' ', (string)$line);
        if (!$first) $content .= "T*\n";
        $content .= '(' . munch_pdf_escape($line) . ") Tj\n";
        $first = false;
    }
    $content .= "ET";

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    return $pdf;
}

function munch_receipt_email_html($receipt, $receiptUrl) {
    $message = "<p>Hi " . htmlspecialchars($receipt['customer_name']) . ",</p>" .
        "<p>Your payment has been approved. Here is your receipt summary:</p>" .
        "<ul>" .
        "<li><strong>" . htmlspecialchars($receipt['reference_label']) . ":</strong> " . htmlspecialchars($receipt['reference']) . "</li>" .
        "<li><strong>Total Paid:</strong> " . munch_money($receipt['total']) . "</li>" .
        "<li><strong>Transaction No:</strong> " . htmlspecialchars($receipt['transaction']) . "</li>" .
        "</ul>" .
        "<p>A PDF copy of the receipt is attached/saved in the local outbox for this prototype.</p>";

    return munch_email_template('Payment Approved', $message, 'View Receipt', $receiptUrl);
}
?>
