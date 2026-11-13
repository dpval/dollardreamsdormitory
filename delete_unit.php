<?php
session_start();

// Security Guard: Ensure admin authentication
if (!isset($_SESSION['admin'])) {
    exit("Unauthorized Access");
}

include "db.php";

$id = $_POST['id'] ?? $_GET['id'] ?? null;

if (!$id) {
    exit("No ID received");
}

// 1. Fetch Image File Path and Delete File from Disk
$getStmt = $conn->prepare("SELECT image FROM units WHERE id = ?");
$getStmt->bind_param("i", $id);
$getStmt->execute();
$res = $getStmt->get_result();

if ($row = $res->fetch_assoc()) {
    $imagePath = $row['image'];
    if (!empty($imagePath) && file_exists($imagePath)) {
        unlink($imagePath); // Deletes file from server
    }
}
$getStmt->close();

// 2. Delete Unit Record from Database
$stmt = $conn->prepare("DELETE FROM units WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redirects if accessed via normal <a> link; returns plain string if requested via AJAX
    if (isset($_GET['id'])) {
        header("Location: manage_units.php");
        exit();
    } else {
        echo "deleted";
    }
} else {
    echo "Error deleting unit: " . $stmt->error;
}

$stmt->close();
?>