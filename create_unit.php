<?php
session_start();

// Security Guard: Ensure admin authentication
if (!isset($_SESSION['admin'])) {
    header("Location: signin.php");
    exit();
}

include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $unit = trim($_POST['unit_name']);
    $type = trim($_POST['room_type']);
    $description = trim($_POST['description']);

    // Image Upload Handling
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $fileSize = $_FILES['image']['size'];
        $fileName = $_FILES['image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExt, $allowedExts)) {
            $error = "Only JPG, PNG, and WEBP images are allowed.";
        } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB Limit
            $error = "Image must be less than 2MB.";
        } else {
            $uploadDir = "uploads/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = time() . "_" . uniqid() . "." . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {

                // Safe Prepared Statement
                $stmt = $conn->prepare("INSERT INTO units (unit_name, room_type, description, image) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $unit, $type, $description, $targetPath);
                
                if ($stmt->execute()) {
                    header("Location: manage_units.php");
                    exit();
                } else {
                    $error = "Database Error: " . $stmt->error;
                }

            } else {
                $error = "Failed to upload image file.";
            }
        }

    } else {
        $error = "Please select an image file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Unit - Dollar Dreams Dormitory</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6fa;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.container {
    background: white;
    padding: 30px;
    width: 360px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-align: center;
}

.logo {
    width: 80px;
    margin-bottom: 10px;
}

.container input,
.container textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
}

.container textarea {
    height: 80px;
    resize: none;
}

.container button {
    width: 100%;
    padding: 10px;
    background: #183823;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    font-size: 14px;
    margin-top: 5px;
}

.container button:hover {
    background: #245336;
}

.error {
    color: #e74c3c;
    margin-top: 10px;
    font-size: 13px;
    font-weight: bold;
}
</style>
</head>
<body>

<div class="container">
    <img src="image/logo.jpg" class="logo" alt="Logo">

    <h2>Create Unit</h2>

    <!-- Explicitly set form submission action -->
    <form action="create_unit.php" method="POST" enctype="multipart/form-data">
        <input type="text" name="unit_name" placeholder="Unit Name" required>
        <input type="text" name="room_type" placeholder="Room Type" required>
        <textarea name="description" placeholder="Description" required></textarea>
        <input type="file" name="image" accept="image/*" required>

        <button type="submit">Save Unit</button>
    </form>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>

</body>
</html>