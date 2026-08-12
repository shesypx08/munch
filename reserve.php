<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/system_init.php";
require_once __DIR__ . "/email_helper.php";
require_once __DIR__ . "/php_user_popup.php";

munch_ensure_enhancements($conn);

if (!isset($_SESSION["CUSTUSERNAME"]) || trim($_SESSION["CUSTUSERNAME"]) === "") {
    munch_show_user_popup("Please login first before making a reservation.", "customer-login.html");
}

$custID = trim($_SESSION["CUSTUSERNAME"]);

function postValue($key, $default = "") {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function postArray($key) {
    if (!isset($_POST[$key])) return [];
    if (is_array($_POST[$key])) return array_map('trim', $_POST[$key]);
    return [trim($_POST[$key])];
}

function cleanPhone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

function showAlertBack($message) {
    munch_show_user_popup($message);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Reservation.html");
    exit();
}

$checkCustomer = mysqli_prepare($conn, "SELECT CUSTUSERNAME, CUSTEMAIL, EMAILVERIFIED FROM customer WHERE CUSTUSERNAME = ?");
if (!$checkCustomer) showAlertBack("We could not check your account right now. Please try again.");
mysqli_stmt_bind_param($checkCustomer, "s", $custID);
mysqli_stmt_execute($checkCustomer);
$customerResult = mysqli_stmt_get_result($checkCustomer);

if (mysqli_num_rows($customerResult) === 0) {
    munch_show_user_popup("We could not find your customer session. Please logout and login again.", "customer-login.html");
}

$customer = mysqli_fetch_assoc($customerResult);
if (!empty($customer['CUSTEMAIL']) && (int)$customer['EMAILVERIFIED'] !== 1) {
    echo "<script>alert('Please verify your email before making a reservation.'); window.location.href='email_verification_notice.php?email=" . urlencode($customer['CUSTEMAIL']) . "';</script>";
    exit();
}

$name = postValue("customer-name");
$phone = cleanPhone(postValue("customer-phone"));
$email = strtolower(postValue("customer-email"));
$payment = postValue("payment-method");
$type = postValue("booking-type", "walkin");
$specialRequest = postValue("special-request");

if ($name === "" || $phone === "" || $email === "" || $payment === "") {
    showAlertBack("Please complete your customer details and payment method.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showAlertBack("Invalid email address. Please enter a valid email such as name@email.com.");
}

if (strlen($phone) < 10 || strlen($phone) > 11) {
    showAlertBack("Invalid phone number. Please enter 10 to 11 digits.");
}

if ($type === "walkin") {
    $date = postValue("reservation-date");
    $pax = postValue("reservation-pax");
    $session = postValue("session");
    $time = postValue("time");
    $seating = postValue("seating");
    $occasion = postValue("occasion", "Walk-in Reservation");
    $specialReq = $specialRequest;

    if ($date === "" || $pax === "" || $session === "" || $time === "" || $seating === "") {
        showAlertBack("Please complete all walk-in reservation details.");
    }

    $guestCount = (int)$pax;
    $deposit = $guestCount * 10;
} else {
    $eventType = postValue("event-type");
    $date = postValue("event-date");
    $time = postValue("event-time");
    $setupTime = postValue("setup-time");
    $location = postValue("event-location");
    $pax = postValue("event-package");

    if ($eventType === "" || $date === "" || $time === "" || $location === "" || $pax === "") {
        showAlertBack("Please complete all catering reservation details.");
    }

    $depositMap = ["80" => 100, "150" => 200, "200" => 300, "300" => 500, "500" => 800];
    $session = "Catering";
    $seating = "Event Catering";
    $occasion = $eventType;
    $guestCount = (int)$pax;
    $deposit = isset($depositMap[$pax]) ? $depositMap[$pax] : 0;

    $rice = implode(", ", postArray("rice-choice"));
    $main = implode(", ", postArray("main-choice"));
    $side = implode(", ", postArray("side-choice"));
    $drink = implode(", ", postArray("drink-choice"));

    $specialReq =
        "Event Location: " . $location .
        "\nSetup Time: " . $setupTime .
        "\nRice: " . $rice .
        "\nMain Dish: " . $main .
        "\nSide Dish: " . $side .
        "\nDrink: " . $drink .
        "\nSpecial Request: " . $specialRequest;
}

$status = "Pending Payment";

mysqli_begin_transaction($conn);
try {
    $sql = "INSERT INTO reservation (
                RESERVEDATE, SESSION, TIMESLOT, SEATINGPREF, guestCount, FULLNAME, PHONENO, EMAIL,
                OCCASION, SPECIALREQ, DEPOSIT, PAYMENTMETHOD, STATUS, CUSTID
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new Exception("We could not prepare your reservation right now. Please try again.");

    mysqli_stmt_bind_param(
        $stmt,
        "ssssisssssdsss",
        $date,
        $session,
        $time,
        $seating,
        $guestCount,
        $name,
        $phone,
        $email,
        $occasion,
        $specialReq,
        $deposit,
        $payment,
        $status,
        $custID
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("We could not save your reservation right now. Please try again.");
    }

    $reserveID = (string)mysqli_insert_id($conn);
    munch_create_or_update_payment($conn, 'reservation', $reserveID, $custID, $email, $deposit, $payment);

    mysqli_commit($conn);

    header("Location: payment-gateway.php?type=reservation&ref=" . urlencode($reserveID));
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    showAlertBack($e->getMessage());
}
?>
