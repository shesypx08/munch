<?php
session_start();
include "dbconnect.php";

header("Content-Type: application/json");

function respond($data) {
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION["STAFFID"]) || trim($_SESSION["STAFFID"]) === "") {
    respond([
        "success" => false,
        "message" => "Please login as staff first."
    ]);
}

$staffID = trim($_SESSION["STAFFID"]);

$staff = [
    "STAFFID" => $staffID,
    "STAFFNAME" => $_SESSION["STAFFNAME"] ?? "Staff",
    "STAFFROLE" => $_SESSION["STAFFROLE"] ?? "Staff"
];

$staffStmt = mysqli_prepare($conn, "SELECT STAFFID, STAFFNAME, STAFFROLE FROM staff WHERE STAFFID = ?");
if ($staffStmt) {
    mysqli_stmt_bind_param($staffStmt, "s", $staffID);
    mysqli_stmt_execute($staffStmt);
    $staffResult = mysqli_stmt_get_result($staffStmt);

    if ($row = mysqli_fetch_assoc($staffResult)) {
        $staff = $row;
    }
}


function normalizeStaffOrderStatus($status) {
    $status = strtolower(trim((string)$status));

    if ($status === "completed" || $status === "served") return "Completed";
    if ($status === "ready") return "Ready";
    if ($status === "preparing") return "Preparing";

    // Paid orders are still active kitchen orders, so staff workflow starts at Pending.
    return "Pending";
}

function getCountByStatus($conn, $status) {
    $sql = "SELECT COUNT(*) AS total
            FROM orders
            WHERE LOWER(ORDERSTATUS) = LOWER(?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;

    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return (int)($row["total"] ?? 0);
}

$paidAsPendingResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE LOWER(ORDERSTATUS) IN ('pending', 'paid')");
$paidAsPendingRow = $paidAsPendingResult ? mysqli_fetch_assoc($paidAsPendingResult) : ["total" => 0];

$stats = [
    "pending" => (int)($paidAsPendingRow["total"] ?? 0),
    "preparing" => getCountByStatus($conn, "Preparing"),
    "ready" => getCountByStatus($conn, "Ready"),
    "completed" => getCountByStatus($conn, "Completed"),
    "served" => getCountByStatus($conn, "Served"),
    "todaySales" => 0
];

$salesSql = "SELECT COALESCE(SUM(SALESTOTAL), 0) AS todaySales
             FROM sales
             WHERE DATE(SALESDATE) = CURDATE()";

$salesResult = mysqli_query($conn, $salesSql);

if ($salesResult && $salesRow = mysqli_fetch_assoc($salesResult)) {
    $stats["todaySales"] = (float)$salesRow["todaySales"];
}

/*
    IMPORTANT:
    This shows ALL active/non-completed orders.
    It does NOT limit to 3.
    It hides completed/served/cancelled orders.
*/
$orderSql = "SELECT
                o.ORDERID,
                o.ORDERDATE,
                o.ORDERTYPE,
                o.ORDERSTATUS,
                o.CUSTID,
                COALESCE(c.CUSTNAME, o.CUSTID) AS CUSTNAME,
                COALESCE(c.PHONENO, '-') AS PHONENO,
                COALESCE(s.SALESPAYMETHOD, '-') AS PAYMENTMETHOD,
                COALESCE(s.SALESTOTAL, SUM(om.SUBTOTAL), 0) AS TOTALAMOUNT,
                GROUP_CONCAT(CONCAT(m.MENUNAME, ' x', om.QUANTITY) SEPARATOR ', ') AS ITEMS
             FROM orders o
             LEFT JOIN customer c ON o.CUSTID = c.CUSTUSERNAME
             LEFT JOIN ordermenu om ON o.ORDERID = om.ORDERID
             LEFT JOIN menu m ON om.MENUID = m.MENUID
             LEFT JOIN sales s ON o.ORDERID = s.ORDERID
             WHERE LOWER(o.ORDERSTATUS) NOT IN ('completed', 'served', 'cancelled')
             GROUP BY
                o.ORDERID,
                o.ORDERDATE,
                o.ORDERTYPE,
                o.ORDERSTATUS,
                o.CUSTID,
                c.CUSTNAME,
                c.PHONENO,
                s.SALESPAYMETHOD,
                s.SALESTOTAL
             ORDER BY
                CASE LOWER(o.ORDERSTATUS)
                    WHEN 'pending' THEN 1
                    WHEN 'paid' THEN 1
                    WHEN 'preparing' THEN 2
                    WHEN 'ready' THEN 3
                    ELSE 4
                END,
                o.ORDERDATE DESC,
                o.ORDERID DESC";

$orderResult = mysqli_query($conn, $orderSql);

if (!$orderResult) {
    respond([
        "success" => false,
        "message" => "Orders are not available right now. Please try again later."
    ]);
}

$orders = [];

while ($row = mysqli_fetch_assoc($orderResult)) {
    $orders[] = [
        "ORDERID" => $row["ORDERID"],
        "ORDERDATE" => $row["ORDERDATE"],
        "ORDERTYPE" => $row["ORDERTYPE"],
        "ORDERSTATUS" => normalizeStaffOrderStatus($row["ORDERSTATUS"]),
        "CUSTID" => $row["CUSTID"],
        "CUSTNAME" => $row["CUSTNAME"],
        "PHONENO" => $row["PHONENO"],
        "PAYMENTMETHOD" => $row["PAYMENTMETHOD"],
        "TOTALAMOUNT" => $row["TOTALAMOUNT"],
        "ITEMS" => $row["ITEMS"] ?: "No menu item recorded"
    ];
}

$paymentSql = "SELECT
                    s.SALESID,
                    s.SALESDATE,
                    s.SALESTOTAL,
                    s.SALESPAYMETHOD,
                    s.ORDERID,
                    o.CUSTID,
                    COALESCE(c.CUSTNAME, o.CUSTID) AS CUSTNAME
               FROM sales s
               INNER JOIN orders o ON s.ORDERID = o.ORDERID
               LEFT JOIN customer c ON o.CUSTID = c.CUSTUSERNAME
               ORDER BY s.SALESDATE DESC, s.SALESID DESC
               LIMIT 30";

$paymentResult = mysqli_query($conn, $paymentSql);

if (!$paymentResult) {
    respond([
        "success" => false,
        "message" => "Payments are not available right now. Please try again later."
    ]);
}

$payments = [];

while ($row = mysqli_fetch_assoc($paymentResult)) {
    $payments[] = [
        "SALESID" => $row["SALESID"],
        "SALESDATE" => $row["SALESDATE"],
        "SALESTOTAL" => $row["SALESTOTAL"],
        "SALESPAYMETHOD" => $row["SALESPAYMETHOD"],
        "ORDERID" => $row["ORDERID"],
        "CUSTID" => $row["CUSTID"],
        "CUSTNAME" => $row["CUSTNAME"]
    ];
}

respond([
    "success" => true,
    "staff" => $staff,
    "stats" => $stats,
    "orders" => $orders,
    "payments" => $payments
]);
?>
