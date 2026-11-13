<?php
session_start();

// Security Guard: Ensure only authenticated admins can access this page
if (!isset($_SESSION['admin'])) {
    header("Location: signin.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . "/db.php";

/* =========================
   GET UNITS
========================= */
$units = $conn->query("
    SELECT * 
    FROM units 
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Units - Dollar Dreams Dormitory</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f4f6fa;
}

.container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.top-bar {
    margin-bottom: 15px;
}

.btn-create {
    display: inline-block;
    padding: 8px 14px;
    background: #183823;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
}

.btn-create:hover {
    background: #245336;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

th, td {
    border: 1px solid #e2e8f0;
    padding: 10px;
    text-align: center;
    font-size: 14px;
    vertical-align: middle;
}

th {
    background: #183823;
    color: white;
    font-weight: 600;
}

tr:nth-child(even) {
    background-color: #f8fafc;
}

img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #ddd;
}

button {
    padding: 6px 12px;
    background: #183823;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
}

button:hover {
    background: #245336;
}

.btn-delete {
    background: #e74c3c;
}

.btn-delete:hover {
    background: #c0392b;
}

a {
    text-decoration: none;
}
</style>

</head>

<body>

<?php include __DIR__ . "/partials/navbar.php"; ?>

<div class="container">

<h2>Manage Units</h2>

<div class="top-bar">
    <a href="create_unit.php" class="btn-create">+ Create Unit</a>
</div>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Unit Name</th>
    <th>Room Type</th>
    <th>Description</th>
    <th>Image</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php if ($units && $units->num_rows > 0): ?>
    <?php while($u = $units->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($u['id']) ?></td>
        <td><?= htmlspecialchars($u['unit_name']) ?></td>
        <td><?= htmlspecialchars($u['room_type']) ?></td>
        <td><?= htmlspecialchars($u['description']) ?></td>

        <td>
            <?php if (!empty($u['image'])): ?>
                <img src="<?= htmlspecialchars($u['image']) ?>" alt="Unit Image">
            <?php else: ?>
                <span style="color: #888; font-size: 12px;">No Image</span>
            <?php endif; ?>
        </td>

        <td>
            <a href="edit_unit.php?id=<?= $u['id'] ?>">
                <button type="button">Edit</button>
            </a>

            <a href="delete_unit.php?id=<?= $u['id'] ?>" 
               onclick="return confirm('Are you sure you want to delete this unit?')">
                <button type="button" class="btn-delete">Delete</button>
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="6">No units found in database.</td>
    </tr>
<?php endif; ?>
</tbody>
</table>

</div>

</body>
</html>