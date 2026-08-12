<?php
session_start();
include 'dbconnect.php';

$total = 0;
$cartData = [];

if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))
{
    echo json_encode([
        "status" => "empty",
        "message" => "Cart is empty"
    ]);
    exit();
}

foreach($_SESSION['cart'] as $menuID => $qty)
{
    $query = "SELECT * FROM MENU WHERE MENUID = '$menuID'";
    $result = mysqli_query($conn, $query);

    if($row = mysqli_fetch_assoc($result))
    {
        $subtotal = $row['MENUPRICE'] * $qty;
        $total += $subtotal;

        $cartData[] = [
            "menuID" => $menuID,
            "name" => $row['MENUNAME'],
            "price" => $row['MENUPRICE'],
            "quantity" => $qty,
            "subtotal" => $subtotal
        ];
    }
}

echo json_encode([
    "status" => "success",
    "cart" => $cartData,
    "total" => $total
]);
?>