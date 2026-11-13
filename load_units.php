<?php
include "db.php";

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================
   GET BOOKINGS STATUS
========================= */
$unitStatus = [];

$bookingQuery = $conn->query("
    SELECT unit_id, payment, reservation_fee
    FROM bookings
");

while ($b = $bookingQuery->fetch_assoc()) {

    $unitId = $b['unit_id'];

    $isBooked = ($b['payment'] == 1);
    $isReserved = ($b['reservation_fee'] != 0 && $b['payment'] == 0);

    if ($isBooked) {
        $unitStatus[$unitId] = "booked";
    } elseif ($isReserved && (!isset($unitStatus[$unitId]) || $unitStatus[$unitId] !== "booked")) {
        $unitStatus[$unitId] = "reserved";
    } elseif (!isset($unitStatus[$unitId])) {
        $unitStatus[$unitId] = "available";
    }
}

/* =========================
   ROOM TYPE CHECK (NO TABLE RELIANCE)
   SIMPLE RULE BASED ON TEXT
========================= */
function isBedspaceType($roomType) {
    $t = strtolower($roomType);
    return strpos($t, 'bed') !== false;
}

/* =========================
   GET UNITS
========================= */
$result = $conn->query("SELECT * FROM units ORDER BY id ASC");

$units = [];

while ($row = $result->fetch_assoc()) {

    $unitName = $row['unit_name'];
    $type = $row['room_type'];
    $unitId = $row['id'];

    $status = $unitStatus[$unitId] ?? "available";
    $isBedspace = isBedspaceType($type);

    /* GROUPING (SAFE) */
    if (!isset($units[$unitName])) {
        $units[$unitName] = [];
    }

    if (!isset($units[$unitName][$type])) {
        $units[$unitName][$type] = [
            "unit_name" => $unitName,
            "room_type" => $type,
            "is_bedspace" => $isBedspace,
            "image" => $row['image'],
            "items" => []
        ];
    }

    /* BEDSPACE IMAGE PRIORITY */
    if ($isBedspace) {
        $units[$unitName][$type]["image"] = $row['image'];
    }

    $units[$unitName][$type]["items"][] = [
        "id" => $unitId,
        "description" => $row['description'],
        "image" => $row['image'],
        "status" => $status
    ];
}

/* =========================
   SUMMARY
========================= */
$summary = [
    "rooms_available" => 0,
    "rooms_booked" => 0,
    "beds_available" => 0,
    "beds_booked" => 0
];

foreach ($units as $unitName => $types) {
    foreach ($types as $type => $group) {

        foreach ($group["items"] as $item) {

            $status = $item["status"];

            if ($group["is_bedspace"]) {

                preg_match('/-(\d+)$/', $item['description'], $match);
                $beds = (int)($match[1] ?? 1);

                if ($status === "booked") {
                    $summary["beds_booked"] += $beds;
                } else {
                    $summary["beds_available"] += $beds;
                }

            } else {

                if ($status === "booked") {
                    $summary["rooms_booked"] += 1;
                } else {
                    $summary["rooms_available"] += 1;
                }
            }
        }
    }
}

/* =========================
   OUTPUT SAFE JSON
========================= */
echo json_encode([
    "units" => $units,
    "summary" => $summary
]);