<?php
/* Admin home: who is in which bed this week, what is free today, and what is waiting on you. */
require_once "auth.php";
require_admin_page();
include "db.php";

$todayStr = date('Y-m-d');
$today    = new DateTime('today');

/* ---------- bookings, priced by the one formula ---------- */
$bookings = $conn->query("SELECT * FROM bookings ORDER BY move_in ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
foreach ($bookings as &$b) {
    $b['calc'] = calculate_booking($b['move_in'], $b['move_out'], $b['price'], $b['payment'], $b['reservation_fee']);
}
unset($b);

$waiting = array_values(array_filter($bookings, fn($b) => (int)$b['payment'] === 0 && $b['move_out'] >= $todayStr));
$waitingBalance = array_sum(array_map(fn($b) => $b['calc']['balance'], $waiting));
$staying = array_filter($bookings, fn($b) => $b['calc']['status'] === 'IN');
$pastOut = array_filter($bookings, fn($b) => $b['calc']['status'] === 'OVERSTAY' && $b['move_out'] >= date('Y-m-d', strtotime('-30 days')));

/* paid revenue by the month the booking was made, last 6 months, current month last */
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("first day of -$i month"));
    $months[$ym] = 0.0;
}
$byTier = ['daily' => 0.0, 'weekly' => 0.0, 'monthly' => 0.0];
foreach ($bookings as $b) {
    if ((int)$b['payment'] !== 1) continue;
    $ym = substr((string)$b['created_at'], 0, 7);
    if (isset($months[$ym])) $months[$ym] += $b['calc']['total'];
    $byTier[$b['calc']['tier']] += $b['calc']['total'];
}
$paidThisMonth = end($months);

/* ---------- today's beds and rooms ---------- */
$units = get_unit_availability($conn);
$freeToday = array_sum(array_column($units, 'available'));
$capacity  = array_sum(array_column($units, 'capacity'));

