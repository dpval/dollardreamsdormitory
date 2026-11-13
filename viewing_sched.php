<?php
/* Admin: viewing time slots guests can pick, and the requests they sent. */
require_once "auth.php";
require_admin_page();
include "db.php";

/* Remove slots that have already ended */
$today = date("Y-m-d");
$now = date("H:i:s");
$cleanup = $conn->prepare("DELETE FROM viewing_schedule WHERE date < ? OR (date = ? AND time_end < ?)");
$cleanup->bind_param("sss", $today, $today, $now);
$cleanup->execute();

$schedules = $conn->query("SELECT * FROM viewing_schedule ORDER BY date ASC, time_start ASC")->fetch_all(MYSQLI_ASSOC);
$requests = $conn->query("SELECT * FROM viewing_requests ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$waiting = count(array_filter($requests, fn($r) => ($r['status'] ?? 'Pending') === 'Pending'));
$error = $_GET['error'] ?? '';

$page = 'viewings';
$title = 'Viewings';
$navCounts = admin_counts($conn);
include __DIR__ . '/partials/admin_top.php';
?>
<style>
.split { display: grid; grid-template-columns: minmax(0, 1fr) 384px; gap: 24px; align-items: start; }
.split h2 { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
#requestsTable td { vertical-align: middle; }
#requestsTable .who { font-weight: 550; }
.status-select { width: 128px; }
.slot-form { display: grid; gap: 12px; }
.slot-form .times { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.slot-form .input { width: 100%; }
.slot-form .btn { width: 100%; height: 38px; }
.rail .panel + h2 { margin-top: 24px; }
.slots .row > div { flex: 1; min-width: 0; }
.slots .when { font-weight: 550; }
.slots .time { display: block; color: var(--muted); font-size: var(--fs-small); }
.actions { display: flex; justify-content: flex-end; }
@media (max-width: 1100px) { .split { grid-template-columns: minmax(0, 1fr); } }
</style>

<div class="title-row">
  <div>
    <h1>Viewings</h1>
    <p class="finding">
      <b><?= $waiting ?></b> <?= $waiting === 1 ? 'request' : 'requests' ?> waiting ·
      <?php if ($schedules): $next = $schedules[0]; ?>
        next slot <b><?= date('D j M', strtotime($next['date'])) ?>, <?= date('g:i A', strtotime($next['time_start'])) ?></b>
      <?php else: ?>
        <b>no open slots</b>, so guests cannot request a viewing yet
      <?php endif; ?>
    </p>
  </div>
</div>

<?php if ($error !== ''): ?>
  <p class="notice" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<div class="split">
  <section aria-labelledby="reqHead">
    <h2 id="reqHead">Requests from guests</h2>
    <div class="table-wrap">
    <table id="requestsTable">
    <thead>
    <tr>
      <th>Guest</th>
      <th>Requested slot</th>
      <th>Sent</th>
      <th>Status</th>
      <th><span class="sr-only">Actions</span></th>
    </tr>
    </thead>
    <tbody>
    <?php if ($requests): foreach ($requests as $r): $statusVal = $r['status'] ?? 'Pending'; ?>
      <tr id="request-row-<?= (int)$r['id'] ?>">
        <td><span class="who"><?= e($r['name']) ?></span><a class="sub" href="tel:<?= e($r['mobile']) ?>"><?= e($r['mobile']) ?></a></td>
        <td><?= e($r['schedule']) ?></td>
        <td class="num"><?= date("j M, g:i A", strtotime($r['created_at'])) ?></td>
        <td>
          <select class="input small status-select" data-current="<?= e($statusVal) ?>" onchange="updateViewingStatus(this, <?= (int)$r['id'] ?>)" aria-label="Status for <?= e($r['name']) ?>">
            <option value="Pending" <?= $statusVal === 'Pending' ? 'selected' : '' ?>>Waiting</option>
            <option value="Done" <?= $statusVal === 'Done' ? 'selected' : '' ?>>Viewed</option>
            <option value="Cancelled" <?= $statusVal === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </td>
        <td><div class="actions"><button type="button" class="btn small danger delete-btn" onclick="deleteViewingRequest(<?= (int)$r['id'] ?>)">Delete</button></div></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="5"><p class="empty">No viewing requests yet. Guests send them from the booking page.</p></td></tr>
    <?php endif; ?>
    </tbody>
    </table>
    </div>
  </section>

  <aside class="rail">
    <h2>Open a slot</h2>
    <div class="panel">
      <!-- The day name (Monday, Tuesday...) is filled in automatically from the date -->
      <form class="slot-form" method="POST" action="save.php">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="schedule">
        <div class="field">
          <label for="slotDate">Date</label>
          <input class="input" id="slotDate" type="date" name="date" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="times">
          <div class="field"><label for="slotStart">From</label><input class="input" id="slotStart" type="time" name="time_start" required></div>
          <div class="field"><label for="slotEnd">To</label><input class="input" id="slotEnd" type="time" name="time_end" required></div>
        </div>
        <button type="submit" class="btn primary">Add slot</button>
      </form>
    </div>

    <h2>Open slots</h2>
    <div class="panel slots">
      <?php if ($schedules): ?>
        <div class="rows">
          <?php foreach ($schedules as $s): ?>
            <div class="row" id="schedule-row-<?= (int)$s['id'] ?>">
              <div>
                <span class="when"><?= e($s['day']) ?>, <?= date("j M Y", strtotime($s['date'])) ?></span>
                <span class="time"><?= date("g:i A", strtotime($s['time_start'])) ?> to <?= date("g:i A", strtotime($s['time_end'])) ?></span>
              </div>
              <button type="button" class="btn small danger delete-btn" onclick="deleteSchedule(<?= (int)$s['id'] ?>)">Delete</button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty" style="margin:4px 0">No open slots. Add one above.</p>
      <?php endif; ?>
    </div>
  </aside>
</div>

<?php include __DIR__ . '/partials/admin_bottom.php'; ?>
<script>
function deleteSchedule(id) {
  if (!confirm("Delete this viewing slot?")) return;
  post("delete.php", { type: "schedule", id: id }).then(res => {
    if (res === "success") document.getElementById("schedule-row-" + id)?.remove();
    else alert(res);
  }).catch(() => alert("Network error. Please try again."));
}
function deleteViewingRequest(id) {
  if (!confirm("Delete this viewing request?")) return;
  post("delete.php", { type: "viewing", id: id }).then(res => {
    if (res === "success") document.getElementById("request-row-" + id)?.remove();
    else alert(res);
  }).catch(() => alert("Network error. Please try again."));
}
function updateViewingStatus(select, id) {
  const previous = select.dataset.current;
  select.disabled = true;
  post("update.php", { type: "viewing", id: id, status: select.value }).then(res => {
    if (res === "success") select.dataset.current = select.value;
    else { alert(res); select.value = previous; }
  }).catch(() => { alert("Network error. Please try again."); select.value = previous; })
    .finally(() => { select.disabled = false; });
}
</script>
</body>
</html>
