<?php
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id']);
    $price = floatval($_POST['price']);
    $payment = intval($_POST['payment']);
    $move_in = $_POST['move_in'];
    $move_out = $_POST['move_out'];

    // TERM
    $term = 1;
    if ($move_in && $move_out) {
        $d1 = new DateTime($move_in);
        $d2 = new DateTime($move_out);
        $term = $d1->diff($d2)->days + 1;
    }

    $total = $price * $term;
    $balance = ($payment == 1) ? 0 : $total;

    // STATUS ENGINE
    $today = new DateTime();
    $status = "PENDING";

    if ($payment == 0) {
        $status = "PENDING";
    } else {
        $start = new DateTime($move_in);
        $end = new DateTime($move_out);

        if ($today < $start) {
            $status = "RESERVED";
        } elseif ($today <= $end) {
            $status = "IN";
        } else {
            $status = "OVERSTAY";
        }
    }

    $stmt = $conn->prepare("
        UPDATE bookings
        SET price=?, payment=?, move_in=?, move_out=?,
            term=?, total=?, balance=?, status=?, updated_at=NOW()
        WHERE id=?
    ");

    $stmt->bind_param(
        "dissidssi",
        $price,
        $payment,
        $move_in,
        $move_out,
        $term,
        $total,
        $balance,
        $status,
        $id
    );

    echo $stmt->execute() ? "success" : $stmt->error;
}
?>