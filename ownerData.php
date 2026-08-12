<?php
session_start();
include "dbconnect.php";

header("Content-Type: application/json");

function respond($data) {
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION["OWNERID"]) || trim($_SESSION["OWNERID"]) === "") {
    respond(["success" => false, "message" => "Please login as owner first."]);
}

function scalarQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) return 0;

    $row = mysqli_fetch_row($result);
    return $row ? $row[0] : 0;
}

function fetchRows($conn, $sql) {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        respond(["success" => false, "message" => "Owner information is not available right now. Please try again later."]);
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

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

$section = isset($_GET["section"]) ? trim($_GET["section"]) : "dashboard";

if ($section === "dashboard") {
    $salesTrend = fetchRows($conn, "
        SELECT DATE_FORMAT(SALESDATE, '%d/%m') AS label,
               COALESCE(SUM(SALESTOTAL), 0) AS total
        FROM sales
        GROUP BY SALESDATE
        ORDER BY SALESDATE DESC
        LIMIT 7
    ");

    $salesTrend = array_reverse($salesTrend);

    if (count($salesTrend) === 0) {
        $salesTrend = [
            ["label" => "No Data", "total" => 0]
        ];
    }

    $categoryDemand = fetchRows($conn, "
        SELECT m.MENUCATEGORY AS category,
               COALESCE(SUM(om.QUANTITY), 0) AS qty
        FROM ordermenu om
        INNER JOIN menu m ON om.MENUID = m.MENUID
        GROUP BY m.MENUCATEGORY
        ORDER BY qty DESC
        LIMIT 6
    ");

    respond([
        "success" => true,
        "ownerName" => $_SESSION["OWNERNAME"] ?? "Owner",
        "todaySales" => (float)scalarQuery($conn, "SELECT COALESCE(SUM(SALESTOTAL), 0) FROM sales WHERE SALESDATE = CURDATE()"),
        "todayOrders" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE ORDERDATE = CURDATE()"),
        "todayReservations" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM reservation WHERE RESERVEDATE = CURDATE()"),
        "cateringRequests" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM reservation WHERE LOWER(`SESSION`) = 'catering' OR LOWER(SEATINGPREF) LIKE '%event%'"),
        "staffCount" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM staff"),
        "menuCount" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM menu"),
        "salesTrend" => $salesTrend,
        "categoryDemand" => $categoryDemand
    ]);
}

if ($section === "menu") {
    $items = fetchRows($conn, "
        SELECT MENUID, MENUNAME, MENUCATEGORY, MENUPRICE, MENUDESC
        FROM menu
        ORDER BY CAST(SUBSTRING(MENUID, 2) AS UNSIGNED) ASC, MENUID ASC
    ");

    $counts = [
        "nasi" => 0,
        "main" => 0,
        "vegetables" => 0,
        "side" => 0,
        "drinks" => 0,
        "combo" => 0
    ];

    foreach ($items as &$item) {
        $cat = strtolower(trim($item["MENUCATEGORY"]));

        if ($cat === "nasi" || strpos($cat, "rice") !== false) {
            $counts["nasi"]++;
        } elseif ($cat === "main" || $cat === "main dishes" || strpos($cat, "main") !== false) {
            $counts["main"]++;
        } elseif ($cat === "vegetables" || strpos($cat, "vegetable") !== false) {
            $counts["vegetables"]++;
        } elseif ($cat === "side" || $cat === "side dishes" || strpos($cat, "side") !== false) {
            $counts["side"]++;
        } elseif ($cat === "drinks" || $cat === "drink" || strpos($cat, "drink") !== false) {
            $counts["drinks"]++;
        } elseif ($cat === "combo sets" || strpos($cat, "combo") !== false) {
            $counts["combo"]++;
        }

        $item["IMAGE"] = menuImagePath($item["MENUID"]);
    }

    respond([
        "success" => true,
        "items" => $items,
        "categoryCounts" => $counts
    ]);
}

if ($section === "employees") {
    $staff = fetchRows($conn, "
        SELECT 
            s.STAFFID,
            s.STAFFNAME,
            s.STAFFPHONENO,
            s.STAFFROLE,
            o.EQUITYTYPE,
            o.CONTRACTDURATION,
            op.WORKSTATION,
            op.SKILLEVEL
        FROM staff s
        LEFT JOIN owner o ON s.STAFFID = o.STAFFID
        LEFT JOIN operationalstaff op ON s.STAFFID = op.STAFFID
        ORDER BY s.STAFFID
    ");

    $roleCounts = [
        "owner" => 0,
        "operational" => 0
    ];

    foreach ($staff as $row) {
        $role = strtoupper($row["STAFFROLE"]);

        if ($role === "OWNER" || $row["EQUITYTYPE"] !== null) {
            $roleCounts["owner"]++;
        } else {
            $roleCounts["operational"]++;
        }
    }

    respond([
        "success" => true,
        "staff" => $staff,
        "roleCounts" => $roleCounts,
        "ownerCount" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM owner"),
        "operationalCount" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM operationalstaff"),
        "staffWithSales" => (int)scalarQuery($conn, "SELECT COUNT(DISTINCT STAFFID) FROM sales")
    ]);
}

if ($section === "reports") {
    $itemRanking = fetchRows($conn, "
        SELECT 
            m.MENUID,
            m.MENUNAME,
            m.MENUCATEGORY,
            COALESCE(SUM(om.QUANTITY), 0) AS qty,
            COALESCE(SUM(om.SUBTOTAL), 0) AS revenue
        FROM ordermenu om
        INNER JOIN menu m ON om.MENUID = m.MENUID
        GROUP BY m.MENUID, m.MENUNAME, m.MENUCATEGORY
        ORDER BY qty DESC, revenue DESC
        LIMIT 10
    ");

    $paymentMix = fetchRows($conn, "
        SELECT COALESCE(SALESPAYMETHOD, 'Unknown') AS method,
               COALESCE(SUM(SALESTOTAL), 0) AS total
        FROM sales
        GROUP BY SALESPAYMETHOD
        ORDER BY total DESC
    ");

    respond([
        "success" => true,
        "sales" => [
            "daily" => (float)scalarQuery($conn, "SELECT COALESCE(SUM(SALESTOTAL), 0) FROM sales WHERE SALESDATE = CURDATE()"),
            "weekly" => (float)scalarQuery($conn, "SELECT COALESCE(SUM(SALESTOTAL), 0) FROM sales WHERE YEARWEEK(SALESDATE, 1) = YEARWEEK(CURDATE(), 1)"),
            "monthly" => (float)scalarQuery($conn, "SELECT COALESCE(SUM(SALESTOTAL), 0) FROM sales WHERE YEAR(SALESDATE) = YEAR(CURDATE()) AND MONTH(SALESDATE) = MONTH(CURDATE())"),
            "yearly" => (float)scalarQuery($conn, "SELECT COALESCE(SUM(SALESTOTAL), 0) FROM sales WHERE YEAR(SALESDATE) = YEAR(CURDATE())")
        ],
        "counts" => [
            "dailyOrders" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE ORDERDATE = CURDATE()"),
            "weeklyOrders" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE YEARWEEK(ORDERDATE, 1) = YEARWEEK(CURDATE(), 1)"),
            "monthlyOrders" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE YEAR(ORDERDATE) = YEAR(CURDATE()) AND MONTH(ORDERDATE) = MONTH(CURDATE())"),
            "yearlyOrders" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM orders WHERE YEAR(ORDERDATE) = YEAR(CURDATE())")
        ],
        "itemRanking" => $itemRanking,
        "paymentMix" => $paymentMix
    ]);
}

if ($section === "bookings") {
    $normal = fetchRows($conn, "
        SELECT RESERVEID, FULLNAME, RESERVEDATE, TIMESLOT, guestCount, SEATINGPREF, DEPOSIT, STATUS, PAYMENTMETHOD
        FROM reservation
        WHERE NOT (LOWER(`SESSION`) = 'catering' OR LOWER(SEATINGPREF) LIKE '%event%')
        ORDER BY RESERVEDATE DESC, TIMESLOT DESC
    ");

    $catering = fetchRows($conn, "
        SELECT RESERVEID, FULLNAME, RESERVEDATE, TIMESLOT, guestCount, `SESSION`, OCCASION, SPECIALREQ, DEPOSIT, STATUS, PAYMENTMETHOD
        FROM reservation
        WHERE LOWER(`SESSION`) = 'catering' OR LOWER(SEATINGPREF) LIKE '%event%'
        ORDER BY RESERVEDATE DESC, TIMESLOT DESC
    ");

    foreach ($catering as &$row) {
        $estimatedTotal = ((int)$row["guestCount"]) * 10;
        $row["balanceDue"] = max(0, $estimatedTotal - (float)$row["DEPOSIT"]);
    }

    $reservationPax = 0;
    foreach ($normal as $row) $reservationPax += (int)$row["guestCount"];

    $cateringPax = 0;
    foreach ($catering as $row) $cateringPax += (int)$row["guestCount"];

    $dateDemand = fetchRows($conn, "
        SELECT RESERVEDATE,
               COUNT(*) AS bookingCount,
               COALESCE(AVG(guestCount), 0) AS avgPax,
               COALESCE(SUM(guestCount), 0) AS totalPax
        FROM reservation
        GROUP BY RESERVEDATE
        ORDER BY bookingCount DESC, totalPax DESC
        LIMIT 7
    ");

    respond([
        "success" => true,
        "normalReservations" => $normal,
        "cateringEvents" => $catering,
        "dateDemand" => $dateDemand,
        "totalReservationPax" => $reservationPax,
        "totalCateringPax" => $cateringPax,
        "depositCollected" => (float)scalarQuery($conn, "SELECT COALESCE(SUM(DEPOSIT), 0) FROM reservation"),
        "pendingBalance" => (int)scalarQuery($conn, "SELECT COUNT(*) FROM reservation WHERE LOWER(STATUS) LIKE '%pending%'")
    ]);
}

respond(["success" => false, "message" => "Invalid owner section."]);
?>
