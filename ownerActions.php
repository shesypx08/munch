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

$action = isset($_POST["action"]) ? trim($_POST["action"]) : "";

function nextMenuID($conn) {
    $result = mysqli_query($conn, "SELECT MENUID FROM menu ORDER BY CAST(SUBSTRING(MENUID, 2) AS UNSIGNED) DESC LIMIT 1");

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $number = (int)preg_replace("/[^0-9]/", "", $row["MENUID"]);
        return "M" . str_pad($number + 1, 3, "0", STR_PAD_LEFT);
    }

    return "M001";
}

function saveMenuImage($menuID) {
    if (!isset($_FILES["MENUIMAGE"]) || $_FILES["MENUIMAGE"]["error"] === UPLOAD_ERR_NO_FILE) {
        return ["success" => true, "imagePath" => ""];
    }

    if ($_FILES["MENUIMAGE"]["error"] !== UPLOAD_ERR_OK) {
        return ["success" => false, "message" => "Menu image upload failed."];
    }

    if ($_FILES["MENUIMAGE"]["size"] > 3 * 1024 * 1024) {
        return ["success" => false, "message" => "Menu image must be 3MB or smaller."];
    }

    $tmpFile = $_FILES["MENUIMAGE"]["tmp_name"];
    $mime = mime_content_type($tmpFile);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed[$mime])) {
        return ["success" => false, "message" => "Only JPG, PNG, or WEBP menu images are allowed."];
    }

    $imgDir = __DIR__ . "/img/";

    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0777, true);
    }

    // Remove old same-menu image with different extension if it exists.
    foreach (["jpg", "jpeg", "png", "webp", "JPG", "JPEG", "PNG", "WEBP"] as $oldExt) {
        $oldPath = $imgDir . $menuID . "." . $oldExt;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    $extension = $allowed[$mime];
    $targetPath = $imgDir . $menuID . "." . $extension;
    $dbPath = "img/" . $menuID . "." . $extension;

    if (!move_uploaded_file($tmpFile, $targetPath)) {
        return ["success" => false, "message" => "Could not save menu image into img folder."];
    }

    return ["success" => true, "imagePath" => $dbPath];
}

if ($action === "addMenu") {
    $menuID = nextMenuID($conn);
    $name = isset($_POST["MENUNAME"]) ? trim($_POST["MENUNAME"]) : "";
    $category = isset($_POST["MENUCATEGORY"]) ? trim($_POST["MENUCATEGORY"]) : "";
    $price = isset($_POST["MENUPRICE"]) ? (float)$_POST["MENUPRICE"] : 0;
    $desc = isset($_POST["MENUDESC"]) ? trim($_POST["MENUDESC"]) : "";

    if ($name === "" || $category === "" || $price <= 0) {
        respond(["success" => false, "message" => "Menu name, category and valid price are required."]);
    }

    $allowedCategories = ["Nasi", "Main Dishes", "Vegetables", "Side Dishes", "Drinks", "Combo Sets"];
    if (!in_array($category, $allowedCategories, true)) {
        respond(["success" => false, "message" => "Invalid category. Please choose Nasi, Main Dishes, Vegetables, Side Dishes, Drinks, or Combo Sets."]);
    }

    if ($desc === "") {
        $desc = "No description yet.";
    }

    mysqli_begin_transaction($conn);

    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO menu (MENUID, MENUNAME, MENUPRICE, MENUCATEGORY, MENUDESC) VALUES (?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Menu item could not be saved right now. Please try again.");
        }

        mysqli_stmt_bind_param($stmt, "ssdss", $menuID, $name, $price, $category, $desc);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Menu item could not be saved right now. Please try again.");
        }

        $imageResult = saveMenuImage($menuID);

        if (!$imageResult["success"]) {
            throw new Exception($imageResult["message"]);
        }

        mysqli_commit($conn);

        respond([
            "success" => true,
            "MENUID" => $menuID,
            "imagePath" => $imageResult["imagePath"],
            "message" => $imageResult["imagePath"] !== ""
                ? "Menu item $menuID and image uploaded successfully. It now appears in the live menu list."
                : "Menu item $menuID added successfully. It now appears in the live menu list."
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);

        // Clean up uploaded file if it was saved before an error.
        foreach (["jpg", "jpeg", "png", "webp", "JPG", "JPEG", "PNG", "WEBP"] as $ext) {
            $path = __DIR__ . "/img/" . $menuID . "." . $ext;
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        respond(["success" => false, "message" => "Could not add menu: " . $e->getMessage()]);
    }
}

if ($action === "updateMenuPrice") {
    $menuID = isset($_POST["MENUID"]) ? trim($_POST["MENUID"]) : "";
    $price = isset($_POST["MENUPRICE"]) ? (float)$_POST["MENUPRICE"] : 0;

    if ($menuID === "" || $price <= 0) {
        respond(["success" => false, "message" => "Menu ID and valid price are required."]);
    }

    $stmt = mysqli_prepare($conn, "UPDATE menu SET MENUPRICE = ? WHERE MENUID = ?");

    if (!$stmt) respond(["success" => false, "message" => "Menu price could not be updated right now. Please try again."]);

    mysqli_stmt_bind_param($stmt, "ds", $price, $menuID);

    if (mysqli_stmt_execute($stmt)) {
        respond(["success" => true, "message" => "Menu price updated successfully.", "MENUID" => $menuID]);
    }

    respond(["success" => false, "message" => "Menu price update failed. Please try again."]);
}

