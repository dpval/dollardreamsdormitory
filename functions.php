<?php
/*
 * Shared helpers. Loaded by db.php - don't include it directly.
 *
 * Pricing, booking status and room availability are calculated ONLY here,
 * so the booking page, booking tracker and dashboard always agree.
 */

/* Default rates when a booking has no custom price (price = 0). */
const RATE_DAILY   = 350.00;   // per day,   1-6 days
const RATE_WEEKLY  = 2400.00;  // per week,  7-29 days (extra days charged at RATE_DAILY)
const RATE_MONTHLY = 6500.00;  // per month, 30+ days  (extra days pro-rated)

const MIN_RESERVATION_FEE = 500;

/* Escape text for HTML output. */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Accepts 09XXXXXXXXX or +639XXXXXXXXX (spaces/dashes ignored). Returns cleaned number or null. */
function clean_mobile($mobile) {
    $m = preg_replace('/[\s\-()]/', '', (string)$mobile);
    return preg_match('/^(09\d{9}|\+639\d{9})$/', $m) ? $m : null;
}

/* Returns a DateTime for a valid Y-m-d string, otherwise null. */
function parse_date($value) {
    $d = DateTime::createFromFormat('!Y-m-d', (string)$value);
    return ($d && $d->format('Y-m-d') === $value) ? $d : null;
}

/*
 * The one pricing formula for the whole site.
 * $price = 0 means "use the default rate for this length of stay".
 */
function calculate_booking($moveIn, $moveOut, $price, $payment, $reservationFee) {
    $in  = parse_date($moveIn)  ?: new DateTime('today');
    $out = parse_date($moveOut) ?: clone $in;

    $days = ($out < $in) ? 1 : $in->diff($out)->days + 1;

    if ($days >= 30) {
        $tier = 'monthly';
        $rate = $price > 0 ? (float)$price : RATE_MONTHLY;
        $total = floor($days / 30) * $rate + ($days % 30) * ($rate / 30);
        $termText = floor($days / 30) . ' mo ' . ($days % 30) . ' day(s)';
    } elseif ($days >= 7) {
        $tier = 'weekly';
        $rate = $price > 0 ? (float)$price : RATE_WEEKLY;
        $total = floor($days / 7) * $rate + ($days % 7) * RATE_DAILY;
        $termText = floor($days / 7) . ' wk ' . ($days % 7) . ' day(s)';
    } else {
        $tier = 'daily';
        $rate = $price > 0 ? (float)$price : RATE_DAILY;
        $total = $days * $rate;
        $termText = $days . ' day(s)';
    }

    $total = round($total, 2);
    $paid = (int)$payment === 1;

    // Status
    $today = new DateTime('today');
    if (!$paid) {
        $status = 'PENDING';
    } elseif ($today < $in) {
        $status = 'UPCOMING';
    } elseif ($today <= $out) {
        $status = 'IN';
    } else {
        $status = 'OVERSTAY';
    }

    return [
        'move_in'   => $in->format('Y-m-d'),
        'move_out'  => $out->format('Y-m-d'),
        'days'      => $days,
        'tier'      => $tier,
        'rate'      => $rate,
        'term_text' => $termText,
        'total'     => $total,
        'balance'   => $paid ? 0.0 : max(0, round($total - (float)$reservationFee, 2)),
        'status'    => $status,
    ];
}

/* Bedspace units have "bed" in the room type; capacity is the "-N" at the end of the description. */
function unit_capacity($roomType, $description) {
    if (stripos((string)$roomType, 'bed') === false) {
        return [false, 1];
    }
    $beds = preg_match('/-\s*(\d+)\s*$/', (string)$description, $m) ? max(1, (int)$m[1]) : 1;
    return [true, $beds];
}

/*
 * Availability of every unit TODAY. Only bookings that haven't ended yet count:
 *   paid booking            -> takes a bed/room
 *   unpaid with reservation -> holds a bed/room (reserved)
 * Returns [unit_id => row + is_bedspace, capacity, booked, reserved, available, status].
 */
