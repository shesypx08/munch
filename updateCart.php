<?php
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if (!isset($_SESSION["cart_requests"]) || !is_array($_SESSION["cart_requests"])) {
    $_SESSION["cart_requests"] = [];
}

$action = isset($_POST["ACTION"]) ? trim($_POST["ACTION"]) : "";
$menuID = isset($_POST["MENUID"]) ? trim($_POST["MENUID"]) : "";

if ($action === "clear") {
    $_SESSION["cart"] = [];
    $_SESSION["cart_requests"] = [];
    echo json_encode(["success" => true, "cartCount" => 0]);
    exit();
}

if ($menuID === "" || !isset($_SESSION["cart"][$menuID])) {
    echo json_encode(["success" => false, "message" => "Cart item not found."]);
    exit();
}

if ($action === "plus") {
    $_SESSION["cart"][$menuID]++;
} elseif ($action === "minus") {
    $_SESSION["cart"][$menuID]--;

    if ($_SESSION["cart"][$menuID] <= 0) {
        unset($_SESSION["cart"][$menuID]);
        unset($_SESSION["cart_requests"][$menuID]);
    }
} elseif ($action === "remove") {
    unset($_SESSION["cart"][$menuID]);
    unset($_SESSION["cart_requests"][$menuID]);
}

echo json_encode([
    "success" => true,
    "cartCount" => array_sum($_SESSION["cart"])
]);
?>