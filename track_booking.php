<?php
session_start();

// Security guard: Ensure only logged-in admins can access the tracker
if (!isset($_SESSION['admin'])) {
    header("Location: signin.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("db.php");

/* =========================
   GET ALL BOOKINGS
========================= */
$bookings = $conn->query("
    SELECT * 
    FROM bookings 
    ORDER BY id DESC
");

/* =========================
   GET LONG TERM CLIENTS (>= 30 DAYS)
========================= */
$longTerm = $conn->query("
    SELECT * 
    FROM bookings
    WHERE DATEDIFF(move_out, move_in) >= 29
    ORDER BY id DESC
");

/* Helper function for PHP calculation */
function calculateBookingDetails($moveInDate, $moveOutDate, $customPrice = null) {
    if (empty($moveInDate) || $moveInDate == '0000-00-00') $moveInDate = date('Y-m-d');
    if (empty($moveOutDate) || $moveOutDate == '0000-00-00') $moveOutDate = date('Y-m-d');

    $moveIn = new DateTime($moveInDate);
    $moveOut = new DateTime($moveOutDate);
    
    $days = $moveIn->diff($moveOut)->days + 1;
    if ($days < 1) $days = 1;

    // Determine tier & base price if price is not overridden
    if ($days >= 30) {
        $tier = 'monthly';
        $defaultPrice = 6500.00;
        $termText = floor($days / 30) . " mo " . ($days % 30) . " day(s)";
    } elseif ($days >= 7) {
        $tier = 'weekly';
        $defaultPrice = 2400.00;
        $termText = floor($days / 7) . " wk " . ($days % 7) . " day(s)";
    } else {
        $tier = 'daily';
        $defaultPrice = 350.00;
        $termText = $days . " day(s)";
    }

    $price = ($customPrice !== null && floatval($customPrice) > 0) ? floatval($customPrice) : $defaultPrice;

    // Compute total based on rate strategy
    if ($tier === 'monthly') {
        $months = floor($days / 30);
        $remDays = $days % 30;
        $total = ($months * $price) + ($remDays * ($price / 30));
    } elseif ($tier === 'weekly') {
        $weeks = floor($days / 7);
        $remDays = $days % 7;
        $total = ($weeks * $price) + ($remDays * 350.00);
    } else {
        $total = $days * $price;
    }

    return [
        'moveInDate' => $moveInDate,
        'moveOutDate' => $moveOutDate,
        'days' => $days,
        'tier' => $tier,
        'termText' => $termText,
        'price' => $price,
        'total' => $total,
        'moveIn' => $moveIn,
        'moveOut' => $moveOut
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Tracker - Dollar Dreams Dormitory</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f4f6fa;
}

.container {
    padding: 20px;
    max-width: 100%;
    margin: 0 auto;
}

/* FILTER BAR STYLING */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.filter-bar label {
    font-weight: bold;
    font-size: 14px;
    color: #183823;
}

.filter-bar select {
    width: auto;
    min-width: 180px;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
}

.table-wrapper {
    overflow-x: auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

th, td {
    border: 1px solid #e2e8f0;
    padding: 8px 10px;
    text-align: center;
    font-size: 13px;
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

td input[type="date"] {
    padding: 5px 6px;
    min-width: 125px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
}

td select.paymentStatus {
    padding: 5px 6px;
    min-width: 90px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
}

.price-container {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-width: 120px;
}

.price-container input.input-price {
    width: 80px;
    padding: 5px 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
    box-sizing: border-box;
}

.rate-label {
    font-size: 11px;
    color: #666;
    display: block;
}

.quick-save-btn {
    background: #2ecc71;
    color: white;
    padding: 5px 8px;
    font-size: 13px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    line-height: 1;
}

.quick-save-btn:hover {
    background: #27ae60;
}

button {
    padding: 6px 10px;
    background: #183823;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    font-size: 12px;
    white-space: nowrap;
}

button:hover {
    background: #245336;
}

.delete-btn {
    background: #e74c3c;
    margin-top: 3px;
}

.delete-btn:hover {
    background: #c0392b;
}

.status-in { color: #27ae60; font-weight: bold; }
.status-out { color: #c0392b; font-weight: bold; }
.status-pending { color: #d35400; font-weight: bold; }
.status-upcoming { color: #2980b9; font-weight: bold; }

@media(max-width: 768px) {
    th, td { padding: 6px 4px; }
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-bar select {
        width: 100%;
    }
}
</style>
</head>

<body>

<?php include("partials/navbar.php"); ?>

<div class="container">

<h2>Booking Tracker</h2>

<!-- TERM FILTER CONTROL -->
<div class="filter-bar">
    <label for="termFilter">Filter by Term Duration:</label>
    <select id="termFilter" onchange="filterBookings()">
        <option value="all">All Bookings</option>
        <option value="daily">Daily (1 - 6 Days)</option>
        <option value="weekly">Weekly (7 - 29 Days)</option>
        <option value="monthly">Monthly (30+ Days)</option>
    </select>
</div>

<div class="table-wrapper">
<table id="mainBookingsTable">
<thead>
<tr>
    <th>Unit</th>
    <th>Room Type</th>
    <th>Description</th>
    <th>Name</th>
    <th>Mobile</th>
    <th>Move In</th>
    <th>Move Out</th>
    <th>Rate (₱)</th>
    <th>Term</th>
    <th>Total</th>
    <th>Reservation</th>
    <th>Balance</th>
    <th>Payment</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php if ($bookings && $bookings->num_rows > 0): ?>
    <?php while($booking = $bookings->fetch_assoc()): ?>

    <?php
    $calc = calculateBookingDetails($booking['move_in'], $booking['move_out'], $booking['price']);
    
    $reservation = floatval($booking['reservation_fee']);
    $balance = ($booking['payment'] == 1) ? 0 : max(0, $calc['total'] - $reservation);

    $today = new DateTime('today');
    $status = "PENDING";
    $statusClass = "status-pending";

    if($booking['payment'] == 1){
        if($today > $calc['moveOut']){
            $status = "OVERSTAY";
            $statusClass = "status-out";
        }
        elseif($today >= $calc['moveIn'] && $today <= $calc['moveOut']){
            $status = "IN";
            $statusClass = "status-in";
        }
        else{
            $status = "UPCOMING";
            $statusClass = "status-upcoming";
        }
    }
    ?>

    <tr class="booking-row" 
        data-id="<?= $booking['id'] ?>" 
        data-term="<?= $calc['days'] ?>" 
        data-reservation="<?= $reservation ?>"
        data-user-edited="false">
        
        <td><?= htmlspecialchars($booking['unit_name']) ?></td>
        <td><?= htmlspecialchars($booking['room_type']) ?></td>
        <td><?= htmlspecialchars($booking['description']) ?></td>
        <td><?= htmlspecialchars($booking['name']) ?></td>
        <td><?= htmlspecialchars($booking['mobile']) ?></td>

        <td><input type="date" class="input-move-in" value="<?= $calc['moveInDate'] ?>" onchange="onDateChange(<?= $booking['id'] ?>)"></td>
        <td><input type="date" class="input-move-out" value="<?= $calc['moveOutDate'] ?>" onchange="onDateChange(<?= $booking['id'] ?>)"></td>

        <td>
            <div class="price-container">
                <input type="number" step="0.01" class="input-price" value="<?= $calc['price'] ?>" oninput="onPriceManualEdit(<?= $booking['id'] ?>)">
                <button type="button" class="quick-save-btn" title="Quick Save Price & Recalculate" onclick="quickSavePrice(<?= $booking['id'] ?>)">✔</button>
            </div>
            <span class="rate-label cell-rate-type">/<?= $calc['tier'] == 'monthly' ? 'mo' : ($calc['tier'] == 'weekly' ? 'wk' : 'day') ?></span>
        </td>

        <td class="cell-term"><?= $calc['termText'] ?></td>

        <td class="cell-total">₱<?= number_format($calc['total'], 2) ?></td>
        <td>₱<?= number_format($reservation, 2) ?></td>
        <td class="cell-balance">₱<?= number_format($balance, 2) ?></td>

        <td>
            <select class="paymentStatus" onchange="recalculateRow(<?= $booking['id'] ?>)">
                <option value="0" <?= $booking['payment'] == 0 ? 'selected' : '' ?>>Pending</option>
                <option value="1" <?= $booking['payment'] == 1 ? 'selected' : '' ?>>Paid</option>
            </select>
        </td>

        <td class="<?= $statusClass ?>">
            <?= $status ?>
        </td>

        <td>
            <button onclick="updateBooking(<?= $booking['id'] ?>)">Mass Update</button>
            <button class="delete-btn" onclick="deleteBooking(<?= $booking['id'] ?>)">Delete</button>
        </td>
    </tr>

    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="15">No bookings recorded yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- =========================
   LONG TERM CLIENTS
========================= -->
<h2>Long Term Clients (30+ Days)</h2>

<div class="table-wrapper">
<table id="longTermTable">
<thead>
<tr>
    <th>Name</th>
    <th>Mobile</th>
    <th>Move In</th>
    <th>Move Out</th>
    <th>Term</th>
    <th>Total</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php if ($longTerm && $longTerm->num_rows > 0): ?>
    <?php while($lt = $longTerm->fetch_assoc()): ?>

    <?php
    $ltCalc = calculateBookingDetails($lt['move_in'], $lt['move_out'], $lt['price']);
    ?>

    <tr class="longterm-row" data-term="<?= $ltCalc['days'] ?>">
        <td><?= htmlspecialchars($lt['name']) ?></td>
        <td><?= htmlspecialchars($lt['mobile']) ?></td>
        <td><?= $ltCalc['moveInDate'] ?></td>
        <td><?= $ltCalc['moveOutDate'] ?></td>
        <td><?= $ltCalc['termText'] ?></td>
        <td>₱<?= number_format($ltCalc['total'], 2) ?></td>
        <td style="color:#2980b9; font-weight:bold;">LONG TERM</td>
    </tr>

    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="7">No long-term clients recorded.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

</div>

<script>
// Filter Rows dynamically by term range
function filterBookings() {
    let filter = document.getElementById("termFilter").value;

    let rows = document.querySelectorAll(".booking-row");
    rows.forEach(row => {
        let term = parseInt(row.getAttribute("data-term")) || 0;

        if (filter === "all") {
            row.style.display = "";
        } else if (filter === "daily" && term >= 1 && term <= 6) {
            row.style.display = "";
        } else if (filter === "weekly" && term >= 7 && term <= 29) {
            row.style.display = "";
        } else if (filter === "monthly" && term >= 30) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });

    let ltRows = document.querySelectorAll(".longterm-row");
    ltRows.forEach(row => {
        if (filter === "all" || filter === "monthly") {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

// User manually changed the date range -> auto-switch tiers unless manually overridden
function onDateChange(id) {
    let row = document.querySelector('tr[data-id="'+id+'"]');
    if (!row) return;

    // Reset auto-price strategy on date change
    row.dataset.userEdited = "false";
    recalculateRow(id);
}

// User manually edits price input field
function onPriceManualEdit(id) {
    let row = document.querySelector('tr[data-id="'+id+'"]');
    if (!row) return;

    row.dataset.userEdited = "true";
    recalculateRow(id);
}

// Recalculate row UI totals with tiered logic
function recalculateRow(id) {
    let row = document.querySelector('tr[data-id="'+id+'"]');
    if (!row) return;

    let moveIn = row.querySelector('.input-move-in').value;
    let moveOut = row.querySelector('.input-move-out').value;
    let priceInput = row.querySelector('.input-price');
    let payment = parseInt(row.querySelector('.paymentStatus').value) || 0;
    let reservation = parseFloat(row.dataset.reservation) || 0;
    let isUserEdited = row.dataset.userEdited === "true";

    let days = 1;
    if (moveIn && moveOut) {
        let d1 = new Date(moveIn);
        let d2 = new Date(moveOut);
        let diffTime = d2 - d1;
        days = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
        if (days < 1) days = 1;
    }

    // Tier Identification
    let tier = "daily";
    let defaultPrice = 350;
    let termText = days + " day(s)";
    let rateUnit = "/day";

    if (days >= 30) {
        tier = "monthly";
        defaultPrice = 6500;
        let months = Math.floor(days / 30);
        let remDays = days % 30;
        termText = months + " mo " + remDays + " day(s)";
        rateUnit = "/mo";
    } else if (days >= 7) {
        tier = "weekly";
        defaultPrice = 2400;
        let weeks = Math.floor(days / 7);
        let remDays = days % 7;
        termText = weeks + " wk " + remDays + " day(s)";
        rateUnit = "/wk";
    }

    // Apply tier pricing unless modified by user manually
    if (!isUserEdited) {
        priceInput.value = defaultPrice.toFixed(2);
    }

    let activePrice = parseFloat(priceInput.value) || 0;
    let total = 0;

    // Computation Logic
    if (tier === "monthly") {
        let months = Math.floor(days / 30);
        let remDays = days % 30;
        total = (months * activePrice) + (remDays * (activePrice / 30));
    } else if (tier === "weekly") {
        let weeks = Math.floor(days / 7);
        let remDays = days % 7;
        total = (weeks * activePrice) + (remDays * 350);
    } else {
        total = days * activePrice;
    }

    let balance = (payment === 1) ? 0 : Math.max(0, total - reservation);

    // Update DOM
    row.setAttribute("data-term", days);
    row.querySelector('.cell-rate-type').innerText = rateUnit;
    row.querySelector('.cell-term').innerText = termText;
    row.querySelector('.cell-total').innerText = "₱" + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    row.querySelector('.cell-balance').innerText = "₱" + balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    filterBookings();
}

// Quick Save Button
function quickSavePrice(id) {
    let row = document.querySelector('tr[data-id="'+id+'"]');
    recalculateRow(id);

    let move_in = row.querySelector('.input-move-in').value;
    let move_out = row.querySelector('.input-move-out').value;
    let price = row.querySelector('.input-price').value;
    let payment = row.querySelector('.paymentStatus').value;

    fetch("update_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id) +
              "&move_in=" + encodeURIComponent(move_in) +
              "&move_out=" + encodeURIComponent(move_out) +
              "&price=" + encodeURIComponent(price) +
              "&payment=" + encodeURIComponent(payment)
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === "success") {
            alert("Price updated and recalculated successfully!");
        } else {
            alert("Update Error: " + data);
        }
    })
    .catch(err => alert("Network communication error."));
}

// Mass Update Button
function updateBooking(id){
    let row = document.querySelector('tr[data-id="'+id+'"]');

    let move_in = row.querySelector('.input-move-in').value;
    let move_out = row.querySelector('.input-move-out').value;
    let price = row.querySelector('.input-price').value;
    let payment = row.querySelector('.paymentStatus').value;

    fetch("update_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id) +
              "&move_in=" + encodeURIComponent(move_in) +
              "&move_out=" + encodeURIComponent(move_out) +
              "&price=" + encodeURIComponent(price) +
              "&payment=" + encodeURIComponent(payment)
    })
    .then(res => res.text())
    .then(data => {
        if(data.trim() === "success"){
            alert("Booking updated successfully!");
            location.reload();
        } else {
            alert("Update Error: " + data);
        }
    })
    .catch(err => alert("Network communication error."));
}

function deleteBooking(id){
    if(!confirm("Are you sure you want to permanently delete this booking from the database?")) return;

    fetch("delete_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
    })
    .then(res => res.text())
    .then(data => {
        let response = data.trim();

        if (response === "deleted") {
            let row = document.querySelector('tr[data-id="'+id+'"]');
            if(row) row.remove();

            alert("Booking permanently deleted from database!");
            location.reload();
        } else {
            alert("Deletion failed! Server response: " + response);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Server request failed.");
    });
}
</script>

</body>
</html>