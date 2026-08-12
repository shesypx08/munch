<?php
header('Content-Type: application/json');

include 'dbconnect.php';

$sql = "SELECT 
            MENUNAME, 
            MENUCATEGORY, 
            MENUPRICE, 
            MENUDESC, 
            MENUIMAGE 
        FROM MENU
        WHERE MENUSTATUS = 'Available'
        ORDER BY MENUCATEGORY, MENUNAME";

$result = mysqli_query($conn, $sql);

$menuItems = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menuItems[] = $row;
    }
}

echo json_encode($menuItems);

mysqli_close($conn);
?>