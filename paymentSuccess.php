<?php

session_start();
include 'dbconnect.php';

if(!isset($_SESSION['checkout']))
{
    echo "No checkout session!";
    exit();
}

$items = $_SESSION['checkout'];
$total = $_SESSION['checkout_total'];
$orderRemark = $_SESSION['order_remark'];
$orderType = $_SESSION['order_type'];
$tableNo = $_SESSION['table_no'];
$address = $_SESSION['address'];

$custID = $_SESSION['CUSTUSERNAME'];

// auto generate order id
$orderID = "O" . rand(100,999);

// create order
$query = "INSERT INTO ORDERS
(ORDERID, ORDERDATE, ORDERTYPE, TABLENO, ORDERREMARK, ORDERSTATUS, CUSTID, ADDRESS)

VALUES

('$orderID', NOW(), '$orderType', '$tableNo', '$orderRemark', 'Pending', '$custID', '$address')";

$result = mysqli_query($conn,$query);

if($result)
{

    foreach($items as $item)
    {
        $menuID = $item['menuID'];
        $qty = $item['qty'];
        $subtotal = $item['subtotal'];

        $query2 = "INSERT INTO ORDERMENU
        (MENUID, ORDERID, QUANTITY, SUBTOTAL, REQUEST)

        VALUES

        ('$menuID','$orderID','$qty','$subtotal','-')";

        mysqli_query($conn,$query2);
    }

    unset($_SESSION['cart']);
    unset($_SESSION['checkout']);
    unset($_SESSION['checkout_total']);
    unset($_SESSION['order_type']);
    unset($_SESSION['table_no']);
    unset($_SESSION['address']);

    echo "Order placed successfully!";
}
else
{
    echo "Order failed!";
}

?>