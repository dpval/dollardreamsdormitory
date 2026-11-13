<?php
/* Admin: every unit (room or bedspace) with today's free beds, and links to edit or delete. */
require_once "auth.php";
require_admin_page();
include "db.php";

$units = get_unit_availability($conn);
uasort($units, fn($a, $b) => [$a['unit_name'], $a['id']] <=> [$b['unit_name'], $b['id']]);
$doors = count(array_unique(array_column($units, 'unit_name')));
$free = array_sum(array_column($units, 'available'));
$capacity = array_sum(array_column($units, 'capacity'));

$page = 'units';
$title = 'Units';
$navCounts = admin_counts($conn);
include __DIR__ . '/partials/admin_top.php';
?>
<style>
#unitsTable td { vertical-align: middle; }
#unitsTable .code { font-weight: 600; }
.desc { max-width: 36ch; }
.actions { display: flex; gap: 6px; justify-content: flex-end; }
.actions form { display: inline; }
.free-0 { color: var(--muted); }
</style>

<div class="title-row">
  <div>
    <h1>Units</h1>
    <p class="finding"><b><?= $doors ?></b> units, <b><?= count($units) ?></b> rooms and bedspaces · <b><?= $free ?> of <?= $capacity ?></b> beds and rooms free today</p>
  </div>
  <a class="btn primary" href="unit_form.php">Add a unit</a>
</div>

<div class="table-wrap">
<table id="unitsTable">
<thead>
<tr>
  <th><span class="sr-only">Photo</span></th>
  <th>Unit</th>
  <th>Room</th>
  <th>Description</th>
  <th class="n">Free today</th>
  <th>Status</th>
  <th><span class="sr-only">Actions</span></th>
</tr>
</thead>
<tbody>
<?php if ($units): foreach ($units as $u):
    $pill = $u['status'] === 'available' ? ['pos', 'Free'] : ($u['status'] === 'reserved' ? ['warn', 'Reserved'] : ['', 'Full']);
?>
  <tr>
    <td><?php if (!empty($u['image'])): ?><img class="thumb" src="<?= e($u['image']) ?>" alt="" width="64" height="48" loading="lazy"><?php else: ?><span class="dash">—</span><?php endif; ?></td>
    <td><span class="code"><?= e($u['unit_name']) ?></span><span class="sub"><?= e(unit_floor_label($u['unit_name'])) ?: '&nbsp;' ?></span></td>
    <td><?= e($u['room_type']) ?></td>
    <td class="desc"><?= e($u['description']) ?></td>
    <td class="n <?= $u['available'] ? '' : 'free-0' ?>"><?= $u['available'] ?> <span class="dash">/ <?= $u['capacity'] ?></span></td>
    <td><span class="pill <?= $pill[0] ?>"><?= $pill[1] ?></span></td>
    <td>
      <div class="actions">
        <a class="btn small" href="unit_form.php?id=<?= (int)$u['id'] ?>">Edit</a>
        <form method="POST" action="delete.php" onsubmit="return confirm('Delete <?= e($u['unit_name']) ?> · <?= e($u['room_type']) ?>? Past bookings keep their copy of the unit name.')">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="unit">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button type="submit" class="btn small danger btn-delete">Delete</button>
        </form>
      </div>
    </td>
  </tr>
<?php endforeach; else: ?>
  <tr><td colspan="7"><p class="empty">No units yet. <a class="card-link" href="unit_form.php">Add the first unit</a></p></td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php include __DIR__ . '/partials/admin_bottom.php'; ?>
</body>
</html>
