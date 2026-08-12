<?php
include "dbconnect.php";

header("Content-Type: application/json");

function menuImagePath($menuID) {
    $menuID = trim((string)$menuID);
    $extensions = ["jpg", "jpeg", "png", "webp", "JPG", "JPEG", "PNG", "WEBP"];

    foreach ($extensions as $ext) {
        $imagePath = "img/" . $menuID . "." . $ext;

        if (file_exists(__DIR__ . "/" . $imagePath)) {
            return $imagePath;
        }
    }

    return "img/restaurant-1.png";
}

$sql = "SELECT MENUID, MENUNAME, MENUPRICE, MENUCATEGORY, MENUDESC
        FROM menu
        ORDER BY CASE
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('nasi') THEN 1
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('main dishes', 'main dish', 'main', 'western') THEN 2
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('vegetables', 'vegetable', 'veggies') THEN 3
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('side dishes', 'side dish', 'side', 'addons') THEN 4
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('drinks', 'drink') THEN 5
            WHEN LOWER(TRIM(MENUCATEGORY)) IN ('combo sets', 'combo set', 'combo') THEN 6
            ELSE 99
        END,
        CAST(SUBSTRING(MENUID, 2) AS UNSIGNED) ASC,
        MENUID ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Menu is not available right now. Please try again later."
    ]);
    exit();
}

$items = [];

while ($row = mysqli_fetch_assoc($result)) {
    $row["IMAGE"] = menuImagePath($row["MENUID"]);
    $items[] = $row;
}

echo json_encode([
    "success" => true,
    "items" => $items
]);
?>
