<?php
/* Admin: every booking, with dates, rate and payment editable in the row. */
require_once "auth.php";
require_admin_page();
include "db.php";

$bookings = $conn->query("SELECT * FROM bookings ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$hasProof = bookings_has_payment_proof($conn);

$rateUnit = ['daily' => '/day', 'weekly' => '/wk', 'monthly' => '/mo'];
$statusPill = [
    'PENDING'  => ['warn', 'Unpaid'],
    'UPCOMING' => ['', 'Upcoming'],
    'IN'       => ['pos', 'Staying'],
    'OVERSTAY' => ['neg', 'Past move-out'],
];

$today = date('Y-m-d');
foreach ($bookings as &$b) {
    $b['calc'] = calculate_booking($b['move_in'], $b['move_out'], $b['price'], $b['payment'], $b['reservation_fee']);
}
unset($b);

/* tab counts: the same tests the script below uses to filter */
$tabs = [
    'all'      => ['All', count($bookings)],
    'pending'  => ['Unpaid', count(array_filter($bookings, fn($b) => $b['calc']['status'] === 'PENDING'))],
    'upcoming' => ['Upcoming', count(array_filter($bookings, fn($b) => $b['calc']['status'] === 'UPCOMING'))],
    'in'       => ['Staying now', count(array_filter($bookings, fn($b) => $b['calc']['status'] === 'IN'))],
    'overstay' => ['Past move-out', count(array_filter($bookings, fn($b) => $b['calc']['status'] === 'OVERSTAY'))],
    'long'     => ['30+ days', count(array_filter($bookings, fn($b) => $b['calc']['days'] >= 30))],
];
$tab = $_GET['tab'] ?? 'all';
if (!isset($tabs[$tab])) $tab = 'all';

$page = 'bookings';
$title = 'Bookings';
$navCounts = admin_counts($conn);
include __DIR__ . '/partials/admin_top.php';
?>
<style>
.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.toolbar .input { width: 300px; max-width: 100%; }
.toolbar .count { margin-left: auto; color: var(--muted); }
#mainBookingsTable td { vertical-align: middle; white-space: nowrap; padding-left: 8px; padding-right: 8px; }
#mainBookingsTable th { padding: 0 8px; }
#mainBookingsTable .who { font-weight: 550; }
.cell-dates { display: flex; gap: 6px; align-items: center; }
.cell-dates .input { width: 132px; }
.cell-dates + .sub { margin-top: 4px; }
.input-price { width: 92px; text-align: right; }
.rate-label { display: block; margin-top: 2px; color: var(--muted); font-size: var(--fs-small); text-align: right; }
.paymentStatus { width: 104px; }
.files a { display: block; text-decoration: underline; text-underline-offset: 3px; }
.actions { display: flex; gap: 6px; justify-content: flex-end; }
tr.booking-row.is-dirty td { background: var(--raised); }
tr.booking-row:target td, tr.booking-row.is-target td { background: #e6efe2; }
.saved-flash td { transition: background-color 600ms ease; }
@media (max-width: 860px) {
  .toolbar .input { width: 100%; }
  .toolbar .count { margin-left: 0; }
}
</style>

<div class="title-row">
  <div>
    <h1>Bookings</h1>
    <p class="finding">
      <?php if ($tabs['pending'][1]): ?><b><?= $tabs['pending'][1] ?> unpaid</b> · <?php endif; ?>
      Change dates, rate or payment in a row, then save it. Rates follow the length of stay unless you type your own.
    </p>
  </div>
</div>

<nav class="tabs" role="tablist" aria-label="Booking status">
  <?php foreach ($tabs as $key => [$label, $n]): ?>
    <a href="?tab=<?= $key ?>" role="tab" data-tab="<?= $key ?>" aria-selected="<?= $tab === $key ? 'true' : 'false' ?>"><?= $label ?><span class="n"><?= $n ?></span></a>
  <?php endforeach; ?>
</nav>

<div class="toolbar">
  <label class="sr-only" for="search">Search bookings</label>
  <input class="input" id="search" type="search" placeholder="Search by name, mobile or unit" autocomplete="off">
  <span class="count" id="shownCount" aria-live="polite"></span>
</div>

<div class="table-wrap">
<table id="mainBookingsTable">
<thead>
<tr>
  <th>Guest</th>
  <th>Unit</th>
  <th>Move in and out</th>
  <th class="n">Rate</th>
  <th class="n">Total</th>
  <th class="n">Balance</th>
  <th>Payment</th>
  <th>Status</th>
  <th>Files</th>
  <th><span class="sr-only">Actions</span></th>
</tr>
</thead>
<tbody>
<?php foreach ($bookings as $b):
    $calc = $b['calc'];
    $id = (int)$b['id'];
    [$pillClass, $pillText] = $statusPill[$calc['status']];
?>
  <tr class="booking-row" id="booking-<?= $id ?>" data-id="<?= $id ?>" data-term="<?= $calc['days'] ?>" data-status="<?= strtolower($calc['status']) ?>"
      data-search="<?= e(strtolower($b['name'] . ' ' . $b['mobile'] . ' ' . $b['unit_name'] . ' ' . $b['room_type'])) ?>"
      data-reservation="<?= (float)$b['reservation_fee'] ?>" data-user-edited="false">
    <td><span class="who"><?= e($b['name']) ?></span><a class="sub" href="tel:<?= e($b['mobile']) ?>"><?= e($b['mobile']) ?></a></td>
    <td title="<?= e($b['description']) ?>"><?= e($b['unit_name']) ?><span class="sub"><?= e($b['room_type']) ?></span></td>
    <td>
      <div class="cell-dates">
        <input type="date" class="input small input-move-in" value="<?= $calc['move_in'] ?>" onchange="onDateChange(<?= $id ?>)" aria-label="Move in">
        <input type="date" class="input small input-move-out" value="<?= $calc['move_out'] ?>" onchange="onDateChange(<?= $id ?>)" aria-label="Move out">
      </div>
      <span class="sub cell-term"><?= $calc['term_text'] ?></span>
    </td>
    <td class="n">
      <input type="number" step="0.01" min="0" class="input small input-price" value="<?= $calc['rate'] ?>" oninput="onPriceManualEdit(<?= $id ?>)" aria-label="Rate">
      <span class="rate-label cell-rate-type"><?= $rateUnit[$calc['tier']] ?></span>
    </td>
    <td class="n"><span class="cell-total">₱<?= number_format($calc['total'], 2) ?></span><span class="sub"><?= (float)$b['reservation_fee'] > 0 ? '₱' . number_format((float)$b['reservation_fee']) . ' reserved' : 'no reservation fee' ?></span></td>
    <td class="n cell-balance">₱<?= number_format($calc['balance'], 2) ?></td>
    <td>
      <select class="input small paymentStatus" onchange="recalculateRow(<?= $id ?>)" aria-label="Payment">
        <option value="0" <?= (int)$b['payment'] === 0 ? 'selected' : '' ?>>Unpaid</option>
        <option value="1" <?= (int)$b['payment'] === 1 ? 'selected' : '' ?>>Paid</option>
      </select>
    </td>
    <td class="status-cell"><span class="pill <?= $pillClass ?>"><?= $pillText ?></span></td>
    <td class="files">
      <?php if (!empty($b['id_upload'])): ?><a href="view_file.php?id=<?= $id ?>&amp;type=id" target="_blank" rel="noopener">ID</a><?php endif; ?>
      <?php if ($hasProof && !empty($b['payment_proof'])): ?><a href="view_file.php?id=<?= $id ?>&amp;type=proof" target="_blank" rel="noopener">Receipt</a><?php endif; ?>
      <?php if (empty($b['id_upload']) && !($hasProof && !empty($b['payment_proof']))): ?><span class="dash">—</span><?php endif; ?>
    </td>
    <td>
      <div class="actions">
        <button type="button" class="btn small primary" onclick="updateBooking(<?= $id ?>)">Save</button>
        <button type="button" class="btn small danger delete-btn" onclick="deleteBooking(<?= $id ?>)">Delete</button>
      </div>
    </td>
  </tr>
<?php endforeach; ?>
<tr class="no-match" hidden><td colspan="10"><p class="empty">No bookings match.</p></td></tr>
<?php if (!$bookings): ?>
  <tr><td colspan="10"><p class="empty">No bookings yet. They appear here when a guest books on the website.</p></td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php include __DIR__ . '/partials/admin_bottom.php'; ?>
<script>
// Same default rates as functions.php
const RATES = { daily: <?= RATE_DAILY ?>, weekly: <?= RATE_WEEKLY ?>, monthly: <?= RATE_MONTHLY ?> };
const rowOf = (id) => document.querySelector('tr[data-id="' + id + '"]');
const peso = (n) => "₱" + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ---------- tabs and search ---------- */
let tab = <?= json_encode($tab) ?>;
function filterBookings() {
  const q = document.getElementById('search').value.trim().toLowerCase();
  let shown = 0;
  document.querySelectorAll('.booking-row').forEach(row => {
    const okTab = tab === 'all' || (tab === 'long' ? +row.dataset.term >= 30 : row.dataset.status === tab);
    const ok = okTab && (!q || row.dataset.search.includes(q));
    row.hidden = !ok;
    if (ok) shown++;
  });
  const total = document.querySelectorAll('.booking-row').length;
  document.querySelector('.no-match').hidden = shown > 0 || total === 0;
  document.getElementById('shownCount').textContent = total ? shown + ' of ' + total + ' bookings' : '';
}
document.querySelectorAll('.tabs a').forEach(a => a.addEventListener('click', (e) => {
  e.preventDefault();
  tab = a.dataset.tab;
  document.querySelectorAll('.tabs a').forEach(x => x.setAttribute('aria-selected', String(x === a)));
  history.replaceState(null, '', '?tab=' + tab);
  filterBookings();
}));
document.getElementById('search').addEventListener('input', filterBookings);
filterBookings();
// Arriving from the dashboard with #booking-12: show it even if another tab hid it
(() => {
  const row = location.hash.startsWith('#booking-') && document.querySelector(location.hash);
  if (!row) return;
  if (row.hidden) { tab = 'all'; document.querySelectorAll('.tabs a').forEach(x => x.setAttribute('aria-selected', String(x.dataset.tab === 'all'))); filterBookings(); }
  row.classList.add('is-target');
  row.scrollIntoView({ block: 'center' });
})();

/* ---------- live preview; the server recalculates with the same formula on Save ---------- */
function onDateChange(id) { rowOf(id).dataset.userEdited = "false"; recalculateRow(id); }
function onPriceManualEdit(id) { rowOf(id).dataset.userEdited = "true"; recalculateRow(id); }
function recalculateRow(id) {
  const row = rowOf(id);
  const moveIn = row.querySelector('.input-move-in').value;
  const moveOut = row.querySelector('.input-move-out').value;
  const priceInput = row.querySelector('.input-price');
  const payment = parseInt(row.querySelector('.paymentStatus').value) || 0;
  const reservation = parseFloat(row.dataset.reservation) || 0;

  let days = 1;
  if (moveIn && moveOut) days = Math.max(1, Math.round((new Date(moveOut) - new Date(moveIn)) / 86400000) + 1);
  const tier = days >= 30 ? "monthly" : (days >= 7 ? "weekly" : "daily");
  if (row.dataset.userEdited !== "true") priceInput.value = RATES[tier].toFixed(2);
  const rate = parseFloat(priceInput.value) || RATES[tier];

  let total, termText, unit;
  if (tier === "monthly") { total = Math.floor(days / 30) * rate + (days % 30) * (rate / 30); termText = Math.floor(days / 30) + " mo " + (days % 30) + " day(s)"; unit = "/mo"; }
  else if (tier === "weekly") { total = Math.floor(days / 7) * rate + (days % 7) * RATES.daily; termText = Math.floor(days / 7) + " wk " + (days % 7) + " day(s)"; unit = "/wk"; }
  else { total = days * rate; termText = days + " day(s)"; unit = "/day"; }
  const balance = payment === 1 ? 0 : Math.max(0, total - reservation);

  row.dataset.term = days;
  row.querySelector('.cell-rate-type').innerText = unit;
  row.querySelector('.cell-term').innerText = termText;
  row.querySelector('.cell-total').innerText = peso(total);
  row.querySelector('.cell-balance').innerText = peso(balance);
  row.classList.add('is-dirty');   // unsaved changes show as a tinted row until Save
}

function updateBooking(id) {
  const row = rowOf(id);
  const btn = row.querySelector('.btn.primary');
  btn.disabled = true; btn.textContent = 'Saving…';
  post("update.php", {
    type: "booking",
    id: id,
    move_in: row.querySelector('.input-move-in').value,
    move_out: row.querySelector('.input-move-out').value,
    price: row.querySelector('.input-price').value,
    payment: row.querySelector('.paymentStatus').value
  })
  .then(res => {
    if (res === "success") { location.hash = 'booking-' + id; location.reload(); }
    else { alert("Could not save: " + res); btn.disabled = false; btn.textContent = 'Save'; }
  })
  .catch(() => { alert("Network error. Please try again."); btn.disabled = false; btn.textContent = 'Save'; });
}

function deleteBooking(id) {
  if (!confirm("Permanently delete this booking (and the guest's uploaded ID and payment proof)?")) return;
  post("delete.php", { type: "booking", id: id })
  .then(res => {
    if (res === "success") location.reload();
    else alert("Delete failed: " + res);
  })
  .catch(() => alert("Network error. Please try again."));
}
</script>
</body>
</html>
