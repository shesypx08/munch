<?php
session_start();
include "dbconnect.php";

header("Content-Type: application/json");

$cart = isset($_SESSION["cart"]) && is_array($_SESSION["cart"]) ? $_SESSION["cart"] : [];
$cartRequests = isset($_SESSION["cart_requests"]) && is_array($_SESSION["cart_requests"]) ? $_SESSION["cart_requests"] : [];

if (empty($cart)) {
    echo json_encode([
        "success" => true,
        "items" => [],
        "subtotal" => 0,
        "tax" => 0,
        "total" => 0,
        "cartCount" => 0
    ]);
    exit();
}

$items = [];
$subtotal = 0;

foreach ($cart as $menuID => $qty) {
    $stmt = mysqli_prepare($conn, "SELECT MENUID, MENUNAME, MENUPRICE FROM menu WHERE MENUID = ?");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Cart is not available right now. Please try again later."]);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "s", $menuID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $qty = (int)$qty;
        $price = (float)$row["MENUPRICE"];
        $lineSubtotal = $price * $qty;
        $subtotal += $lineSubtotal;

        $items[] = [
            "MENUID" => $row["MENUID"],
            "MENUNAME" => $row["MENUNAME"],
            "MENUPRICE" => $price,
            "QUANTITY" => $qty,
            "SUBTOTAL" => $lineSubtotal,
            "REQUEST" => $cartRequests[$menuID] ?? ""
        ];
    }
}

$tax = $subtotal * 0.06;
$total = $subtotal + $tax;

echo json_encode([
    "success" => true,
    "items" => $items,
    "subtotal" => $subtotal,
    "tax" => $tax,
    "total" => $total,
    "cartCount" => array_sum($cart)
]);
?>
