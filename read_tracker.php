<?php
include "db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$result = $conn->query("SELECT * FROM bookings");

if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>