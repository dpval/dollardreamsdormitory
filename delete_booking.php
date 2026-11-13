<?php
include "db.php";

// Turn off HTML error outputs so AJAX gets clean string responses
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id <= 0) {
        exit("invalid_id");
    }

    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");

    if (!$stmt) {
        exit("prepare_failed: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "deleted";
        } else {
            echo "not_found";
        }
    } else {
        echo "execute_failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "invalid_method";
}
?>