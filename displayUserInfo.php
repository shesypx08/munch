<?php
session_start();
include "dbconnect.php";

header("Content-Type: application/json");

function respond($data) {
    echo json_encode($data);
    exit();
}

function columnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);

    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = mysqli_query($conn, $sql);

    return $result && mysqli_num_rows($result) > 0;
}

if (!isset($_SESSION["CUSTUSERNAME"]) || trim($_SESSION["CUSTUSERNAME"]) === "") {
    respond(["success" => false, "message" => "User not logged in"]);
}

$username = trim($_SESSION["CUSTUSERNAME"]);

$hasProfilePic = columnExists($conn, "customer", "CUSTPROFILEPIC");

if ($hasProfilePic) {
    $customerSql = "SELECT CUSTNAME, CUSTUSERNAME, PHONENO, CUSTPROFILEPIC
                    FROM customer
                    WHERE CUSTUSERNAME = ?";
} else {
    $customerSql = "SELECT CUSTNAME, CUSTUSERNAME, PHONENO
                    FROM customer
                    WHERE CUSTUSERNAME = ?";
}

$stmt = mysqli_prepare($conn, $customerSql);

if (!$stmt) {
    respond(["success" => false, "message" => "Profile information is not available right now. Please try again later."]);
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_assoc($result);

if (!$customer) {
    respond(["success" => false, "message" => "Customer not found"]);
}

if (!$hasProfilePic || !isset($customer["CUSTPROFILEPIC"]) || trim($customer["CUSTPROFILEPIC"]) === "") {
    $customer["CUSTPROFILEPIC"] = "img/user-1.png";
}

$recentOrder = null;
$sql = "SELECT 
            o.ORDERID,
            o.ORDERDATE,
            o.ORDERSTATUS,
            GROUP_CONCAT(CONCAT(m.MENUNAME, ' x', om.QUANTITY) SEPARATOR ', ') AS MEALS,
            COALESCE(s.SALESTOTAL, SUM(om.SUBTOTAL), 0) AS TOTAL
        FROM orders o
        LEFT JOIN ordermenu om ON o.ORDERID = om.ORDERID
        LEFT JOIN menu m ON om.MENUID = m.MENUID
        LEFT JOIN sales s ON o.ORDERID = s.ORDERID
        WHERE o.CUSTID = ?
        GROUP BY o.ORDERID, o.ORDERDATE, o.ORDERSTATUS, s.SALESTOTAL
        ORDER BY o.ORDERDATE DESC, o.ORDERID DESC
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $recentOrder = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

$upcomingReservation = null;
$sql = "SELECT RESERVEID, RESERVEDATE, TIMESLOT, guestCount
        FROM reservation
        WHERE CUSTID = ?
        ORDER BY RESERVEDATE DESC, TIMESLOT DESC
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $upcomingReservation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

$favouriteMeal = null;
$sql = "SELECT 
            m.MENUID,
            m.MENUNAME,
            m.MENUCATEGORY,
            SUM(om.QUANTITY) AS TOTALQTY
        FROM orders o
        INNER JOIN ordermenu om ON o.ORDERID = om.ORDERID
        INNER JOIN menu m ON om.MENUID = m.MENUID
        WHERE o.CUSTID = ?
        GROUP BY m.MENUID, m.MENUNAME, m.MENUCATEGORY
        ORDER BY TOTALQTY DESC
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $favouriteMeal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

$paymentDetails = null;
$sql = "SELECT 
            s.SALESID,
            s.SALESTOTAL AS LASTPAYMENT,
            s.SALESPAYMETHOD AS PREFERREDMETHOD
        FROM sales s
        INNER JOIN orders o ON s.ORDERID = o.ORDERID
        WHERE o.CUSTID = ?
        ORDER BY s.SALESDATE DESC, s.SALESID DESC
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $paymentDetails = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

respond([
    "success" => true,
    "customer" => $customer,
    "recentOrder" => $recentOrder,
    "upcomingReservation" => $upcomingReservation,
    "favouriteMeal" => $favouriteMeal,
    "paymentDetails" => $paymentDetails
]);
?>
