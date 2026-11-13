<?php
session_start();

// 1. SECURITY GUARD
if (!isset($_SESSION['admin'])) {
    header("Location: signin.php");
    exit();
}

include __DIR__ . "/db.php";

/* =========================
   DASHBOARD KPI STATS
========================= */

// 1. Total Sales (STRICTLY PAID BOOKINGS ONLY: payment = 1)
$sales = $conn->query("
    SELECT SUM(price * (DATEDIFF(move_out, move_in) + 1)) AS total_sales 
    FROM bookings
    WHERE payment = 1
")->fetch_assoc()['total_sales'] ?? 0;

// 2. Sales Breakdown by Term (PAID ONLY)
// Daily Sales (1 to 6 days)
$dailySales = $conn->query("
    SELECT SUM(price * (DATEDIFF(move_out, move_in) + 1)) AS total 
    FROM bookings
    WHERE payment = 1 AND (DATEDIFF(move_out, move_in) + 1) BETWEEN 1 AND 6
")->fetch_assoc()['total'] ?? 0;

// Weekly Sales (7 to 29 days)
$weeklySales = $conn->query("
    SELECT SUM(price * (DATEDIFF(move_out, move_in) + 1)) AS total 
    FROM bookings
    WHERE payment = 1 AND (DATEDIFF(move_out, move_in) + 1) BETWEEN 7 AND 29
")->fetch_assoc()['total'] ?? 0;

// Monthly Sales (30+ days)
$monthlySales = $conn->query("
    SELECT SUM(price * (DATEDIFF(move_out, move_in) + 1)) AS total 
    FROM bookings
    WHERE payment = 1 AND (DATEDIFF(move_out, move_in) + 1) >= 30
")->fetch_assoc()['total'] ?? 0;

// 3. Payment Status Counts
$paidCount = $conn->query("
    SELECT COUNT(*) AS total 
    FROM bookings 
    WHERE payment = 1
")->fetch_assoc()['total'] ?? 0;

$pendingCount = $conn->query("
    SELECT COUNT(*) AS total 
    FROM bookings 
    WHERE payment = 0
")->fetch_assoc()['total'] ?? 0;

// 4. Active Reservations Count (EXCLUDES PAID BOOKINGS TO PREVENT DOUBLE COUNTING)
$reservations = $conn->query("
    SELECT COUNT(*) AS total 
    FROM bookings 
    WHERE reservation_fee > 0 AND payment = 0
")->fetch_assoc()['total'] ?? 0;

// 5. Total Bookings Count
$bookings = $conn->query("
    SELECT COUNT(*) AS total 
    FROM bookings
")->fetch_assoc()['total'] ?? 0;

/* =========================
   MONTHLY REVENUE CHART (PAID ONLY)
========================= */

$salesQuery = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%b %Y') as month_label,
        SUM(price * (DATEDIFF(move_out, move_in) + 1)) as total
    FROM bookings
    WHERE payment = 1
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at) ASC, MONTH(created_at) ASC
");

$salesMonths = [];
$salesTotals = [];

while ($s = $salesQuery->fetch_assoc()) {
    $salesMonths[] = $s['month_label'];
    $salesTotals[] = (float)$s['total'];
}

/* =========================
   DYNAMIC AVAILABILITY ENGINE
========================= */

$bookedQuery = $conn->query("
    SELECT DISTINCT unit_id
    FROM bookings
    WHERE payment = 1
");

$bookedIds = [];
while ($b = $bookedQuery->fetch_assoc()) {
    $bookedIds[] = $b['unit_id'];
}

$unitsQuery = $conn->query("SELECT * FROM units");

$roomAvailable = 0;
$bedAvailable = 0;
$roomCounts = [];

while ($u = $unitsQuery->fetch_assoc()) {
    $type = strtolower($u['room_type']);
    $originalType = $u['room_type'];
    $isBooked = in_array($u['id'], $bookedIds);

    if (strpos($type, 'bed') !== false) {
        preg_match('/-(\d+)$/', $u['description'], $match);
        $capacity = (int)($match[1] ?? 1);

        if ($isBooked) {
            $bedAvailable += max(0, $capacity - 1);
        } else {
            $bedAvailable += $capacity;
        }
    } else {
        if (!$isBooked) {
            $roomAvailable++;
        }
    }

    if (!isset($roomCounts[$originalType])) {
        $roomCounts[$originalType] = 0;
    }

    if (!$isBooked) {
        $roomCounts[$originalType]++;
    }
}

$roomTypes = array_keys($roomCounts);
$roomAvail = array_values($roomCounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dollar Dreams Dormitory</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #183823;
            --primary-light: #245336;
            --accent-green: #2ecc71;
            --bg-gray: #f4f6fa;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
        }

        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            margin: 0; 
            background: var(--bg-gray);
            color: var(--text-dark);
        }

        .container { 
            padding: 30px 20px; 
            max-width: 1350px; 
            margin: 0 auto; 
        }

        .dashboard-header {
            margin-bottom: 25px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 26px;
            color: var(--primary);
            font-weight: 700;
        }

        .dashboard-header p {
            margin: 5px 0 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin: 20px 0 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* METRIC CARDS GRID */
        .cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 18px; 
            margin-bottom: 25px;
        }

        .card { 
            background: white; 
            padding: 20px; 
            border-radius: 14px; 
            box-shadow: var(--card-shadow);
            border-left: 5px solid var(--primary);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        /* CARD COLOR VARIATIONS */
        .card.sales-card { border-left-color: #2ecc71; }
        .card.daily-card { border-left-color: #1abc9c; }
        .card.weekly-card { border-left-color: #3498db; }
        .card.monthly-card { border-left-color: #9b59b6; }
        .card.paid-card { border-left-color: #27ae60; }
        .card.pending-card { border-left-color: #e67e22; }
        .card.reservations-card { border-left-color: #f39c12; }
        .card.bookings-card { border-left-color: #2980b9; }
        .card.rooms-card { border-left-color: #8e44ad; }
        .card.beds-card { border-left-color: #16a085; }

        .card-icon {
            position: absolute;
            right: 15px;
            top: 20px;
            opacity: 0.12;
            width: 42px;
            height: 42px;
        }

        .card-title { 
            margin: 0; 
            color: var(--text-muted); 
            font-size: 12px; 
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-value { 
            margin: 8px 0 0 0; 
            color: var(--text-dark); 
            font-size: 24px; 
            font-weight: 800;
        }

        /* CHART GRID LAYOUT */
        .chartGrid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 25px; 
        }

        .chartBox { 
            background: white; 
            padding: 24px; 
            border-radius: 14px; 
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
        }

        .chartBox.full { 
            grid-column: 1 / -1; 
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 12px;
        }

        .chartBox h3 { 
            margin: 0; 
            color: var(--primary); 
            font-size: 16px; 
            font-weight: 700;
        }

        .chart-container {
            position: relative;
            flex: 1;
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        canvas { 
            width: 100% !important; 
            max-height: 320px; 
        }

        @media(max-width: 992px) {
            .chartGrid { 
                grid-template-columns: 1fr; 
            }
            .chartBox.full { 
                grid-column: auto; 
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . "/partials/navbar.php"; ?>

<div class="container">
    
    <div class="dashboard-header">
        <h1>Overview & Financial Analytics</h1>
        <p>Real-time revenue, booking statuses, and occupancy metrics for Dollar Dreams Dormitory</p>
    </div>

    <!-- REVENUE KPI CARDS -->
    <div class="section-title">Revenue & Term Breakdown</div>
    <div class="cards">
        <div class="card sales-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>
            <p class="card-title">Total Sales Revenue (Paid)</p>
            <p class="card-value">₱<?= number_format($sales, 2) ?></p>
        </div>

        <div class="card daily-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            <p class="card-title">Daily Sales (1–6 Days)</p>
            <p class="card-value">₱<?= number_format($dailySales, 2) ?></p>
        </div>

        <div class="card weekly-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
            <p class="card-title">Weekly Sales (7–29 Days)</p>
            <p class="card-value">₱<?= number_format($weeklySales, 2) ?></p>
        </div>

        <div class="card monthly-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
            <p class="card-title">Monthly Sales (30+ Days)</p>
            <p class="card-value">₱<?= number_format($monthlySales, 2) ?></p>
        </div>
    </div>

    <!-- BOOKING & OCCUPANCY CARDS -->
    <div class="section-title">Occupancy & Status Metrics</div>
    <div class="cards">
        <div class="card paid-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            <p class="card-title">Paid Bookings</p>
            <p class="card-value"><?= number_format($paidCount) ?></p>
        </div>

        <div class="card pending-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>
            <p class="card-title">Pending Bookings</p>
            <p class="card-value"><?= number_format($pendingCount) ?></p>
        </div>

        <div class="card reservations-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
            <p class="card-title">Active Reservations</p>
            <p class="card-value"><?= number_format($reservations) ?></p>
        </div>

        <div class="card bookings-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            <p class="card-title">Total Bookings</p>
            <p class="card-value"><?= number_format($bookings) ?></p>
        </div>

        <div class="card rooms-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v10h2v-3h6v3h2V9c0-1.1-.9-2-2-2z"/></svg>
            <p class="card-title">Available Rooms</p>
            <p class="card-value"><?= number_format($roomAvailable) ?></p>
        </div>

        <div class="card beds-card">
            <svg class="card-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M20 9.55D11.97 11.97 0 0 0 12 6c-3.48 0-6.64 1.48-8.95 3.85L1 7.6 12 1.5l11 6.1-2 1.95zM12 8c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/></svg>
            <p class="card-title">Available Beds</p>
            <p class="card-value"><?= number_format($bedAvailable) ?></p>
        </div>
    </div>

    <!-- ANALYTICS CHARTS -->
    <div class="chartGrid">
        <div class="chartBox">
            <div class="chart-header">
                <h3>Available Rooms by Category</h3>
            </div>
            <div class="chart-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <div class="chartBox">
            <div class="chart-header">
                <h3>Room Type Distribution</h3>
            </div>
            <div class="chart-container">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <div class="chartBox full">
            <div class="chart-header">
                <h3>Monthly Paid Revenue Overview</h3>
            </div>
            <div class="chart-container">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script>
const labels = <?= json_encode($roomTypes) ?>;
const roomData = <?= json_encode($roomAvail) ?>;
const salesLabels = <?= json_encode($salesMonths) ?>;
const salesData = <?= json_encode($salesTotals) ?>;

const modernColors = ['#183823', '#2ecc71', '#3498db', '#f39c12', '#9b59b6', '#e74c3c', '#1abc9c'];

Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.color = '#7f8c8d';

// Bar Chart
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Available Units',
            data: roomData,
            backgroundColor: '#183823',
            hoverBackgroundColor: '#245336',
            borderRadius: 6,
            maxBarThickness: 40
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { display: false },
            tooltip: { cornerRadius: 8, padding: 10 }
        },
        scales: { 
            y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});

// Pie Chart
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: labels,
        datasets: [{ 
            data: roomData, 
            backgroundColor: modernColors,
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: { 
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
        },
        cutout: '65%'
    }
});

// Line Chart
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'Paid Revenue (₱)',
            data: salesData,
            borderColor: '#183823',
            borderWidth: 3,
            backgroundColor: (context) => {
                const ctx = context.chart.ctx;
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(24, 56, 35, 0.25)');
                gradient.addColorStop(1, 'rgba(24, 56, 35, 0.0)');
                return gradient;
            },
            pointBackgroundColor: '#2ecc71',
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { cornerRadius: 8, padding: 10 }
        },
        scales: { 
            y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

</body>
</html>