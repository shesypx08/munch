<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";
require_once __DIR__ . "/email_helper.php";

munch_ensure_enhancements($conn);
header("Content-Type: application/json");

if (!isset($_SESSION["CUSTUSERNAME"]) || trim($_SESSION["CUSTUSERNAME"]) === "") {
    echo json_encode(["success" => false, "message" => "Please login first."]);
    exit();
}

if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    echo json_encode(["success" => false, "message" => "Your cart is empty."]);
    exit();
}

$custID = $_SESSION["CUSTUSERNAME"];
$orderType = isset($_POST["orderType"]) ? trim($_POST["orderType"]) : "Dine In";
$paymentMethod = isset($_POST["paymentMethod"]) ? trim($_POST["paymentMethod"]) : "Cash at Counter";
$specialRequest = isset($_POST["specialRequest"]) ? trim($_POST["specialRequest"]) : "";
$deliveryAddress = isset($_POST["deliveryAddress"]) ? trim($_POST["deliveryAddress"]) : "";

$cartRequests = isset($_SESSION["cart_requests"]) && is_array($_SESSION["cart_requests"]) ? $_SESSION["cart_requests"] : [];

if (strtolower($orderType) === "delivery" && $deliveryAddress === "") {
    echo json_encode(["success" => false, "message" => "Please enter delivery address."]);
    exit();
}

$customerStmt = mysqli_prepare($conn, "SELECT CUSTEMAIL, EMAILVERIFIED FROM customer WHERE CUSTUSERNAME=? LIMIT 1");
mysqli_stmt_bind_param($customerStmt, 's', $custID);
mysqli_stmt_execute($customerStmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($customerStmt));

if ($customer && !empty($customer['CUSTEMAIL']) && (int)$customer['EMAILVERIFIED'] !== 1) {
    echo json_encode(["success" => false, "message" => "Please verify your email before placing an order."]);
    exit();
}

$orderID = "O" . random_int(10000, 99999);
$orderStatus = "Pending";
$tableNo = null;
$orderRemark = $specialRequest !== "" ? substr($specialRequest, 0, 255) : null;
$address = strtolower($orderType) === "delivery" ? substr($deliveryAddress, 0, 255) : null;

mysqli_begin_transaction($conn);

try {
    $sqlOrder = "INSERT INTO orders (ORDERID, ORDERDATE, ORDERTYPE, TABLENO, ORDERREMARK, ORDERSTATUS, CUSTID, Address)
                 VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)";

    $stmtOrder = mysqli_prepare($conn, $sqlOrder);

    if (!$stmtOrder) {
        throw new Exception("We could not prepare your order right now. Please try again.");
    }

    mysqli_stmt_bind_param($stmtOrder, "sssssss", $orderID, $orderType, $tableNo, $orderRemark, $orderStatus, $custID, $address);

    if (!mysqli_stmt_execute($stmtOrder)) {
        throw new Exception("We could not save your order right now. Please try again.");
    }

    $subtotal = 0;

    foreach ($_SESSION["cart"] as $menuID => $quantity) {
        $priceStmt = mysqli_prepare($conn, "SELECT MENUPRICE FROM menu WHERE MENUID = ?");
        mysqli_stmt_bind_param($priceStmt, "s", $menuID);
        mysqli_stmt_execute($priceStmt);
        $priceResult = mysqli_stmt_get_result($priceStmt);
        $menu = mysqli_fetch_assoc($priceResult);

        if (!$menu) {
            throw new Exception("Menu item not found: " . $menuID);
        }

        $quantity = (int)$quantity;
        $price = (float)$menu["MENUPRICE"];
        $lineSubtotal = $price * $quantity;
        $subtotal += $lineSubtotal;

        $request = isset($cartRequests[$menuID]) ? trim($cartRequests[$menuID]) : null;

        if ($request !== null && strlen($request) > 255) {
            $request = substr($request, 0, 252) . "...";
        }

        $itemSql = "INSERT INTO ordermenu (ORDERID, MENUID, QUANTITY, SUBTOTAL, REQUEST)
                    VALUES (?, ?, ?, ?, ?)";

        $itemStmt = mysqli_prepare($conn, $itemSql);

        if (!$itemStmt) {
            throw new Exception("We could not prepare one of your order items. Please try again.");
        }

        mysqli_stmt_bind_param($itemStmt, "ssids", $orderID, $menuID, $quantity, $lineSubtotal, $request);

        if (!mysqli_stmt_execute($itemStmt)) {
            throw new Exception("We could not save one of your order items. Please try again.");
        }
    }

    $deliveryCharge = strtolower($orderType) === "delivery" ? 6.00 : 0.00;
    $tax = ($subtotal + $deliveryCharge) * 0.06;
    $total = $subtotal + $deliveryCharge + $tax;
    $payerEmail = $customer['CUSTEMAIL'] ?? ($_SESSION['CUSTEMAIL'] ?? '');

    munch_create_or_update_payment($conn, 'order', $orderID, $custID, $payerEmail, $total, $paymentMethod);

    mysqli_commit($conn);

    $_SESSION["cart"] = [];
    $_SESSION["cart_requests"] = [];

    echo json_encode([
        "success" => true,
        "message" => "Order created. Please complete payment.",
        "orderID" => $orderID,
        "total" => $total,
        "redirect" => "payment-gateway.php?type=order&ref=" . urlencode($orderID)
    ]);
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
    exit();
}
?>
