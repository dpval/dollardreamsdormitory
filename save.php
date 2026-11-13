<?php
/*
 * Save something new. POST field "type" picks what:
 *   type=booking   Public  - guest books a room (book.php)               -> "success" or error
 *   type=viewing   Public  - guest requests a viewing (book.php)         -> "success" or error
 *   type=schedule  Admin   - add a viewing time slot (viewing_sched.php) -> back to viewing_sched.php
 */
include "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('invalid_method');
}

switch ($_POST['type'] ?? '') {
    case 'booking':
        save_booking($conn);
        break;
    case 'viewing':
        save_viewing($conn);
        break;
    case 'schedule':
        require_once "auth.php";
        require_admin_api();
        save_schedule($conn);
        break;
    default:
        http_response_code(400);
        exit('Unknown save type.');
}

/* =====================================================================
   BOOKING (public)
   ===================================================================== */
function save_booking($conn) {
    /* ---------- Guest details ---------- */
    $unitId = (int)($_POST['unit_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $mobile = clean_mobile($_POST['mobile'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $fee    = (float)($_POST['reservation_fee'] ?? 0);
    $in     = parse_date($_POST['move_in'] ?? '');
    $out    = parse_date($_POST['move_out'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        exit('Please enter your full name.');
    }
    if (!$mobile) {
        exit('Please enter a valid mobile number (e.g. 09171234567).');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit('Please enter a valid email address, or leave it blank.');
    }
    if ($fee < MIN_RESERVATION_FEE) {
        exit('The reservation fee must be at least ₱' . MIN_RESERVATION_FEE . '.');
    }
    if (!$in || !$out) {
        exit('Please choose your move-in and move-out dates.');
    }
    if ($in < new DateTime('today')) {
        exit('The move-in date cannot be in the past.');
    }
    if ($out < $in) {
        exit('The move-out date must be on or after the move-in date.');
    }

    /* ---------- Room: details come from the database, not the browser ---------- */
    $units = get_unit_availability($conn);
    if (!isset($units[$unitId])) {
        exit('That room no longer exists. Please reload the page.');
    }
    $unit = $units[$unitId];
    if ($unit['available'] < 1) {
        exit('Sorry, this room was just taken. Please reload the page and choose another.');
    }

    /* ---------- Files: checked first, so a rejected booking leaves nothing behind ---------- */
    $idExt  = check_upload('id_upload', 'Government ID', ['jpg', 'png', 'webp', 'pdf'], 5);
    $payExt = check_upload('payment_proof', 'GCash payment proof', ['jpg', 'png', 'webp', 'pdf'], 5);
    foreach ([$idExt, $payExt] as $check) {
        if ($check[0] === '!') {
            exit(substr($check, 1));
        }
    }

    // Private folder: blocked from browsers by private_uploads/.htaccess
    $idPath  = save_upload('id_upload', $idExt, 'private_uploads/', 'id_');
    $payPath = save_upload('payment_proof', $payExt, 'private_uploads/', 'pay_');
    if (!$idPath || !$payPath) {
        exit('Could not save your files. Please try again.');
    }

    /* ---------- Save ---------- */
    $moveIn  = $in->format('Y-m-d');
    $moveOut = $out->format('Y-m-d');
    $price   = 0;   // 0 = default rate for the length of stay; the admin can change it in the tracker
    $payment = 0;
    $calc    = calculate_booking($moveIn, $moveOut, $price, $payment, $fee);

    $cols = "unit_id, unit_name, room_type, description, name, mobile, email, id_upload,
             move_in, move_out, term, reservation_fee, price, payment, total, balance, status";
    $marks = "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?";
    $types = "isssssssssiddidds";
    $params = [
        $unitId, $unit['unit_name'], $unit['room_type'], $unit['description'],
        $name, $mobile, $email, $idPath,
        $moveIn, $moveOut, $calc['days'], $fee, $price, $payment,
        $calc['total'], $calc['balance'], $calc['status'],
    ];
    if (bookings_has_payment_proof($conn)) {
        $cols  .= ", payment_proof";
        $marks .= ",?";
        $types .= "s";
        $params[] = $payPath;
    }

    $stmt = $conn->prepare("INSERT INTO bookings ($cols, created_at) VALUES ($marks, NOW())");
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        error_log('save booking: ' . $stmt->error);
        @unlink(__DIR__ . '/' . $idPath);
        @unlink(__DIR__ . '/' . $payPath);
        exit('Sorry, your booking could not be saved. Please try again.');
    }

    /* ---------- Email the owner (the booking is saved even if this fails) ---------- */
    $attachments = [];
    foreach (['Government-ID' => $idPath, 'Payment-Proof' => $payPath] as $label => $path) {
        $attachments[] = [
            'filename' => $label . '.' . pathinfo($path, PATHINFO_EXTENSION),
            'content'  => base64_encode(file_get_contents(__DIR__ . '/' . $path)),
        ];
    }

    send_notification_email('New Booking & Reservation Request Received', "
        <h2>New Room Reservation Details</h2>
        <p><strong>Full Name:</strong> " . e($name) . "</p>
        <p><strong>Mobile:</strong> " . e($mobile) . "</p>
        <p><strong>Email:</strong> " . e($email) . "</p>
        <p><strong>Unit Name:</strong> " . e($unit['unit_name']) . "</p>
        <p><strong>Room Type:</strong> " . e($unit['room_type']) . "</p>
        <p><strong>Description:</strong> " . e($unit['description']) . "</p>
        <p><strong>Reservation Fee:</strong> &#8369;" . number_format($fee, 2) . "</p>
        <p><strong>Move-In Date:</strong> " . e($moveIn) . "</p>
        <p><strong>Move-Out Date:</strong> " . e($moveOut) . "</p>
        <p><strong>Total Term:</strong> " . e($calc['term_text']) . "</p>
        <p><strong>Estimated Total:</strong> &#8369;" . number_format($calc['total'], 2) . "</p>
        <p><em>Government ID and payment proof are attached.</em></p>
    ", $attachments);

    echo 'success';
}

/* =====================================================================
   VIEWING REQUEST (public)
   ===================================================================== */
function save_viewing($conn) {
    $name     = trim($_POST['name'] ?? '');
    $mobile   = clean_mobile($_POST['mobile'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');

    if ($name === '' || $schedule === '') {
        exit('Please fill in all fields.');
    }
    if (!$mobile) {
        exit('Please enter a valid mobile number (e.g. 09171234567).');
    }
    if (mb_strlen($name) > 100 || mb_strlen($schedule) > 150) {
        exit('Input is too long.');
    }

    $stmt = $conn->prepare("
        INSERT INTO viewing_requests (name, mobile, schedule, status, created_at)
        VALUES (?, ?, ?, 'Pending', NOW())
    ");
    $stmt->bind_param('sss', $name, $mobile, $schedule);

    if (!$stmt->execute()) {
        error_log('save viewing: ' . $stmt->error);
        exit('Sorry, your request could not be saved. Please try again.');
    }

    send_notification_email('New Customer Viewing Request Received', "
        <h2>New Viewing Request</h2>
        <p><strong>Customer Name:</strong> " . e($name) . "</p>
        <p><strong>Mobile Number:</strong> " . e($mobile) . "</p>
        <p><strong>Requested Schedule Slot:</strong> " . e($schedule) . "</p>
        <p><strong>Status:</strong> Pending</p>
    ");

    echo 'success';
}

/* =====================================================================
   VIEWING TIME SLOT (admin form on viewing_sched.php)
   ===================================================================== */
function save_schedule($conn) {
    $back = function ($error = '') {
        header('Location: viewing_sched.php' . ($error ? '?error=' . urlencode($error) : ''));
        exit();
    };

    $date  = parse_date($_POST['date'] ?? '');
    $start = $_POST['time_start'] ?? '';
    $end   = $_POST['time_end'] ?? '';

    if (!$date || !preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
        $back('Please fill in the date and both times.');
    }
    if ($date < new DateTime('today')) {
        $back('The date cannot be in the past.');
    }
    if ($end <= $start) {
        $back('The end time must be after the start time.');
    }

    $day     = $date->format('l');   // e.g. "Monday", taken from the date
    $dateStr = $date->format('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO viewing_schedule (day, date, time_start, time_end) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $day, $dateStr, $start, $end);

    if (!$stmt->execute()) {
        error_log('save schedule: ' . $stmt->error);
        $back('Could not save the slot.');
    }
    $back();
}
