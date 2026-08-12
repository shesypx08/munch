<?php
include "dbconnect.php";

header("Content-Type: text/plain");

$tables = ["staff", "owner", "menu", "orders", "ordermenu", "sales", "reservation"];

foreach ($tables as $table) {
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$table`");

    if (!$result) {
        echo "$table: ERROR - " . mysqli_error($conn) . PHP_EOL;
        continue;
    }

    $row = mysqli_fetch_assoc($result);
    echo "$table: " . $row["total"] . " record(s)" . PHP_EOL;
}

echo PHP_EOL;
echo "Owner login test accounts from your uploaded SQL:" . PHP_EOL;
echo "S001 / staff123" . PHP_EOL;
echo "S003 / staff321" . PHP_EOL;
?>