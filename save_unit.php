<?php
ini_set('session.save_path', sys_get_temp_dir());
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $unit = trim($_POST['unit_name'] ?? '');
    $type = trim($_POST['room_type'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($unit) || empty($type)) {
        exit("Unit name and room type are required.");
    }

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

    // Handle array of images or single image
    $files = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $files[] = [
                'name' => $_FILES['images']['name'][$key],
                'type' => $_FILES['images']['type'][$key],
                'tmp_name' => $_FILES['images']['tmp_name'][$key],
                'error' => $_FILES['images']['error'][$key],
                'size' => $_FILES['images']['size'][$key]
            ];
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $files[] = $_FILES['image'];
    }

    if (empty($files)) {
        exit("Please upload at least one image.");
    }

    $stmt = $conn->prepare("INSERT INTO units (unit_name, room_type, description, image) VALUES (?, ?, ?, ?)");

    foreach ($files as $file) {
        if ($file['error'] !== 0) continue;

        if (!in_array($file['type'], $allowedTypes)) {
            exit("Error: Only JPG and PNG images are allowed.");
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            exit("Error: File size must be under 2MB.");
        }

        $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($file['name']));
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt->bind_param("ssss", $unit, $type, $description, $targetPath);
            $stmt->execute();
        }
    }

    $stmt->close();
    echo "success";
}
?>