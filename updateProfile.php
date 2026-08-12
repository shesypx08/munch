<?php
session_start();
include "dbconnect.php";

header("Content-Type: application/json");

function columnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);

    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = mysqli_query($conn, $sql);

    return $result && mysqli_num_rows($result) > 0;
}

if (!isset($_SESSION["CUSTUSERNAME"]) || trim($_SESSION["CUSTUSERNAME"]) === "") {
    echo json_encode(["success" => false, "message" => "Please login first."]);
    exit();
}

$username = trim($_SESSION["CUSTUSERNAME"]);
$name = isset($_POST["CUSTNAME"]) ? trim($_POST["CUSTNAME"]) : "";
$phone = isset($_POST["PHONENO"]) ? preg_replace("/[^0-9]/", "", $_POST["PHONENO"]) : "";
$newPassword = isset($_POST["NEWPASSWORD"]) ? $_POST["NEWPASSWORD"] : "";

if ($name === "" || $phone === "") {
    echo json_encode(["success" => false, "message" => "Name and phone number are required."]);
    exit();
}

if (strlen($phone) < 10 || strlen($phone) > 11) {
    echo json_encode(["success" => false, "message" => "Phone number must be 10 to 11 digits."]);
    exit();
}

/*
    Profile picture upload.
    It saves the file into uploads/profile/
    and saves the path into customer.CUSTPROFILEPIC.
*/
$profilePicPath = null;

if (isset($_FILES["PROFILEPIC"]) && $_FILES["PROFILEPIC"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["PROFILEPIC"]["error"] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "Profile picture upload failed."]);
        exit();
    }

    if ($_FILES["PROFILEPIC"]["size"] > 2 * 1024 * 1024) {
        echo json_encode(["success" => false, "message" => "Profile picture must be 2MB or smaller."]);
        exit();
    }

    $allowedMime = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    $tmpFile = $_FILES["PROFILEPIC"]["tmp_name"];
    $mime = mime_content_type($tmpFile);

    if (!isset($allowedMime[$mime])) {
        echo json_encode(["success" => false, "message" => "Only JPG, PNG, or WEBP images are allowed."]);
        exit();
    }

    $uploadDir = __DIR__ . "/uploads/profile/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = $allowedMime[$mime];
    $safeUsername = preg_replace("/[^a-zA-Z0-9_-]/", "_", $username);
    $fileName = $safeUsername . "_" . time() . "." . $extension;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpFile, $targetPath)) {
        echo json_encode(["success" => false, "message" => "Could not save profile picture."]);
        exit();
    }

    $profilePicPath = "uploads/profile/" . $fileName;
}

$hasProfilePicColumn = columnExists($conn, "customer", "CUSTPROFILEPIC");

if ($profilePicPath !== null && !$hasProfilePicColumn) {
    echo json_encode([
        "success" => false,
        "message" => "Profile picture updates are not available right now. Please try again later."
    ]);
    exit();
}

if ($newPassword !== "") {
    if (strlen($newPassword) < 8) {
        echo json_encode(["success" => false, "message" => "Password must be at least 8 characters."]);
        exit();
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    if ($profilePicPath !== null) {
        $sql = "UPDATE customer
                SET CUSTNAME = ?, PHONENO = ?, CUSTPASSWORD = ?, CUSTPROFILEPIC = ?
                WHERE CUSTUSERNAME = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Profile update is not available right now. Please try again later."]);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $hashed, $profilePicPath, $username);
    } else {
        $sql = "UPDATE customer
                SET CUSTNAME = ?, PHONENO = ?, CUSTPASSWORD = ?
                WHERE CUSTUSERNAME = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Profile update is not available right now. Please try again later."]);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "ssss", $name, $phone, $hashed, $username);
    }
} else {
    if ($profilePicPath !== null) {
        $sql = "UPDATE customer
                SET CUSTNAME = ?, PHONENO = ?, CUSTPROFILEPIC = ?
                WHERE CUSTUSERNAME = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Profile update is not available right now. Please try again later."]);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "ssss", $name, $phone, $profilePicPath, $username);
    } else {
        $sql = "UPDATE customer
                SET CUSTNAME = ?, PHONENO = ?
                WHERE CUSTUSERNAME = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Profile update is not available right now. Please try again later."]);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "sss", $name, $phone, $username);
    }
}

if (mysqli_stmt_execute($stmt)) {
    $_SESSION["CUSTNAME"] = $name;

    echo json_encode([
        "success" => true,
        "message" => "Profile updated successfully.",
        "profilePic" => $profilePicPath
    ]);
    exit();
}

echo json_encode(["success" => false, "message" => "Profile update failed. Please try again."]);
?>
