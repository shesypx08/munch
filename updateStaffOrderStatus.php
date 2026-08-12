<?php
session_start();
include "dbconnect.php";

header("Content-Type: application/json");

if (!isset($_SESSION["STAFFID"]) || trim($_SESSION["STAFFID"]) === "") {
    echo json_encode([
        "success" => false,
        "message" => "Please login as staff first."
    ]);
    exit();
}

$orderID = isset($_POST["ORDERID"]) ? trim($_POST["ORDERID"]) : "";
$status = isset($_POST["STATUS"]) ? trim($_POST["STATUS"]) : "";

$allowedStatus = ["Pending", "Preparing", "Ready", "Completed", "Served", "Cancelled"];

if ($orderID === "" || $status === "") {
    echo json_encode([
        "success" => false,
        "message" => "Order ID and status are required."
    ]);
    exit();
}

if (!in_array($status, $allowedStatus)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid order status."
    ]);
    exit();
}

$sql = "UPDATE orders
        SET ORDERSTATUS = ?
        WHERE ORDERID = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Order update is not available right now. Please try again later."
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "ss", $status, $orderID);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => true,
        "message" => "Order status updated.",
        "ORDERID" => $orderID,
        "STATUS" => $status
    ]);
    exit();
}

echo json_encode([
    "success" => false,
    "message" => "Order update failed. Please try again."
]);
?>
