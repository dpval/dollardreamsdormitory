<?php
/* Admin: open a guest's government ID or payment proof (these files are not public). */
require_once "auth.php";
require_admin_page();
include "db.php";

$id   = (int)($_GET['id'] ?? 0);
$type = ($_GET['type'] ?? '') === 'proof' ? 'payment_proof' : 'id_upload';

if ($type === 'payment_proof' && !bookings_has_payment_proof($conn)) {
    http_response_code(404);
    exit('Not found');
}

$stmt = $conn->prepare("SELECT `$type` AS path FROM bookings WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$path = $stmt->get_result()->fetch_assoc()['path'] ?? '';
$stmt->close();

// Only serve files that really are inside private_uploads/ (or old ones still in uploads/)
$file = $path ? realpath(__DIR__ . '/' . $path) : false;
$allowedDirs = [realpath(__DIR__ . '/private_uploads'), realpath(__DIR__ . '/uploads')];
$ok = false;
foreach ($allowedDirs as $dir) {
    if ($file && $dir && strpos($file, $dir . DIRECTORY_SEPARATOR) === 0) {
        $ok = true;
    }
}
if (!$ok || !is_file($file)) {
    http_response_code(404);
    exit('File not found');
}

$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: ' . (isset($types[$ext]) ? 'inline' : 'attachment') . '; filename="booking-' . $id . '-' . ($type === 'id_upload' ? 'id' : 'payment') . '.' . $ext . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Length: ' . filesize($file));
readfile($file);
