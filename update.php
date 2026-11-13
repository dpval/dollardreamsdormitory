<?php
/*
 * Admin: update something. POST fields: type, id, csrf + the fields below.
 *   type=booking  move_in, move_out, price, payment   (track_booking.php)
 *   type=viewing  status = Pending | Done | Cancelled  (viewing_sched.php)
 * Replies "success" or an error message.
 */
require_once "auth.php";
require_admin_api();
include "db.php";

$type = $_POST['type'] ?? '';
$id   = (int)($_POST['id'] ?? 0);

switch ($type) {
    case 'booking':
        update_booking($conn, $id);
        break;
    case 'viewing':
        update_viewing_status($conn, $id);
        break;
    default:
        http_response_code(400);
        exit('Unknown update type.');
}

/* ---------- helpers ---------- */

/* Save dates, rate and payment status of one booking. */
function update_booking($conn, $id) {
    $price   = max(0, (float)($_POST['price'] ?? 0));
    $payment = ($_POST['payment'] ?? '0') === '1' ? 1 : 0;
    $in      = parse_date($_POST['move_in'] ?? '');
    $out     = parse_date($_POST['move_out'] ?? '');

    if (!$in || !$out) {
        exit('Please enter valid move-in and move-out dates.');
    }
    if ($out < $in) {
        exit('Move-out must be on or after move-in.');
    }

    $sel = $conn->prepare("SELECT reservation_fee FROM bookings WHERE id = ?");
    $sel->bind_param('i', $id);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    $sel->close();
    if (!$row) {
        exit('Booking not found.');
    }

    $moveIn  = $in->format('Y-m-d');
    $moveOut = $out->format('Y-m-d');
    $calc = calculate_booking($moveIn, $moveOut, $price, $payment, $row['reservation_fee']);
    $rate = $calc['rate'];   // store the rate actually used

    $stmt = $conn->prepare("
        UPDATE bookings
        SET price = ?, payment = ?, move_in = ?, move_out = ?,
            term = ?, total = ?, balance = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('dissiddsi', $rate, $payment, $moveIn, $moveOut,
        $calc['days'], $calc['total'], $calc['balance'], $calc['status'], $id);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        error_log('update booking: ' . $stmt->error);
        echo 'Could not save the booking.';
    }
}

/* Mark a viewing request Pending / Done / Cancelled. */
function update_viewing_status($conn, $id) {
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['Pending', 'Done', 'Cancelled'], true)) {
        exit('Invalid status.');
    }

    $stmt = $conn->prepare("UPDATE viewing_requests SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    echo $stmt->execute() ? 'success' : 'Could not update the status.';
}
