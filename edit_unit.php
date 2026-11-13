<?php
ini_set('session.save_path', sys_get_temp_dir());
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: signin.php");
    exit();
}

include "db.php";

$error = "";

// GET UNIT ID
if(!isset($_GET['id'])){
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];

// FETCH EXISTING DATA
$result = $conn->query("SELECT * FROM units WHERE id=$id");
$row = $result->fetch_assoc();

if(!$row){
    header("Location: dashboard.php");
    exit();
}

// UPDATE DATA
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $unit = $_POST['unit_name'];
    $type = $_POST['room_type'];
    $description = $_POST['description'];

    $imagePath = $row['image']; // keep old image by default

    // CHECK IF NEW IMAGE UPLOADED
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

        $allowedTypes = ['image/jpeg','image/png','image/jpg'];
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];

        if(in_array($fileType, $allowedTypes) && $fileSize <= 2 * 1024 * 1024){

            $uploadDir = "uploads/";

            if(!is_dir($uploadDir)){
                mkdir($uploadDir, 0755, true);
            }

            $fileName = time() . "_" . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;

            if(move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)){
                $imagePath = $targetPath;
            }

        } else {
            $error = "Invalid image (JPG/PNG only, max 2MB)";
        }
    }

    // UPDATE QUERY
    $stmt = $conn->prepare("UPDATE units SET unit_name=?, room_type=?, description=?, image=? WHERE id=?");
    $stmt->bind_param("ssssi", $unit, $type, $description, $imagePath, $id);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Unit</title>

<style>
body {
    margin: 0;
    font-family: Arial;
    background: #f4f6fa;

    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background: white;
    padding: 30px;
    width: 350px;
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
}

.container textarea {
    height: 80px;
    resize: none;
}

.container button {
    width: 100%;
    padding: 10px;
    background: #ffc107;
    color: black;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}

.container button:hover {
    background: #e0a800;
}

.preview {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: 10px;
}

.error {
    color: red;
}
</style>

</head>
<body>

<div class="container">

    <img src="image/logo.jpg" class="logo">

    <h2>Edit Unit</h2>

    <!-- CURRENT IMAGE -->
    <img src="<?php echo $row['image']; ?>" class="preview">

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="unit_name" value="<?php echo $row['unit_name']; ?>" required>
        <input type="text" name="room_type" value="<?php echo $row['room_type']; ?>" required>

        <textarea name="description" required><?php echo $row['description']; ?></textarea>

        <input type="file" name="image" accept="image/*">

        <button type="submit">Update Unit</button>
    </form>

    <p class="error"><?php echo $error; ?></p>

</div>

</body>
</html>