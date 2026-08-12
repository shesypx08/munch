<?php
session_start();
include 'dbconnect.php';

if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))
{
    echo "Cart empty";
    exit();
}

$orderType = $_POST['order_type']; 

$tableNo = null;
$address = null;

if($orderType == "dinein" || $orderType == "pickup")
{
    $tableNo = $_POST['table_no'];
}
else if($orderType == "delivery")
{
    $address = $_POST['address'];
}

if(!isset($_POST['selected']))
{
    echo "No items selected";
    exit();
}

$selectedItems = $_POST['selected'];

$checkoutItems = [];
$total = 0;

foreach($selectedItems as $menuID)
{
    if(isset($_SESSION['cart'][$menuID]))
    {
        $qty = $_SESSION['cart'][$menuID];

        $query = "SELECT * FROM MENU WHERE MENUID = '$menuID'";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);

        $subtotal = $row['MENUPRICE'] * $qty;
        $total += $subtotal;

        $checkoutItems[] = [
            "menuID" => $menuID,
            "qty" => $qty,
            "subtotal" => $subtotal
        ];
    }
}

// store checkout session
$_SESSION['checkout'] = $checkoutItems;
$_SESSION['checkout_total'] = $total;

$_SESSION['order_type'] = $orderType;
$_SESSION['table_no'] = $tableNo;
$_SESSION['address'] = $address;

// go payment
header("Location: paymentGateway.php");
exit();
?>