/* ---------- viewing requests still pending ---------- */
$viewings = $conn->query("SELECT * FROM viewing_requests WHERE status = 'Pending' OR status IS NULL ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

/* ---------- the occupancy timeline: 3 days back, 18 ahead ---------- */
$start = (clone $today)->modify('-3 days');
$DAYS = 21;
$days = []; $weekends = [];
for ($i = 0; $i < $DAYS; $i++) {
    $d = (clone $start)->modify("+$i days");
    $days[] = ['label' => $d->format('D j'), 'short' => $d->format('j')];
    if ((int)$d->format('N') >= 6) $weekends[] = $i;
}
$end = (clone $start)->modify('+' . ($DAYS - 1) . ' days')->format('Y-m-d');
$offset = fn($date) => (int)$start->diff(new DateTime($date))->format('%r%a');

$rows = [];
$lastCode = null;
$byUnit = [];
foreach ($bookings as $b) {
    if ($b['move_out'] < $start->format('Y-m-d') || $b['move_in'] > $end) continue;
    $byUnit[(int)$b['unit_id']][] = $b;
}
$sortedUnits = $units;
uasort($sortedUnits, fn($a, $b) => [$a['unit_name'], $a['id']] <=> [$b['unit_name'], $b['id']]);
foreach ($sortedUnits as $u) {
    if ($u['unit_name'] !== $lastCode) {
        $lastCode = $u['unit_name'];
        $rows[] = ['group' => trim($lastCode . ' · ' . unit_floor_label($lastCode), ' ·')];
    }
    // a bedspace gets one row per bed; bookings go to the first bed that is free on their move-in day
    $beds = array_fill(0, $u['capacity'], []);
    foreach ($byUnit[(int)$u['id']] ?? [] as $b) {
        $kind = (int)$b['payment'] === 1
            ? ($b['calc']['status'] === 'OVERSTAY' ? 'over' : 'paid')
            : ((float)$b['reservation_fee'] > 0 ? 'reserved' : 'unpaid');
        $bar = ['from' => $offset($b['move_in']), 'to' => $offset($b['move_out']), 'kind' => $kind,
                'label' => short_name($b['name']), 'note' => $b['calc']['term_text'] . ', ₱' . number_format($b['calc']['total']), 'id' => (int)$b['id']];
        $placed = false;
        foreach ($beds as $k => $list) {
            $clash = false;
            foreach ($list as $o) if (!($bar['from'] > $o['to'] || $bar['to'] < $o['from'])) $clash = true;
            if (!$clash) { $beds[$k][] = $bar; $placed = true; break; }
        }
        if (!$placed) $beds[count($beds) - 1][] = $bar;   // more bookings than beds: show it rather than hide it
    }
    foreach ($beds as $k => $list) {
        $rows[] = [
            'label' => $u['is_bedspace'] ? 'Bed ' . ($k + 1) : $u['room_type'],
            'sub'   => $u['is_bedspace'] ? ($k === 0 ? $u['room_type'] . ', ' . $u['capacity'] . ' beds' : '') : $u['description'],
            'bars'  => $list,
        ];
    }
}
$rows = array_map(fn($r) => isset($r['sub']) && $r['sub'] === '' ? array_diff_key($r, ['sub' => 1]) : $r, $rows);

/* ---------- the one finding ---------- */
$parts = [];
$parts[] = "<b>$freeToday of $capacity</b> beds and rooms free today";
if ($waiting) $parts[] = '<b>' . count($waiting) . ' unpaid</b> ' . (count($waiting) === 1 ? 'booking' : 'bookings') . ', ₱' . number_format($waitingBalance) . ' to collect';
if ($pastOut) $parts[] = '<b>' . count($pastOut) . '</b> paid ' . (count($pastOut) === 1 ? 'guest' : 'guests') . ' past move-out';
$finding = implode(' · ', $parts);

$page = 'dashboard';
$title = 'Today';
$navCounts = admin_counts($conn);
include __DIR__ . '/partials/admin_top.php';
?>
<style>
.overview { display: grid; grid-template-columns: minmax(0, 1fr) 384px; gap: 24px; align-items: start; }
.main { display: grid; gap: 16px; min-width: 0; }
.rail h2 { font-size: 16px; font-weight: 600; margin: 0 0 8px; }
.rail .panel + h2 { margin-top: 16px; }
.rail .panel { padding: 4px 16px 12px; }
.rail .row { min-height: 45px; }
.rail .row > div { min-width: 0; flex: 1; }
.rail .who { font-weight: 550; }
.rail .what { display: block; color: var(--muted); font-size: var(--fs-small); }
.rail .amount { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
.rail .foot { margin-top: 8px; }
.tiers { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--rule); }
.tiers span { display: block; color: var(--muted); font-size: var(--fs-small); }
.tiers b { font-weight: 550; font-variant-numeric: tabular-nums; }
.timeline-note { margin-top: 10px; color: var(--muted); font-size: var(--fs-small); }
@media (max-width: 1100px) { .overview { grid-template-columns: minmax(0, 1fr); } }
</style>

<div class="title-row">
  <div>
    <h1>Today at Dollar Dreams</h1>
    <p class="finding"><?= $finding ?></p>
  </div>
  <a class="btn primary" href="track_booking.php?tab=pending">Collect payments<?= $waiting ? ' (' . count($waiting) . ')' : '' ?></a>
</div>

<div class="overview">
  <div class="main">

    <div class="kpis">
      <div class="kpi">
        <div class="kpi-label">Free today</div>
        <div class="kpi-value num"><?= $freeToday ?><small>of <?= $capacity ?></small></div>
        <div class="delta">beds and rooms, all units</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Staying now</div>
        <div class="kpi-value num"><?= count($staying) ?></div>
        <div class="delta">paid, between move-in and move-out</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">To collect</div>
        <div class="kpi-value num">₱<?= number_format($waitingBalance) ?></div>
        <div class="delta"><?= count($waiting) ?> unpaid <?= count($waiting) === 1 ? 'booking' : 'bookings' ?>, after reservation fees</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Paid this month</div>
        <div class="kpi-value num">₱<?= number_format($paidThisMonth) ?></div>
        <div class="delta">bookings made in <?= date('F') ?></div>
      </div>
    </div>

    <section class="panel" aria-labelledby="occHead">
      <div class="chart-head">
        <h2 id="occHead">Who is in which bed</h2>
        <div class="legend" id="occLegend"></div>
      </div>
      <?php if ($rows): ?>
        <div class="scroll-x"><div class="chart" id="occupancy"></div></div>
        <p class="timeline-note"><?= $start->format('j M') ?> to <?= (new DateTime($end))->format('j M') ?>. Click a stay to open it in Bookings.</p>
      <?php else: ?>
        <p class="empty">No units yet. <a class="card-link" href="unit_form.php">Add the first unit</a></p>
      <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="revHead">
      <div class="chart-head">
        <h2 id="revHead">Paid revenue by month booked</h2>
        <span class="meta">last 6 months, ₱</span>
      </div>
      <div class="chart" id="revenue"></div>
      <div class="tiers">
        <div><span>Daily stays, all time</span><b>₱<?= number_format($byTier['daily']) ?></b></div>
        <div><span>Weekly stays, all time</span><b>₱<?= number_format($byTier['weekly']) ?></b></div>
        <div><span>Monthly stays, all time</span><b>₱<?= number_format($byTier['monthly']) ?></b></div>
      </div>
    </section>
  </div>

  <aside class="rail">
    <h2>Waiting for payment</h2>
    <div class="panel">
      <?php if ($waiting): ?>
        <div class="rows">
          <?php foreach (array_slice($waiting, 0, 5) as $b): ?>
            <a class="row" href="track_booking.php?tab=pending#booking-<?= (int)$b['id'] ?>">
              <div>
                <span class="who"><?= e($b['name']) ?></span>
                <span class="what"><?= e($b['unit_name']) ?> · moves in <?= date('j M', strtotime($b['move_in'])) ?><?= (float)$b['reservation_fee'] > 0 ? ' · ₱' . number_format($b['reservation_fee']) . ' reserved' : '' ?></span>
              </div>
              <span class="amount">₱<?= number_format($b['calc']['balance']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <p class="foot"><a class="card-link" href="track_booking.php?tab=pending">View all <?= count($waiting) ?></a></p>
      <?php else: ?>
        <p class="empty" style="margin-top:12px">Every current booking is paid.</p>
      <?php endif; ?>
    </div>

    <h2>Viewing requests</h2>
    <div class="panel">
      <?php if ($viewings): ?>
        <div class="rows">
          <?php foreach (array_slice($viewings, 0, 5) as $v): ?>
            <div class="row">
              <div>
                <span class="who"><?= e($v['name']) ?></span>
                <span class="what"><?= e($v['schedule']) ?></span>
              </div>
              <a class="btn small" href="tel:<?= e($v['mobile']) ?>">Call</a>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="foot"><a class="card-link" href="viewing_sched.php">View all <?= count($viewings) ?></a></p>
      <?php else: ?>
        <p class="empty" style="margin-top:12px">No viewing requests waiting.</p>
      <?php endif; ?>
    </div>
  </aside>
</div>

<?php include __DIR__ . '/partials/admin_bottom.php'; ?>
<script src="assets/charts.js"></script>
<script>
<?php if ($rows): ?>
Charts.timeline(document.getElementById('occupancy'), <?= json_encode($rows) ?>, {
  paid:     { label: 'Paid', mark: 'hue' },
  reserved: { label: 'Reserved, unpaid', mark: 'outline', dash: true },
  unpaid:   { label: 'Unpaid, no fee', mark: 'outline' },
  over:     { label: 'Past move-out', mark: 'hue', tone: 'negative' }
}, {
  days: <?= json_encode($days) ?>,
  weekends: <?= json_encode($weekends) ?>,
  today: 3,
  legend: '#occLegend',
  onSelect: (bar) => { location.href = 'track_booking.php#booking-' + bar.id; }
});
<?php endif; ?>
Charts.bars(document.getElementById('revenue'), {
  labels: <?= json_encode(array_map(fn($ym) => date('M', strtotime($ym . '-01')), array_keys($months))) ?>,
  values: <?= json_encode(array_map(fn($v) => round($v), array_values($months))) ?>,
  highlight: 5,
  height: 180,
  format: Charts.fmt.money('PHP', 0)
});
</script>
</body>
</html>
