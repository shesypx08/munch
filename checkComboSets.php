<?php
include "dbconnect.php";

header("Content-Type: text/plain");

echo "Munch Combo Sets Check\n";
echo "======================\n\n";

$sql = "SELECT MENUCATEGORY, COUNT(*) AS TOTAL_ITEMS
        FROM menu
        WHERE MENUCATEGORY IN ('Nasi', 'Main Dishes', 'Vegetables', 'Side Dishes', 'Drinks', 'Combo Sets')
        GROUP BY MENUCATEGORY
        ORDER BY FIELD(MENUCATEGORY, 'Nasi', 'Main Dishes', 'Vegetables', 'Side Dishes', 'Drinks', 'Combo Sets')";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Menu check is not available right now.";
    exit();
}

while ($row = mysqli_fetch_assoc($result)) {
    echo $row["MENUCATEGORY"] . ": " . $row["TOTAL_ITEMS"] . " item(s)\n";
}

echo "\nCombo Sets list:\n";

$combo = mysqli_query($conn, "SELECT MENUID, MENUNAME, MENUPRICE FROM menu WHERE MENUCATEGORY='Combo Sets' ORDER BY CAST(SUBSTRING(MENUID, 2) AS UNSIGNED)");

if (!$combo) {
    echo "Menu check is not available right now.";
    exit();
}

if (mysqli_num_rows($combo) === 0) {
    echo "No Combo Sets found yet.\n";
} else {
    while ($row = mysqli_fetch_assoc($combo)) {
        echo $row["MENUID"] . " - " . $row["MENUNAME"] . " - RM" . number_format($row["MENUPRICE"], 2) . "\n";
    }
}
?>