function get_unit_availability($conn) {
    $today = date('Y-m-d');
    $taken = [];

    $stmt = $conn->prepare("
        SELECT unit_id,
               SUM(payment = 1) AS paid,
               SUM(payment = 0 AND reservation_fee > 0) AS reserved
        FROM bookings
        WHERE move_out >= ?
        GROUP BY unit_id
    ");
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $taken[(int)$r['unit_id']] = [(int)$r['paid'], (int)$r['reserved']];
    }
    $stmt->close();

    $units = [];
    $result = $conn->query("SELECT * FROM units ORDER BY id ASC");
    while ($u = $result->fetch_assoc()) {
        [$isBed, $capacity] = unit_capacity($u['room_type'], $u['description']);
        [$paid, $reserved] = $taken[(int)$u['id']] ?? [0, 0];

        $booked    = min($capacity, $paid);
        $reserved  = min($capacity - $booked, $reserved);
        $available = $capacity - $booked - $reserved;

        $u['is_bedspace'] = $isBed;
        $u['capacity']    = $capacity;
        $u['booked']      = $booked;
        $u['reserved']    = $reserved;
        $u['available']   = $available;
        $u['status']      = $available > 0 ? 'available' : ($booked >= $capacity ? 'booked' : 'reserved');
        $units[(int)$u['id']] = $u;
    }
    return $units;
}

/*
 * Checks an uploaded file by its real contents. Returns the file extension
 * to save it with, or an error message string prefixed with "!".
 */
function check_upload($field, $label, array $allowed, $maxMb) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return "!Please upload your $label.";
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return "!$label could not be uploaded (it may be too large). Please try a smaller file.";
    }
    if ($_FILES[$field]['size'] > $maxMb * 1024 * 1024) {
        return "!$label must be under {$maxMb}MB.";
    }

    $tmp = $_FILES[$field]['tmp_name'];
    $mime = null;
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    } else {
        $img = @getimagesize($tmp);
        if ($img) {
            $mime = $img['mime'];
        } elseif (file_get_contents($tmp, false, null, 0, 5) === '%PDF-') {
            $mime = 'application/pdf';
        }
    }

    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    if (!isset($types[$mime]) || !in_array($types[$mime], $allowed, true)) {
        return "!$label must be a " . strtoupper(implode(', ', $allowed)) . ' file.';
    }
    return $types[$mime];
}

/* Moves a checked upload to $dir under a random name. Returns the saved path or null. */
function save_upload($field, $ext, $dir, $prefix = '') {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = $dir . date('Ymd_His') . '_' . $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
    return move_uploaded_file($_FILES[$field]['tmp_name'], __DIR__ . '/' . $path) ? $path : null;
}

/* Deletes a unit photo from uploads/ unless another unit still uses it. */
function delete_unit_image_if_unused($conn, $path) {
    if (!$path || strpos($path, 'uploads/') !== 0 || strpos($path, '..') !== false) {
        return;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM units WHERE image = ?");
    $stmt->bind_param('s', $path);
    $stmt->execute();
    $inUse = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();

    $file = __DIR__ . '/' . $path;
    if ($inUse === 0 && is_file($file)) {
        unlink($file);
    }
}

/* True once setup.sql has added the payment_proof column. */
function bookings_has_payment_proof($conn) {
    static $has = null;
    if ($has === null) {
        $r = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_proof'");
        $has = $r && $r->num_rows > 0;
    }
    return $has;
}

/* Sends a notification email through Resend. Returns true on success. Never blocks the caller. */
function send_notification_email($subject, $html, $attachments = []) {
    global $config;
    if (empty($config['resend_api_key']) || !function_exists('curl_init')) {
        return false;
    }

    $payload = [
        'from'    => $config['mail_from'],
        'to'      => $config['notify_emails'],
        'subject' => $subject,
        'html'    => $html,
    ];
    if ($attachments) {
        $payload['attachments'] = $attachments;
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $config['resend_api_key'],
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        error_log('Resend cURL error: ' . curl_error($ch));
    } elseif ($code >= 400) {
        error_log("Resend error $code: $response");
    }
    curl_close($ch);
    return $code >= 200 && $code < 300;
}

/* Admin sidebar counts: unpaid bookings that haven't ended, and viewing requests still pending. */
function admin_counts($conn) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM bookings WHERE payment = 0 AND move_out >= ?");
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $bookings = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    $r = $conn->query("SELECT COUNT(*) AS n FROM viewing_requests WHERE status = 'Pending' OR status IS NULL");
    return ['bookings' => $bookings, 'viewings' => $r ? (int)$r->fetch_assoc()['n'] : 0];
}

/* "Juan Dela Cruz" -> "Juan D." for tight labels */
function short_name($name) {
    $parts = preg_split('/\s+/', trim((string)$name));
    if (count($parts) < 2) return $parts[0] ?? '';
    return $parts[0] . ' ' . mb_substr(end($parts), 0, 1) . '.';
}

/* "16A07" -> "16th floor" ; anything else -> "" */
function unit_floor_label($code) {
    if (!preg_match('/^(\d+)\s*[A-Z]/i', (string)$code, $m)) return '';
    $n = (int)$m[1];
    $v = $n % 100;
    $suffix = ($v >= 11 && $v <= 13) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
    return $n . $suffix . ' floor';
}
