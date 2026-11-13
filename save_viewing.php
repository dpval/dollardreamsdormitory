<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');

    if (empty($name) || empty($mobile) || empty($schedule)) {
        exit("Missing fields");
    }

    $stmt = $conn->prepare("
        INSERT INTO viewing_requests (name, mobile, schedule, status, created_at)
        VALUES (?, ?, ?, 'Pending', NOW())
    ");

    if (!$stmt) {
        exit("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $mobile, $schedule);

    if ($stmt->execute()) {

        // ==========================================
        // RESEND API EMAIL NOTIFICATION (NATIVE cURL)
        // ==========================================
        $resendApiKey = 're_NV2SHUV7_3C4k74YnUcHfqc13NyF7UobU';

        $emailPayload = [
            'from'    => 'Dollar Dreams Dormitory <onboarding@resend.dev>',
            'to'      => ['aldwins121@gmail.com'],
            'subject' => 'New Customer Viewing Request Received',
            'html'    => "
                <h2>New Viewing Request</h2>
                <p>A customer has booked a schedule viewing request on the website.</p>
                <hr style='border: 1px solid #eee;'>
                <p><strong>Customer Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Mobile Number:</strong> " . htmlspecialchars($mobile) . "</p>
                <p><strong>Requested Schedule Slot:</strong> " . htmlspecialchars($schedule) . "</p>
                <p><strong>Status:</strong> Pending</p>
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
        
        if (curl_errno($ch)) {
            error_log('Resend cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        // ONLY ONE SUCCESS ECHO HERE
        echo "success";

    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>