<?php
session_start();

// Security Guard
if (!isset($_SESSION['admin'])) {
    header("Location: signin.php");
    exit();
}

include __DIR__ . "/db.php";

date_default_timezone_set('Asia/Manila');

/* =========================
   SAFE EXPIRED SCHEDULE CLEANUP ONLY
========================= */
$today = date("Y-m-d");
$now = date("H:i:s");

// Only delete viewing schedules that are strictly in the past
$conn->query("
    DELETE FROM viewing_schedule
    WHERE date < '$today'
       OR (date = '$today' AND time_end < '$now')
");

/* =========================
   FETCH DATA
========================= */
$schedules = $conn->query("
    SELECT * 
    FROM viewing_schedule 
    ORDER BY date ASC, time_start ASC
");

$requests = $conn->query("
    SELECT * 
    FROM viewing_requests 
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Viewing Schedule - Dollar Dreams Dormitory</title>

<style>
body{
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f4f6fa;
}

.container{
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.table-wrapper{
    overflow-x:auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    border:1px solid #e2e8f0;
    padding:10px 8px;
    text-align:center;
    font-size: 14px;
}

th{
    background:#183823;
    color:white;
}

form{
    background:white;
    padding:20px;
    border-radius:8px;
    margin-bottom:25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

input, select{
    padding:8px 12px;
    margin:5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button{
    padding:8px 14px;
    background:#183823;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
    font-weight: bold;
}

.delete-btn{
    background:#e74c3c;
}

.pending{ color: orange; font-weight: bold; }
.done{ color: green; font-weight: bold; }
.cancelled{ color: red; font-weight: bold; }

@media(max-width:768px){
    table{ font-size:12px; }
    th,td{ padding:6px 4px; }
    input, select{ width:100%; margin:5px 0; }
}
</style>
</head>

<body>

<?php include __DIR__ . "/partials/navbar.php"; ?>

<div class="container">

<h2>Viewing Schedule Management</h2>

<!-- ADD SCHEDULE FORM -->
<form method="POST" action="save_schedule.php">
    <input type="text" name="day" placeholder="Day (e.g. Monday)" required>
    <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
    <input type="time" name="time_start" required>
    <input type="time" name="time_end" required>
    <button type="submit">Add Schedule</button>
</form>

<!-- SCHEDULES TABLE -->
<h3>Active Viewing Slots</h3>
<div class="table-wrapper">
<table>
<tr>
    <th>Day</th>
    <th>Date</th>
    <th>Time Slot</th>
    <th>Action</th>
</tr>

<?php if ($schedules && $schedules->num_rows > 0): ?>
    <?php while($s = $schedules->fetch_assoc()): ?>
    <tr id="schedule-row-<?= $s['id'] ?>">
        <td><?= htmlspecialchars($s['day']) ?></td>
        <td><?= date("F d, Y", strtotime($s['date'])) ?></td>
        <td>
            <?= date("h:i A", strtotime($s['time_start'])) ?> - <?= date("h:i A", strtotime($s['time_end'])) ?>
        </td>
        <td>
            <button class="delete-btn" onclick="deleteSchedule(<?= $s['id'] ?>)">Delete</button>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="4">No active viewing slots found.</td></tr>
<?php endif; ?>
</table>
</div>

<!-- VIEWING REQUESTS TABLE -->
<h3>Customer Viewing Requests</h3>
<div class="table-wrapper">
<table>
<tr>
    <th>Name</th>
    <th>Mobile</th>
    <th>Requested Schedule</th>
    <th>Date Requested</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php if ($requests && $requests->num_rows > 0): ?>
    <?php while($r = $requests->fetch_assoc()): ?>
    <?php $statusVal = $r['status'] ?? 'Pending'; ?>
    <tr id="request-row-<?= $r['id'] ?>">
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['mobile']) ?></td>
        <td><?= htmlspecialchars($r['schedule']) ?></td>
        <td><?= date("F d, Y h:i A", strtotime($r['created_at'])) ?></td>
        <td class="<?= strtolower($statusVal) ?>">
            <select onchange="updateViewingStatus(this, <?= $r['id'] ?>)">
                <option value="Pending" <?= $statusVal == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Done" <?= $statusVal == 'Done' ? 'selected' : '' ?>>Done</option>
                <option value="Cancelled" <?= $statusVal == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </td>
        <td>
            <button class="delete-btn" onclick="deleteViewingRequest(<?= $r['id'] ?>)">Delete</button>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="6">No customer viewing requests found.</td></tr>
<?php endif; ?>
</table>
</div>

</div>

<script>
function deleteSchedule(id){
    if(!confirm("Delete schedule slot?")) return;

    fetch("delete_schedule.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id
    })
    .then(res => res.text())
    .then(() => {
        let row = document.getElementById("schedule-row-" + id);
        if(row) row.remove();
    });
}

function deleteViewingRequest(id){
    if(!confirm("Delete viewing request?")) return;

    fetch("delete_viewing_request.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id
    })
    .then(res => res.text())
    .then(() => {
        let row = document.getElementById("request-row-" + id);
        if(row) row.remove();
    });
}

function updateViewingStatus(select, id){
    fetch("update_viewing_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id + "&status=" + encodeURIComponent(select.value)
    });
}
</script>

</body>
</html>