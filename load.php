<?php
/*
 * Public: data for book.php as JSON.
 *   load.php?type=units     every room with today's availability
 *   load.php?type=schedule  upcoming viewing slots (past slots are left out)
 *
 * Unit codes (room numbers), floors and prices are for the admin only and are never sent here.
 */
include "db.php";

header('Content-Type: application/json');

switch ($_GET['type'] ?? '') {
    case 'units':
        echo json_encode(['rooms' => load_rooms($conn)]);
        break;
    case 'schedule':
        echo json_encode(load_schedule($conn));
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown load type.']);
}

/* ---------- helpers ---------- */

function load_rooms($conn) {
    $rooms = [];
    foreach (get_unit_availability($conn) as $u) {
        $rooms[] = [
            'id'          => (int)$u['id'],          // database id, needed to book; not the room number
            'room_type'   => $u['room_type'],
            'description' => $u['description'],
            'image'       => $u['image'],
            'is_bedspace' => $u['is_bedspace'],
            'status'      => $u['status'],
            'capacity'    => $u['capacity'],
            'booked'      => $u['booked'],
            'reserved'    => $u['reserved'],
            'available'   => $u['available'],
        ];
    }
    return $rooms;
}

function load_schedule($conn) {
    $today = date('Y-m-d');
    $now   = date('H:i:s');

    $stmt = $conn->prepare("
        SELECT id, day, date, time_start, time_end
        FROM viewing_schedule
        WHERE date > ? OR (date = ? AND time_end > ?)
        ORDER BY date ASC, time_start ASC
    ");
    $stmt->bind_param('sss', $today, $today, $now);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
