<?php
function munch_column_exists($conn, $table, $column) {
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$count > 0;
}

function munch_add_column_if_missing($conn, $table, $column, $definition) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        die("Invalid table name");
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        die("Invalid column name");
    }

    if (!munch_column_exists($conn, $table, $column)) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";

        if (!mysqli_query($conn, $sql)) {
            die("Alter table failed: " . mysqli_error($conn));
        }
    }
}

function munch_ensure_enhancements($conn) {
    if (!$conn) return;

    munch_add_column_if_missing($conn, 'customer', 'CUSTEMAIL', "VARCHAR(255) NULL AFTER `PHONENO`");
    munch_add_column_if_missing($conn, 'customer', 'EMAILVERIFIED', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `CUSTEMAIL`");
    munch_add_column_if_missing($conn, 'customer', 'EMAIL_VERIFY_TOKEN', "VARCHAR(64) NULL AFTER `EMAILVERIFIED`");
    munch_add_column_if_missing($conn, 'customer', 'EMAIL_VERIFY_EXPIRES', "DATETIME NULL AFTER `EMAIL_VERIFY_TOKEN`");

    $paymentSql = "CREATE TABLE IF NOT EXISTS payments (
        PAYMENTID varchar(30) NOT NULL,
        RELATEDTYPE varchar(20) NOT NULL,
        RELATEDID varchar(255) NOT NULL,
        CUSTID varchar(255) DEFAULT NULL,
        PAYEREMAIL varchar(255) DEFAULT NULL,
        AMOUNT decimal(10,2) NOT NULL,
        PAYMENTMETHOD varchar(40) NOT NULL,
        PAYMENTSTATUS varchar(20) NOT NULL DEFAULT 'Pending',
        TRANSACTIONNO varchar(40) DEFAULT NULL,
        CREATED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PAID_AT datetime DEFAULT NULL,
        PRIMARY KEY (PAYMENTID),
        KEY related_lookup (RELATEDTYPE, RELATEDID),
        KEY cust_lookup (CUSTID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    mysqli_query($conn, $paymentSql);
}

function munch_random_id($prefix, $length = 8) {
    return $prefix . strtoupper(substr(bin2hex(random_bytes(8)), 0, $length));
}

function munch_create_or_update_payment($conn, $type, $relatedID, $custID, $payerEmail, $amount, $method) {
    munch_ensure_enhancements($conn);

    $check = mysqli_prepare($conn, "SELECT PAYMENTID FROM payments WHERE RELATEDTYPE = ? AND RELATEDID = ? LIMIT 1");
    mysqli_stmt_bind_param($check, 'ss', $type, $relatedID);
    mysqli_stmt_execute($check);
    $existing = mysqli_stmt_get_result($check);

    if ($existing && ($row = mysqli_fetch_assoc($existing))) {
        $paymentID = $row['PAYMENTID'];
        $update = mysqli_prepare($conn, "UPDATE payments SET CUSTID=?, PAYEREMAIL=?, AMOUNT=?, PAYMENTMETHOD=?, PAYMENTSTATUS='Pending', TRANSACTIONNO=NULL, PAID_AT=NULL WHERE PAYMENTID=?");
        mysqli_stmt_bind_param($update, 'ssdss', $custID, $payerEmail, $amount, $method, $paymentID);
        mysqli_stmt_execute($update);
        return $paymentID;
    }

    $paymentID = munch_random_id('PAY', 10);
    $insert = mysqli_prepare($conn, "INSERT INTO payments (PAYMENTID, RELATEDTYPE, RELATEDID, CUSTID, PAYEREMAIL, AMOUNT, PAYMENTMETHOD, PAYMENTSTATUS) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
    mysqli_stmt_bind_param($insert, 'sssssds', $paymentID, $type, $relatedID, $custID, $payerEmail, $amount, $method);
    mysqli_stmt_execute($insert);
    return $paymentID;
}
?>
