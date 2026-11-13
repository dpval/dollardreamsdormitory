<?php
include "db.php";

header('Content-Type: application/json');

$result = $conn->query("
    SELECT 
        id,
        unit_id,
        name,
        mobile,
        email,
        reservation_fee,
        move_in,
        move_out,
        payment_status,
        created_at
    FROM bookings
    ORDER BY id DESC
");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>