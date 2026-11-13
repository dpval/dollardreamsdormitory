<?php
include "db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $unit_id = intval($_POST['unit_id'] ?? 0);
    $unit_name = $_POST['unit'] ?? '';
    $room_type = $_POST['book_type'] ?? '';
    $description = $_POST['description'] ?? '';

    $name = $_POST['name'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $email = $_POST['email'] ?? '';

    $price = floatval($_POST['price'] ?? 0);
    $reservation_fee = floatval($_POST['reservation_fee'] ?? 500);

    $move_in = $_POST['move_in'] ?? '';
    $move_out = $_POST['move_out'] ?? '';

    $payment = 0;
    $status = "PENDING";

    // TERM CALCULATION
    $term = 1;
    if ($move_in && $move_out) {
        $d1 = new DateTime($move_in);
        $d2 = new DateTime($move_out);
        $term = $d1->diff($d2)->days + 1;
    }

    $total = $price * $term;
    $balance = $total;

    // FILE UPLOAD HANDLING
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 1. Government ID Upload
    $id_upload = "";
    if (!empty($_FILES['id_upload']['name']) && $_FILES['id_upload']['error'] === 0) {
        $id_upload = $uploadDir . time() . "_id_" . basename($_FILES['id_upload']['name']);
        move_uploaded_file($_FILES['id_upload']['tmp_name'], $id_upload);
    }

    // 2. GCash Payment Proof Upload
    $payment_proof = "";
    if (!empty($_FILES['payment_proof']['name']) && $_FILES['payment_proof']['error'] === 0) {
        $payment_proof = $uploadDir . time() . "_pay_" . basename($_FILES['payment_proof']['name']);
        move_uploaded_file($_FILES['payment_proof']['tmp_name'], $payment_proof);
    }

    // PREPARE DATABASE INSERT STATEMENT
    $stmt = $conn->prepare("
        INSERT INTO bookings (
            unit_id, unit_name, room_type, description,
            name, mobile, email,
            id_upload,
            move_in, move_out,
            term, reservation_fee,
            price, payment, total, balance,
            status, created_at
        )
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
    ");

    $stmt->bind_param(
        "isssssssssiddidds",
        $unit_id,
        $unit_name,
        $room_type,
        $description,
        $name,
        $mobile,
        $email,
        $id_upload,
        $move_in,
        $move_out,
        $term,
        $reservation_fee,
        $price,
        $payment,
        $total,
        $balance,
        $status
    );

    if ($stmt->execute()) {

        // ==========================================
        // RESEND API EMAIL NOTIFICATION (NATIVE cURL)
        // ==========================================
        $resendApiKey = 're_NV2SHUV7_3C4k74YnUcHfqc13NyF7UobU';

        // Prepare File Attachments for Resend API
        $attachments = [];

        if (!empty($id_upload) && file_exists($id_upload)) {
            $attachments[] = [
                'filename' => basename($id_upload),
                'content'  => base64_encode(file_get_contents($id_upload)),
            ];
        }

        if (!empty($payment_proof) && file_exists($payment_proof)) {
            $attachments[] = [
                'filename' => basename($payment_proof),
                'content'  => base64_encode(file_get_contents($payment_proof)),
            ];
        }

        // IMPORTANT FOR TESTING MODE (onboarding@resend.dev):
        // Resend BLOCKS sending to multiple or unverified emails.
        // You MUST only include your account owner email address here until a domain is verified.
        $recipients = ['aldwins121@gmail.com'];

        $emailPayload = [
            'from'        => 'Dollar Dreams Dormitory <onboarding@resend.dev>',
            'to'          => $recipients,
            'subject'     => 'New Booking & Reservation Request Received',
            'attachments' => $attachments,
            'html'        => "
                <h2>New Room Reservation Details</h2>
                <p><strong>Full Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Mobile:</strong> " . htmlspecialchars($mobile) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Unit Name:</strong> " . htmlspecialchars($unit_name) . "</p>
                <p><strong>Room Type:</strong> " . htmlspecialchars($room_type) . "</p>
                <p><strong>Reservation Fee:</strong> ₱" . number_format($reservation_fee, 2) . "</p>
                <p><strong>Move-In Date:</strong> " . htmlspecialchars($move_in) . "</p>
                <p><strong>Move-Out Date:</strong> " . htmlspecialchars($move_out) . "</p>
                <p><strong>Total Term:</strong> {$term} day(s)</p>
                <p><strong>Description:</strong> " . htmlspecialchars($description) . "</p>
                <br>
                <p><em>Government ID and Payment Proof are attached to this email.</em></p>
            "
        ];

        // Execute cURL Request
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailPayload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $resendApiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Debugging Resend response if not HTTP 200/201 Success
        if ($httpCode >= 400) {
            echo "Email Error: " . $response;
        } else {
            echo "success";
        }

    } else {
        echo $stmt->error;
    }

    $stmt->close();
}
?>