<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/php_user_popup.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: staff-signup.html");
    exit();
}

$staffID = isset($_POST["STAFFID"]) ? trim($_POST["STAFFID"]) : "";
$staffName = isset($_POST["STAFFNAME"]) ? trim($_POST["STAFFNAME"]) : "";
$staffPhone = isset($_POST["STAFFPHONENO"]) ? preg_replace("/[^0-9]/", "", $_POST["STAFFPHONENO"]) : "";
$staffPassword = isset($_POST["STAFFPASSWORD"]) ? $_POST["STAFFPASSWORD"] : "";
$staffConfirm = isset($_POST["STAFFCONFIRMPASS"]) ? $_POST["STAFFCONFIRMPASS"] : "";
$staffRole = isset($_POST["STAFFROLE"]) ? trim($_POST["STAFFROLE"]) : "";

if ($staffID === "" || $staffName === "" || $staffPhone === "" || $staffPassword === "" || $staffConfirm === "" || $staffRole === "") {
    munch_show_user_popup("Please complete all staff details.");
}

$allowedStaffRoles = ["WAITER", "CASHIER", "CHEF", "KITCHEN STAFF"];
$staffRole = strtoupper($staffRole);

if (!in_array($staffRole, $allowedStaffRoles, true)) {
    munch_show_user_popup("Invalid staff role selected. Owner accounts cannot be created from staff registration.");
}

if (strlen($staffPhone) < 10 || strlen($staffPhone) > 11) {
    munch_show_user_popup("Phone number must be 10 to 11 digits.");
}

if ($staffPassword !== $staffConfirm) {
    munch_show_user_popup("Passwords do not match.");
}

$hashedPassword = password_hash($staffPassword, PASSWORD_DEFAULT);

$workstationByRole = [
    "WAITER" => "Service",
    "CASHIER" => "Cashier",
    "CHEF" => "Kitchen",
    "KITCHEN STAFF" => "Kitchen"
];
$workstation = $workstationByRole[$staffRole];
$skillLevel = 0;

mysqli_begin_transaction($conn);

try {
    $sql = "INSERT INTO staff (STAFFID, STAFFNAME, STAFFPHONENO, STAFFPASS, STAFFROLE)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception("Unable to prepare staff registration.");
    }

    mysqli_stmt_bind_param($stmt, "sssss", $staffID, $staffName, $staffPhone, $hashedPassword, $staffRole);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $operationalSql = "INSERT INTO operationalstaff (STAFFID, WORKSTATION, SKILLEVEL)
                       VALUES (?, ?, ?)";
    $operationalStmt = mysqli_prepare($conn, $operationalSql);

    if (!$operationalStmt) {
        throw new Exception("Unable to prepare operational staff registration.");
    }

    mysqli_stmt_bind_param($operationalStmt, "ssi", $staffID, $workstation, $skillLevel);
    mysqli_stmt_execute($operationalStmt);
    mysqli_stmt_close($operationalStmt);

    mysqli_commit($conn);
    munch_show_user_popup("Staff registration successful! Please login.", "staff-login.html");
} catch (Throwable $error) {
    mysqli_rollback($conn);
    munch_show_user_popup("Staff registration failed. The Staff ID may already be registered.");
}
?>
