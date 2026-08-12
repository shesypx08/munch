<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";

munch_ensure_enhancements($conn);
header("Content-Type: application/json");

if (!isset($_SESSION["CUSTUSERNAME"]) || trim($_SESSION["CUSTUSERNAME"]) === "") {
    echo json_encode(["success" => false, "message" => "Please login first."]);
    exit();
}

$custID = $_SESSION["CUSTUSERNAME"];

function menuImagePath($menuID) {
    $menuID = trim((string)$menuID);
    $extensions = ["jpg", "jpeg", "png", "webp", "JPG", "JPEG", "PNG", "WEBP"];

    foreach ($extensions as $ext) {
        $imagePath = "img/" . $menuID . "." . $ext;

        if (file_exists(__DIR__ . "/" . $imagePath)) {
            return $imagePath;
        }
    }

    return "img/restaurant-1.png";
}


function normalizeCustomerOrderStatus($status) {
    $status = strtolower(trim((string)$status));

    if ($status === "" || $status === "paid" || $status === "approved" || $status === "pending payment") {
        return "Pending";
    }

    if ($status === "pending") return "Pending";
    if ($status === "preparing") return "Preparing";
    if ($status === "ready") return "Ready";
    if ($status === "completed" || $status === "served") return "Completed";
    if ($status === "cancelled" || $status === "canceled") return "Cancelled";

    return ucwords($status);
}


$sql = "SELECT 
            o.ORDERID, o.ORDERDATE, o.ORDERTYPE, o.ORDERSTATUS, o.ORDERREMARK, o.Address,
            om.MENUID, m.MENUNAME, m.MENUCATEGORY, om.QUANTITY, om.SUBTOTAL, om.REQUEST,
            p.PAYMENTMETHOD, p.PAYMENTSTATUS, p.AMOUNT, p.TRANSACTIONNO
        FROM orders o
        INNER JOIN ordermenu om ON o.ORDERID = om.ORDERID
        INNER JOIN menu m ON om.MENUID = m.MENUID
        LEFT JOIN payments p ON p.RELATEDTYPE = 'order' AND p.RELATEDID = o.ORDERID
        WHERE o.CUSTID = ?
        ORDER BY o.ORDERDATE DESC, o.ORDERID DESC, CAST(SUBSTRING(om.MENUID, 2) AS UNSIGNED) ASC, om.MENUID ASC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Orders are not available right now. Please try again later."]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $custID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    $orderID = $row["ORDERID"];

    if (!isset($orders[$orderID])) {
        $paidAmount = isset($row['AMOUNT']) ? (float)$row['AMOUNT'] : 0;
        $orders[$orderID] = [
            "ORDERID" => $row["ORDERID"],
            "ORDERDATE" => $row["ORDERDATE"],
            "ORDERTYPE" => $row["ORDERTYPE"],
            "ORDERSTATUS" => $row["ORDERSTATUS"],
            "STATUS" => normalizeCustomerOrderStatus($row["ORDERSTATUS"]),
            "PAYMENTSTATUS" => $row["PAYMENTSTATUS"] ?: "Pending",
            "ORDERREMARK" => $row["ORDERREMARK"],
            "Address" => $row["Address"],
            "PREVIEWIMAGE" => menuImagePath($row["MENUID"]),
            "PREVIEWITEM" => $row["MENUNAME"],
            "PAYMENTMETHOD" => $row["PAYMENTMETHOD"] ?: "Pending",
            "TRANSACTIONNO" => $row["TRANSACTIONNO"] ?: "-",
            "TOTAL" => 0,
            "PAIDAMOUNT" => $paidAmount,
            "TOTALAMOUNT" => $paidAmount,
            "QUANTITY" => 0,
            "RECEIPTURL" => "receipt.php?type=order&ref=" . urlencode($orderID),
            "PAYMENTURL" => "payment-gateway.php?type=order&ref=" . urlencode($orderID),
            "ITEMS" => []
        ];
    }

    $orders[$orderID]["TOTAL"] += (float)$row["SUBTOTAL"];
    $orders[$orderID]["QUANTITY"] += (int)$row["QUANTITY"];

    $orders[$orderID]["ITEMS"][] = [
        "MENUID" => $row["MENUID"],
        "MENUNAME" => $row["MENUNAME"],
        "MENUCATEGORY" => $row["MENUCATEGORY"],
        "IMAGE" => menuImagePath($row["MENUID"]),
        "QUANTITY" => (int)$row["QUANTITY"],
        "SUBTOTAL" => (float)$row["SUBTOTAL"],
        "REQUEST" => $row["REQUEST"]
    ];
}

foreach ($orders as &$order) {
    if ((float)$order["PAIDAMOUNT"] <= 0) {
        $subtotal = (float)$order["TOTAL"];
        $delivery = strtolower($order["ORDERTYPE"] ?? '') === 'delivery' ? 6.00 : 0.00;
        $order["TOTALAMOUNT"] = $subtotal + $delivery + (($subtotal + $delivery) * 0.06);
    }
    unset($order["PAIDAMOUNT"]);
}
unset($order);

echo json_encode(["success" => true, "orders" => array_values($orders)]);
?>