if ($action === "deleteMenu") {
    $menuID = isset($_POST["MENUID"]) ? trim($_POST["MENUID"]) : "";

    if ($menuID === "") {
        respond(["success" => false, "message" => "Menu ID is required."]);
    }

    $usedCount = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ordermenu WHERE MENUID = '" . mysqli_real_escape_string($conn, $menuID) . "'"))[0];

    if ($usedCount > 0) {
        respond(["success" => false, "message" => "Cannot delete this menu because it already appears in order records. Update the price/name instead."]);
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM menu WHERE MENUID = ?");

    if (!$stmt) respond(["success" => false, "message" => "Menu item could not be deleted right now. Please try again."]);

    mysqli_stmt_bind_param($stmt, "s", $menuID);

    if (mysqli_stmt_execute($stmt)) {
        foreach (["jpg", "jpeg", "png", "webp", "JPG", "JPEG", "PNG", "WEBP"] as $ext) {
            $path = __DIR__ . "/img/" . $menuID . "." . $ext;
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        respond(["success" => true, "message" => "Menu item deleted successfully.", "MENUID" => $menuID]);
    }

    respond(["success" => false, "message" => "Menu item deletion failed. Please try again."]);
}

if ($action === "deleteEmployee") {
    $staffID = isset($_POST["STAFFID"]) ? trim($_POST["STAFFID"]) : "";

    if ($staffID === "") {
        respond(["success" => false, "message" => "Staff ID is required."]);
    }

    if ($staffID === $_SESSION["OWNERID"]) {
        respond(["success" => false, "message" => "You cannot remove the owner account currently logged in."]);
    }

    $salesCount = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM sales WHERE STAFFID = '" . mysqli_real_escape_string($conn, $staffID) . "'"))[0];

    if ($salesCount > 0) {
        respond(["success" => false, "message" => "Cannot delete this staff because they are linked to sales records."]);
    }

    mysqli_begin_transaction($conn);

    try {
        $deleteOwner = mysqli_prepare($conn, "DELETE FROM owner WHERE STAFFID = ?");
        mysqli_stmt_bind_param($deleteOwner, "s", $staffID);
        mysqli_stmt_execute($deleteOwner);

        $deleteOperational = mysqli_prepare($conn, "DELETE FROM operationalstaff WHERE STAFFID = ?");
        mysqli_stmt_bind_param($deleteOperational, "s", $staffID);
        mysqli_stmt_execute($deleteOperational);

        $deleteStaff = mysqli_prepare($conn, "DELETE FROM staff WHERE STAFFID = ?");
        mysqli_stmt_bind_param($deleteStaff, "s", $staffID);

        if (!mysqli_stmt_execute($deleteStaff)) {
            throw new Exception("Employee record could not be removed right now. Please try again.");
        }

        mysqli_commit($conn);
        respond(["success" => true]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        respond(["success" => false, "message" => "Could not remove staff: " . $e->getMessage()]);
    }
}


if ($action === "updateBookingStatus") {
    $reserveID = isset($_POST["RESERVEID"]) ? trim($_POST["RESERVEID"]) : "";
    $decision = isset($_POST["DECISION"]) ? strtolower(trim($_POST["DECISION"])) : "";

    if ($reserveID === "") {
        respond(["success" => false, "message" => "Reservation ID is required."]);
    }

    if ($decision === "accepted" || $decision === "accept") {
        $newStatus = "Confirmed";
    } elseif ($decision === "declined" || $decision === "decline") {
        $newStatus = "Refunded";
    } else {
        respond(["success" => false, "message" => "Please choose Accept or Declined."]);
    }

    $checkStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM reservation WHERE RESERVEID = ?");
    if (!$checkStmt) respond(["success" => false, "message" => "Booking record could not be checked right now."]);
    mysqli_stmt_bind_param($checkStmt, "s", $reserveID);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $count);
    mysqli_stmt_fetch($checkStmt);
    mysqli_stmt_close($checkStmt);

    if ((int)$count === 0) {
        respond(["success" => false, "message" => "Booking record was not found."]);
    }

    $stmt = mysqli_prepare($conn, "UPDATE reservation SET STATUS = ? WHERE RESERVEID = ?");
    if (!$stmt) respond(["success" => false, "message" => "Booking status could not be updated right now."]);

    mysqli_stmt_bind_param($stmt, "ss", $newStatus, $reserveID);

    if (mysqli_stmt_execute($stmt)) {
        respond([
            "success" => true,
            "RESERVEID" => $reserveID,
            "STATUS" => $newStatus,
            "message" => "Booking " . $reserveID . " status updated to " . $newStatus . "."
        ]);
    }

    respond(["success" => false, "message" => "Booking status update failed. Please try again."]);
}

respond(["success" => false, "message" => "Invalid owner action."]);
?>
