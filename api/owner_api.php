<?php
// =====================================================
// MUNCH OWNER API
// =====================================================

session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function get_connection() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'munchdb';

    try {
        $conn = new mysqli($host, $username, $password, $database);
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'message' => 'Owner tools are not available right now. Please try again later.',
            'error' => $e->getMessage()
        ], 500);
    }
}

function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function get_input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) return array_merge($_POST, $json);
    return $_POST;
}

function require_owner($conn) {
    $ownerSessionId = $_SESSION['OWNERID'] ?? $_SESSION['owner_id'] ?? '';
    if (trim($ownerSessionId) === '') {
        json_response(['success' => false, 'message' => 'Owner login required.'], 401);
    }

    $stmt = $conn->prepare("SELECT s.STAFFID, s.STAFFNAME, s.STAFFROLE, o.EQUITYTYPE, o.CONTRACTDURATION
                            FROM staff s
                            INNER JOIN owner o ON o.STAFFID = s.STAFFID
                            WHERE s.STAFFID = ? AND UPPER(s.STAFFROLE) = 'OWNER'");
    $stmt->bind_param('s', $ownerSessionId);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();

    if (!$owner) {
        session_destroy();
        json_response(['success' => false, 'message' => 'Owner session is no longer valid.'], 401);
    }

    return $owner;
}

function password_is_valid($plain, $stored) {
    if ($plain === $stored) return true;
    $info = password_get_info($stored);
    if (!empty($info['algo']) && password_verify($plain, $stored)) return true;
    return false;
}

function fetch_all_assoc($stmt) {
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_one_assoc($stmt) {
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: [];
}

function period_bounds($latestDate) {
    $latest = new DateTime($latestDate ?: date('Y-m-d'));

    $dayStart = $latest->format('Y-m-d');
    $dayEnd = $latest->format('Y-m-d');

    $weekStart = clone $latest;
    $weekStart->setISODate((int)$latest->format('o'), (int)$latest->format('W'));
    $weekEnd = clone $weekStart;
    $weekEnd->modify('+6 days');

    $monthStart = new DateTime($latest->format('Y-m-01'));
    $monthEnd = clone $monthStart;
    $monthEnd->modify('last day of this month');

    $yearStart = new DateTime($latest->format('Y-01-01'));
    $yearEnd = new DateTime($latest->format('Y-12-31'));

    return [
        'daily' => [$dayStart, $dayEnd, 'Daily Sales', 'Latest recorded sales day: ' . $latest->format('d M Y')],
        'weekly' => [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'), 'Weekly Sales', $weekStart->format('d M') . ' - ' . $weekEnd->format('d M Y')],
        'monthly' => [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'), 'Monthly Sales', $latest->format('F Y')],
        'yearly' => [$yearStart->format('Y-m-d'), $yearEnd->format('Y-m-d'), 'Yearly Sales', $latest->format('Y')]
    ];
}

function sales_summary($conn, $start, $end) {
    $stmt = $conn->prepare("SELECT
            COALESCE(SUM(SALESTOTAL), 0) AS total_sales,
            COUNT(*) AS sales_count,
            COUNT(DISTINCT ORDERID) AS order_count,
            COALESCE(AVG(SALESTOTAL), 0) AS average_order_value
        FROM sales
        WHERE SALESDATE BETWEEN ? AND ?");
    $stmt->bind_param('ss', $start, $end);
    return fetch_one_assoc($stmt);
}

function best_sales_day($conn, $start, $end) {
    $stmt = $conn->prepare("SELECT DAYNAME(SALESDATE) AS day_name, COUNT(*) AS sales_count, COALESCE(SUM(SALESTOTAL),0) AS total_sales
        FROM sales
        WHERE SALESDATE BETWEEN ? AND ?
        GROUP BY DAYNAME(SALESDATE), DAYOFWEEK(SALESDATE)
        ORDER BY total_sales DESC, sales_count DESC
        LIMIT 1");
    $stmt->bind_param('ss', $start, $end);
    return fetch_one_assoc($stmt);
}

function item_ranking($conn, $limit = 20, $direction = 'DESC') {
    $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
    $sql = "SELECT
            m.MENUID,
            m.MENUNAME,
            m.MENUCATEGORY,
            m.MENUPRICE,
            COALESCE(SUM(CASE WHEN s.SALESID IS NOT NULL THEN om.QUANTITY ELSE 0 END), 0) AS quantity_sold,
            COALESCE(SUM(CASE WHEN s.SALESID IS NOT NULL THEN om.SUBTOTAL ELSE 0 END), 0) AS item_revenue
        FROM menu m
        LEFT JOIN ordermenu om ON om.MENUID = m.MENUID
        LEFT JOIN sales s ON s.ORDERID = om.ORDERID
        GROUP BY m.MENUID, m.MENUNAME, m.MENUCATEGORY, m.MENUPRICE
        ORDER BY quantity_sold $direction, item_revenue $direction, m.MENUNAME ASC
        LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limit);
    return fetch_all_assoc($stmt);
}

function category_performance($conn) {
    $stmt = $conn->prepare("SELECT
            m.MENUCATEGORY,
            COALESCE(SUM(CASE WHEN s.SALESID IS NOT NULL THEN om.QUANTITY ELSE 0 END), 0) AS quantity_sold,
            COALESCE(SUM(CASE WHEN s.SALESID IS NOT NULL THEN om.SUBTOTAL ELSE 0 END), 0) AS revenue
        FROM menu m
        LEFT JOIN ordermenu om ON om.MENUID = m.MENUID
        LEFT JOIN sales s ON s.ORDERID = om.ORDERID
        GROUP BY m.MENUCATEGORY
        ORDER BY quantity_sold DESC, revenue DESC");
    return fetch_all_assoc($stmt);
}

function booking_condition_sql($type) {
    if ($type === 'catering') {
        return "(LOWER(`SESSION`) = 'catering' OR LOWER(`SEATINGPREF`) LIKE '%catering%')";
    }
    return "NOT (LOWER(`SESSION`) = 'catering' OR LOWER(`SEATINGPREF`) LIKE '%catering%')";
}

function booking_summary($conn, $type = 'all') {
    $where = '1=1';
    if ($type === 'normal') $where = booking_condition_sql('normal');
    if ($type === 'catering') $where = booking_condition_sql('catering');

    $sql = "SELECT
            COUNT(*) AS booking_count,
            COALESCE(SUM(guestCount), 0) AS total_pax,
            COALESCE(AVG(guestCount), 0) AS average_pax,
            COALESCE(SUM(DEPOSIT), 0) AS total_deposit,
            SUM(CASE WHEN LOWER(`STATUS`) = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
            SUM(CASE WHEN RESERVEDATE >= CURDATE() THEN 1 ELSE 0 END) AS upcoming_count
        FROM reservation
        WHERE $where";
    $stmt = $conn->prepare($sql);
    return fetch_one_assoc($stmt);
}


function handle_owner_signup($conn) {
    json_response(['success' => false, 'message' => 'Owner sign up has been disabled. Owner accounts are prepared by the system administrator.'], 403);
}

function handle_login($conn) {
    $input = get_input();
    $staffId = trim($input['staffId'] ?? $input['ownerLoginId'] ?? '');
    $password = $input['password'] ?? $input['ownerLoginPassword'] ?? '';

    if ($staffId === '' || $password === '') {
        json_response(['success' => false, 'message' => 'Please enter owner Staff ID and password.'], 422);
    }

    $stmt = $conn->prepare("SELECT s.STAFFID, s.STAFFNAME, s.STAFFPASS, s.STAFFROLE, o.EQUITYTYPE, o.CONTRACTDURATION
                            FROM staff s
                            INNER JOIN owner o ON o.STAFFID = s.STAFFID
                            WHERE s.STAFFID = ? AND UPPER(s.STAFFROLE) = 'OWNER'");
    $stmt->bind_param('s', $staffId);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();

    if (!$owner || !password_is_valid($password, $owner['STAFFPASS'])) {
        json_response(['success' => false, 'message' => 'Invalid owner login. Try owner STAFFID such as S001 with the matching staff password.'], 401);
    }

    $_SESSION['owner_id'] = $owner['STAFFID'];
    $_SESSION['owner_name'] = $owner['STAFFNAME'];
    $_SESSION['owner_role'] = 'OWNER';
    $_SESSION['OWNERID'] = $owner['STAFFID'];
    $_SESSION['OWNERNAME'] = $owner['STAFFNAME'];

    unset($owner['STAFFPASS']);
    json_response(['success' => true, 'message' => 'Owner login successful.', 'owner' => $owner]);
}

function handle_session($conn) {
    $owner = require_owner($conn);
    json_response(['success' => true, 'owner' => $owner]);
}

function handle_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    json_response(['success' => true, 'message' => 'Logged out successfully.']);
}

function handle_menu_list($conn) {
    require_owner($conn);
    $stmt = $conn->prepare("SELECT
            m.MENUID,
            m.MENUNAME,
            m.MENUPRICE,
            m.MENUCATEGORY,
            m.MENUDESC,
            COALESCE(SUM(CASE WHEN s.SALESID IS NOT NULL THEN om.QUANTITY ELSE 0 END), 0) AS SOLDQTY,
            COALESCE(SUM(CASE WHEN s.SALESID IS NOT NULL THEN om.SUBTOTAL ELSE 0 END), 0) AS REVENUE
        FROM menu m
        LEFT JOIN ordermenu om ON om.MENUID = m.MENUID
        LEFT JOIN sales s ON s.ORDERID = om.ORDERID
        GROUP BY m.MENUID, m.MENUNAME, m.MENUPRICE, m.MENUCATEGORY, m.MENUDESC
        ORDER BY CAST(SUBSTRING(m.MENUID, 2) AS UNSIGNED), m.MENUID");
    $items = fetch_all_assoc($stmt);

    $catStmt = $conn->prepare("SELECT MENUCATEGORY, COUNT(*) AS total FROM menu GROUP BY MENUCATEGORY ORDER BY MENUCATEGORY");
    $categories = fetch_all_assoc($catStmt);

    json_response(['success' => true, 'items' => $items, 'categories' => $categories]);
}

function generate_menu_id($conn) {
    $stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(MENUID, 2) AS UNSIGNED)), 0) + 1 AS next_no FROM menu WHERE MENUID REGEXP '^M[0-9]+$'");
    $row = fetch_one_assoc($stmt);
    return 'M' . str_pad((string)$row['next_no'], 3, '0', STR_PAD_LEFT);
}

function handle_menu_save($conn) {
    require_owner($conn);
    $input = get_input();

    $menuId = trim($input['menuId'] ?? '');
    $name = trim($input['menuName'] ?? '');
    $category = trim($input['menuCategory'] ?? '');
    $price = (float)($input['menuPrice'] ?? 0);
    $description = trim($input['menuDesc'] ?? '');

    if ($name === '' || $category === '' || $price <= 0) {
        json_response(['success' => false, 'message' => 'Menu name, category, and price are required.'], 422);
    }
    if ($description === '') $description = 'No description provided yet.';

    if ($menuId === '') {
        $menuId = generate_menu_id($conn);
        $stmt = $conn->prepare("INSERT INTO menu (MENUID, MENUNAME, MENUPRICE, MENUCATEGORY, MENUDESC) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdss', $menuId, $name, $price, $category, $description);
        $stmt->execute();
        json_response(['success' => true, 'message' => "Menu item $menuId has been added successfully.", 'menuId' => $menuId]);
    }

    $stmt = $conn->prepare("UPDATE menu SET MENUNAME = ?, MENUPRICE = ?, MENUCATEGORY = ?, MENUDESC = ? WHERE MENUID = ?");
    $stmt->bind_param('sdsss', $name, $price, $category, $description, $menuId);
    $stmt->execute();

    if ($stmt->affected_rows < 0) {
        json_response(['success' => false, 'message' => 'Menu update failed.'], 500);
    }

    json_response(['success' => true, 'message' => "Menu item $menuId has been updated successfully.", 'menuId' => $menuId]);
}

function handle_menu_delete($conn) {
    require_owner($conn);
    $input = get_input();
    $menuId = trim($input['menuId'] ?? '');
    if ($menuId === '') json_response(['success' => false, 'message' => 'Menu ID is required.'], 422);

    $stmt = $conn->prepare("SELECT
            (SELECT COUNT(*) FROM ordermenu WHERE MENUID = ?) AS order_count,
            (SELECT COUNT(*) FROM cartmenu WHERE MENUID = ?) AS cart_count");
    $stmt->bind_param('ss', $menuId, $menuId);
    $row = fetch_one_assoc($stmt);

    if ((int)$row['order_count'] > 0 || (int)$row['cart_count'] > 0) {
        json_response([
            'success' => false,
            'message' => 'This menu item is linked to existing orders, so it should be kept for accurate reports. Add a new menu item first, then delete that new item for the demo.'
        ], 409);
    }

    $stmt = $conn->prepare("DELETE FROM menu WHERE MENUID = ?");
    $stmt->bind_param('s', $menuId);
    $stmt->execute();

    json_response(['success' => true, 'message' => "Menu item $menuId has been deleted successfully."]);
}

function handle_employees($conn) {
    require_owner($conn);
    $stmt = $conn->prepare("SELECT
            s.STAFFID,
            s.STAFFNAME,
            s.STAFFPHONENO,
            s.STAFFROLE,
            o.EQUITYTYPE,
            o.CONTRACTDURATION,
            op.WORKSTATION,
            op.SKILLEVEL,
            COUNT(sa.SALESID) AS SALES_HANDLED,
            COALESCE(SUM(sa.SALESTOTAL), 0) AS SALES_TOTAL
        FROM staff s
        LEFT JOIN owner o ON o.STAFFID = s.STAFFID
        LEFT JOIN operationalstaff op ON op.STAFFID = s.STAFFID
        LEFT JOIN sales sa ON sa.STAFFID = s.STAFFID
        GROUP BY s.STAFFID, s.STAFFNAME, s.STAFFPHONENO, s.STAFFROLE, o.EQUITYTYPE, o.CONTRACTDURATION, op.WORKSTATION, op.SKILLEVEL
        ORDER BY s.STAFFROLE DESC, s.STAFFID");
    $employees = fetch_all_assoc($stmt);

    $roleStmt = $conn->prepare("SELECT STAFFROLE, COUNT(*) AS total FROM staff GROUP BY STAFFROLE ORDER BY STAFFROLE");
    $roles = fetch_all_assoc($roleStmt);

    json_response(['success' => true, 'employees' => $employees, 'roles' => $roles]);
}

function handle_employee_delete($conn) {
    $owner = require_owner($conn);
    $input = get_input();
    $staffId = trim($input['staffId'] ?? '');
    if ($staffId === '') json_response(['success' => false, 'message' => 'Staff ID is required.'], 422);

    if ($staffId === $owner['STAFFID']) {
        json_response(['success' => false, 'message' => 'You cannot remove the owner account that is currently logged in.'], 409);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM sales WHERE STAFFID = ?");
    $stmt->bind_param('s', $staffId);
    $row = fetch_one_assoc($stmt);

    if ((int)$row['total'] > 0) {
        json_response([
            'success' => false,
            'message' => 'This employee already has sales records. Deletion is blocked to protect report accuracy. For demo, remove a staff account with no sales records.'
        ], 409);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM operationalstaff WHERE STAFFID = ?");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM owner WHERE STAFFID = ?");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM staff WHERE STAFFID = ?");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();

        $conn->commit();
        json_response(['success' => true, 'message' => "Employee $staffId has been removed successfully."]);
    } catch (Throwable $e) {
        $conn->rollback();
        json_response(['success' => false, 'message' => 'Employee deletion failed.', 'error' => $e->getMessage()], 500);
    }
}

function handle_reports($conn) {
    require_owner($conn);

    $latestStmt = $conn->prepare("SELECT MAX(SALESDATE) AS latest_sales_date FROM sales");
    $latestRow = fetch_one_assoc($latestStmt);
    $latestDate = $latestRow['latest_sales_date'] ?? date('Y-m-d');
    $bounds = period_bounds($latestDate);

    $salesCards = [];
    foreach ($bounds as $key => $period) {
        [$start, $end, $title, $subtitle] = $period;
        $summary = sales_summary($conn, $start, $end);
        $bestDay = best_sales_day($conn, $start, $end);
        $salesCards[$key] = [
            'title' => $title,
            'subtitle' => $subtitle,
            'start' => $start,
            'end' => $end,
            'total_sales' => (float)$summary['total_sales'],
            'sales_count' => (int)$summary['sales_count'],
            'order_count' => (int)$summary['order_count'],
            'average_order_value' => (float)$summary['average_order_value'],
            'best_day' => $bestDay['day_name'] ?? 'No sales yet'
        ];
    }

    $ranking = item_ranking($conn, 50, 'DESC');
    $least = item_ranking($conn, 10, 'ASC');
    $categories = category_performance($conn);

    $paymentStmt = $conn->prepare("SELECT SALESPAYMETHOD, COUNT(*) AS total_transactions, COALESCE(SUM(SALESTOTAL), 0) AS total_sales
        FROM sales
        GROUP BY SALESPAYMETHOD
        ORDER BY total_sales DESC");
    $payments = fetch_all_assoc($paymentStmt);

    $trendStmt = $conn->prepare("SELECT SALESDATE, COUNT(*) AS total_transactions, COALESCE(SUM(SALESTOTAL), 0) AS total_sales
        FROM sales
        GROUP BY SALESDATE
        ORDER BY SALESDATE DESC
        LIMIT 14");
    $trend = fetch_all_assoc($trendStmt);

    $orderTypeStmt = $conn->prepare("SELECT o.ORDERTYPE, COUNT(DISTINCT o.ORDERID) AS order_count, COALESCE(SUM(s.SALESTOTAL), 0) AS total_sales
        FROM sales s
        INNER JOIN orders o ON o.ORDERID = s.ORDERID
        GROUP BY o.ORDERTYPE
        ORDER BY order_count DESC");
    $orderTypes = fetch_all_assoc($orderTypeStmt);

    $normal = booking_summary($conn, 'normal');
    $catering = booking_summary($conn, 'catering');
    $allBookings = booking_summary($conn, 'all');

    $popularDateStmt = $conn->prepare("SELECT DAYNAME(RESERVEDATE) AS day_name, COUNT(*) AS booking_count, COALESCE(SUM(guestCount), 0) AS total_pax
        FROM reservation
        GROUP BY DAYNAME(RESERVEDATE), DAYOFWEEK(RESERVEDATE)
        ORDER BY booking_count DESC, total_pax DESC
        LIMIT 7");
    $popularDates = fetch_all_assoc($popularDateStmt);

    $occasionStmt = $conn->prepare("SELECT COALESCE(NULLIF(OCCASION, ''), 'Not stated') AS occasion, COUNT(*) AS booking_count, COALESCE(SUM(guestCount), 0) AS total_pax
        FROM reservation
        GROUP BY COALESCE(NULLIF(OCCASION, ''), 'Not stated')
        ORDER BY booking_count DESC, total_pax DESC
        LIMIT 8");
    $occasions = fetch_all_assoc($occasionStmt);

    $timeslotStmt = $conn->prepare("SELECT TIMESLOT, COUNT(*) AS booking_count, COALESCE(AVG(guestCount), 0) AS average_pax
        FROM reservation
        GROUP BY TIMESLOT
        ORDER BY booking_count DESC, average_pax DESC
        LIMIT 8");
    $timeslots = fetch_all_assoc($timeslotStmt);

    json_response([
        'success' => true,
        'latest_sales_date' => $latestDate,
        'sales_cards' => $salesCards,
        'item_ranking' => $ranking,
        'least_popular' => $least,
        'most_popular' => $ranking[0] ?? null,
        'categories' => $categories,
        'payments' => $payments,
        'sales_trend' => array_reverse($trend),
        'order_types' => $orderTypes,
        'booking_cards' => [
            'normal' => $normal,
            'catering' => $catering,
            'all' => $allBookings,
            'popular_dates' => $popularDates,
            'occasions' => $occasions,
            'timeslots' => $timeslots
        ]
    ]);
}

function handle_bookings($conn) {
    require_owner($conn);

    $normalCond = booking_condition_sql('normal');
    $cateringCond = booking_condition_sql('catering');

    $normalSql = "SELECT * FROM reservation WHERE $normalCond ORDER BY RESERVEDATE DESC, TIMESLOT DESC";
    $normal = $conn->query($normalSql)->fetch_all(MYSQLI_ASSOC);

    $cateringSql = "SELECT * FROM reservation WHERE $cateringCond ORDER BY RESERVEDATE DESC, TIMESLOT DESC";
    $catering = $conn->query($cateringSql)->fetch_all(MYSQLI_ASSOC);

    $popularDateStmt = $conn->prepare("SELECT DAYNAME(RESERVEDATE) AS day_name, COUNT(*) AS booking_count, COALESCE(SUM(guestCount), 0) AS total_pax
        FROM reservation
        GROUP BY DAYNAME(RESERVEDATE), DAYOFWEEK(RESERVEDATE)
        ORDER BY booking_count DESC, total_pax DESC
        LIMIT 7");
    $popularDates = fetch_all_assoc($popularDateStmt);

    $occasionStmt = $conn->prepare("SELECT COALESCE(NULLIF(OCCASION, ''), 'Not stated') AS occasion, COUNT(*) AS booking_count, COALESCE(SUM(guestCount), 0) AS total_pax
        FROM reservation
        GROUP BY COALESCE(NULLIF(OCCASION, ''), 'Not stated')
        ORDER BY booking_count DESC, total_pax DESC
        LIMIT 8");
    $occasions = fetch_all_assoc($occasionStmt);

    json_response([
        'success' => true,
        'normal' => $normal,
        'catering' => $catering,
        'summary' => [
            'normal' => booking_summary($conn, 'normal'),
            'catering' => booking_summary($conn, 'catering'),
            'all' => booking_summary($conn, 'all'),
            'popular_dates' => $popularDates,
            'occasions' => $occasions
        ]
    ]);
}

function handle_dashboard($conn) {
    $owner = require_owner($conn);

    $latestStmt = $conn->prepare("SELECT MAX(SALESDATE) AS latest_sales_date FROM sales");
    $latestRow = fetch_one_assoc($latestStmt);
    $latestDate = $latestRow['latest_sales_date'] ?? date('Y-m-d');
    $bounds = period_bounds($latestDate);
    [$dayStart, $dayEnd] = $bounds['daily'];
    [$weekStart, $weekEnd] = $bounds['weekly'];

    $daily = sales_summary($conn, $dayStart, $dayEnd);
    $weekly = sales_summary($conn, $weekStart, $weekEnd);

    $simpleStmt = $conn->prepare("SELECT
            (SELECT COUNT(*) FROM menu) AS menu_count,
            (SELECT COUNT(*) FROM staff) AS staff_count,
            (SELECT COUNT(*) FROM reservation WHERE NOT (LOWER(`SESSION`) = 'catering' OR LOWER(`SEATINGPREF`) LIKE '%catering%')) AS reservation_count,
            (SELECT COUNT(*) FROM reservation WHERE LOWER(`SESSION`) = 'catering' OR LOWER(`SEATINGPREF`) LIKE '%catering%') AS catering_count,
            (SELECT COALESCE(AVG(guestCount),0) FROM reservation) AS average_pax");
    $counts = fetch_one_assoc($simpleStmt);

    $popular = item_ranking($conn, 1, 'DESC');
    $least = item_ranking($conn, 1, 'ASC');
    $categories = category_performance($conn);

    $recentStmt = $conn->prepare("SELECT s.SALESID, s.SALESDATE, s.SALESTOTAL, s.SALESPAYMETHOD, s.ORDERID, st.STAFFNAME, o.ORDERTYPE
        FROM sales s
        LEFT JOIN staff st ON st.STAFFID = s.STAFFID
        LEFT JOIN orders o ON o.ORDERID = s.ORDERID
        ORDER BY s.SALESDATE DESC, s.SALESID DESC
        LIMIT 6");
    $recentSales = fetch_all_assoc($recentStmt);

    $bookingDayStmt = $conn->prepare("SELECT DAYNAME(RESERVEDATE) AS day_name, COUNT(*) AS booking_count
        FROM reservation
        GROUP BY DAYNAME(RESERVEDATE), DAYOFWEEK(RESERVEDATE)
        ORDER BY booking_count DESC
        LIMIT 1");
    $popularDay = fetch_one_assoc($bookingDayStmt);

    json_response([
        'success' => true,
        'owner' => $owner,
        'latest_sales_date' => $latestDate,
        'daily' => $daily,
        'weekly' => $weekly,
        'counts' => $counts,
        'most_popular' => $popular[0] ?? null,
        'least_popular' => $least[0] ?? null,
        'categories' => $categories,
        'recent_sales' => $recentSales,
        'popular_booking_day' => $popularDay
    ]);
}

try {
    $conn = get_connection();
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');

    switch ($action) {
        case 'owner_signup': handle_owner_signup($conn); break;
        case 'login': handle_login($conn); break;
        case 'logout': handle_logout(); break;
        case 'session': handle_session($conn); break;
        case 'dashboard': handle_dashboard($conn); break;
        case 'menu_list': handle_menu_list($conn); break;
        case 'menu_save': handle_menu_save($conn); break;
        case 'menu_delete': handle_menu_delete($conn); break;
        case 'employees': handle_employees($conn); break;
        case 'employee_delete': handle_employee_delete($conn); break;
        case 'reports': handle_reports($conn); break;
        case 'bookings': handle_bookings($conn); break;
        default:
            json_response(['success' => false, 'message' => 'Unknown owner API action.'], 404);
    }
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Server error while processing owner API request.', 'error' => $e->getMessage()], 500);
}
