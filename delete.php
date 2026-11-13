<?php
/*
 * Admin: delete something. POST fields: type, id, csrf
 *   type=booking   booking + the guest's private ID / payment-proof files   (track_booking.php)
 *   type=unit      unit + its photo, then back to manage_units.php            (manage_units.php form)
 *   type=schedule  viewing time slot                                          (viewing_sched.php)
 *   type=viewing   guest's viewing request                                    (viewing_sched.php)
 * AJAX calls get "success" or an error message.
 */
require_once "auth.php";
require_admin_api();
include "db.php";

$type = $_POST['type'] ?? '';
$id   = (int)($_POST['id'] ?? 0);

switch ($type) {
    case 'booking':
        delete_booking($conn, $id);
        break;
    case 'unit':
        delete_unit($conn, $id);
        header('Location: manage_units.php');
        exit();
    case 'schedule':
        delete_row($conn, 'viewing_schedule', $id, 'Could not delete the slot.');
        break;
    case 'viewing':
        delete_row($conn, 'viewing_requests', $id, 'Could not delete the request.');
        break;
    default:
        http_response_code(400);
        exit('Unknown delete type.');
}

/* ---------- helpers ---------- */

/* $table is always one of the fixed names above, never user input. */
function delete_row($conn, $table, $id, $errorText) {
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id);
    echo ($stmt->execute() && $stmt->affected_rows > 0) ? 'success' : $errorText;
}

function delete_booking($conn, $id) {
    $sel = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
    $sel->bind_param('i', $id);
    $sel->execute();
    $booking = $sel->get_result()->fetch_assoc();
    $sel->close();
    if (!$booking) {
        exit('Booking not found.');
    }

    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        error_log('delete booking: ' . $stmt->error);
        exit('Could not delete the booking.');
    }

    foreach (['id_upload', 'payment_proof'] as $col) {
        $path = $booking[$col] ?? '';
        if ($path && strpos($path, 'private_uploads/') === 0 && strpos($path, '..') === false) {
            @unlink(__DIR__ . '/' . $path);
        }
    }
    echo 'success';
}

function delete_unit($conn, $id) {
    $sel = $conn->prepare("SELECT image FROM units WHERE id = ?");
    $sel->bind_param('i', $id);
    $sel->execute();
    $unit = $sel->get_result()->fetch_assoc();
    $sel->close();
    if (!$unit) {
        return;
    }

    $stmt = $conn->prepare("DELETE FROM units WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        // Past bookings keep their own copy of the unit name, so the tracker still shows them.
        delete_unit_image_if_unused($conn, $unit['image']);
    } else {
        error_log('delete unit: ' . $stmt->error);
    }
}
