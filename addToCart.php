<?php
session_start();
include "dbconnect.php";

header("Content-Type: application/json");

if (!isset($_SESSION["CUSTUSERNAME"]) || trim($_SESSION["CUSTUSERNAME"]) === "") {
    echo json_encode([
        "success" => false,
        "requiresLogin" => true,
        "message" => "Please login as a customer before adding items to cart."
    ]);
    exit();
}

/*
    Robust add-to-cart handler.
    Accepts MENUID plus common alternatives so older buttons/scripts still work:
    MENUID, menuID, menuId, menuid, MENU_ID, id
*/

$menuID = "";

$possibleKeys = ["MENUID", "menuID", "menuId", "menuid", "MENU_ID", "id"];

foreach ($possibleKeys as $key) {
    if (isset($_POST[$key]) && trim($_POST[$key]) !== "") {
        $menuID = trim($_POST[$key]);
        break;
    }
}

$quantity = 1;

if (isset($_POST["QUANTITY"])) {
    $quantity = (int)$_POST["QUANTITY"];
} elseif (isset($_POST["quantity"])) {
    $quantity = (int)$_POST["quantity"];
} elseif (isset($_POST["qty"])) {
    $quantity = (int)$_POST["qty"];
}

$comboDetails = "";

if (isset($_POST["COMBO_DETAILS"])) {
    $comboDetails = trim($_POST["COMBO_DETAILS"]);
} elseif (isset($_POST["request"])) {
    $comboDetails = trim($_POST["request"]);
} elseif (isset($_POST["REQUEST"])) {
    $comboDetails = trim($_POST["REQUEST"]);
}

$customComboIDs = ["M058", "M059", "M060"];

if ($menuID === "") {
    echo json_encode([
        "success" => false,
        "message" => "Menu ID is missing. The clicked add-to-cart button does not have data-menu-id.",
        "receivedPostKeys" => array_keys($_POST)
    ]);
    exit();
}

if ($quantity < 1) {
    $quantity = 1;
}

$stmt = mysqli_prepare($conn, "SELECT MENUID FROM menu WHERE MENUID = ?");

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Cart is not available right now. Please try again later."]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $menuID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(["success" => false, "message" => "Menu item not found: " . $menuID]);
    exit();
}

if (in_array($menuID, $customComboIDs, true) && $comboDetails === "") {
    echo json_encode(["success" => false, "message" => "Please complete the combo details before adding to cart."]);
    exit();
}

if (strlen($comboDetails) > 255) {
    $comboDetails = substr($comboDetails, 0, 252) . "...";
}

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if (!isset($_SESSION["cart_requests"]) || !is_array($_SESSION["cart_requests"])) {
    $_SESSION["cart_requests"] = [];
}

if (!isset($_SESSION["cart"][$menuID])) {
    $_SESSION["cart"][$menuID] = 0;
}

$_SESSION["cart"][$menuID] += $quantity;

if ($comboDetails !== "") {
    $_SESSION["cart_requests"][$menuID] = $comboDetails;
}

echo json_encode([
    "success" => true,
    "message" => "Item added to cart.",
    "cartCount" => array_sum($_SESSION["cart"]),
    "menuID" => $menuID
]);
?>
