<?php
/* Admin: create a unit (unit_form.php) or edit one (unit_form.php?id=5). */
require_once "auth.php";
require_admin_page();
include "db.php";

$id = (int)($_GET['id'] ?? 0);
$unit = ['unit_name' => '', 'room_type' => '', 'description' => '', 'image' => ''];

if ($id) {
    $sel = $conn->prepare("SELECT * FROM units WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $unit = $sel->get_result()->fetch_assoc();
    $sel->close();
    if (!$unit) {
        header("Location: manage_units.php");
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf();

    $unit['unit_name']   = trim($_POST['unit_name'] ?? '');
    $unit['room_type']   = trim($_POST['room_type'] ?? '');
    $unit['description'] = trim($_POST['description'] ?? '');
    $oldImage   = $id ? $unit['image'] : '';
    $newImage   = null;
    $hasNewFile = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($unit['unit_name'] === '' || $unit['room_type'] === '' || $unit['description'] === '') {
        $error = "Please fill in all fields.";
    } elseif (!$id && !$hasNewFile) {
        $error = "Please select an image file.";
    } elseif ($hasNewFile) {
        $ext = check_upload('image', 'Image', ['jpg', 'png', 'webp'], 2);
        if ($ext[0] === '!') {
            $error = substr($ext, 1);
        } elseif (!($newImage = save_upload('image', $ext, 'uploads/'))) {
            $error = "Failed to upload image file.";
        } else {
            $unit['image'] = $newImage;
        }
    }

    if ($error === "") {
        if ($id) {
            $stmt = $conn->prepare("UPDATE units SET unit_name = ?, room_type = ?, description = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $unit['unit_name'], $unit['room_type'], $unit['description'], $unit['image'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO units (unit_name, room_type, description, image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $unit['unit_name'], $unit['room_type'], $unit['description'], $unit['image']);
        }

        if ($stmt->execute()) {
            if ($hasNewFile && $oldImage && $oldImage !== $unit['image']) {
                delete_unit_image_if_unused($conn, $oldImage);
            }
            header("Location: manage_units.php");
            exit();
        }
        error_log('unit_form: ' . $stmt->error);
        $error = "Could not save the unit.";

        // Nothing points at the new photo, so remove it and show the old one again
        if ($newImage) {
            @unlink(__DIR__ . '/' . $newImage);
            $unit['image'] = $oldImage;
        }
    }
}

$page = 'units';
$title = $id ? 'Edit ' . $unit['unit_name'] : 'Add a unit';
$navCounts = admin_counts($conn);
include __DIR__ . '/partials/admin_top.php';
?>
<style>
.settings { display: grid; gap: 0; max-width: 960px; }
.setting { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 24px; padding: 20px 0; }
.setting + .setting { border-top: 1px solid var(--rule); }
.setting label { font-weight: 550; }
.setting .hint { margin-top: 4px; color: var(--muted); }
.setting .input, .setting textarea { width: 100%; }
.setting textarea { height: 96px; padding: 10px 12px; resize: vertical; }
.photo { display: flex; gap: 16px; align-items: flex-start; }
.photo img { width: 160px; height: 120px; object-fit: cover; border-radius: 6px; background: var(--raised); }
.photo input { padding-top: 7px; }
.form-actions { display: flex; gap: 8px; justify-content: flex-end; padding-top: 20px; border-top: 1px solid var(--rule); max-width: 960px; }
@media (max-width: 860px) { .setting { grid-template-columns: 1fr; gap: 8px; } .photo { flex-direction: column; } }
</style>

<div class="title-row">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="finding"><?= $id ? 'Changes show on the booking page as soon as you save.' : 'A unit is one room, or one bedspace room with several beds.' ?></p>
  </div>
</div>

<?php if ($error !== ""): ?>
  <p class="notice" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="settings">
    <div class="setting">
      <div><label for="unit_name">Unit code</label><p class="hint">The door: floor, tower, unit. Rooms with the same code are grouped together.</p></div>
      <input class="input" id="unit_name" type="text" name="unit_name" placeholder="16A07" value="<?= e($unit['unit_name']) ?>" required>
    </div>
    <div class="setting">
      <div><label for="room_type">Room type</label><p class="hint">Put “bed” in the name for a bedspace, so its beds are counted one by one.</p></div>
      <input class="input" id="room_type" type="text" name="room_type" placeholder="Solo Room, Couple Room or Bedspace" value="<?= e($unit['room_type']) ?>" required>
    </div>
    <div class="setting">
      <div><label for="description">Description</label><p class="hint">For a bedspace, end with the number of beds, e.g. “Aircon bedspace -4”.</p></div>
      <textarea class="input" id="description" name="description" required><?= e($unit['description']) ?></textarea>
    </div>
    <div class="setting">
      <div><label for="image">Photo</label><p class="hint">JPG, PNG or WebP up to 2 MB.<?= $id ? ' Leave empty to keep the current photo.' : '' ?></p></div>
      <div class="photo">
        <?php if (!empty($unit['image'])): ?><img src="<?= e($unit['image']) ?>" alt="Current photo" width="160" height="120"><?php endif; ?>
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" <?= $id ? '' : 'required' ?>>
      </div>
    </div>
  </div>
  <div class="form-actions">
    <a class="btn" href="manage_units.php">Cancel</a>
    <button type="submit" class="btn primary"><?= $id ? 'Save changes' : 'Add unit' ?></button>
  </div>
</form>

<?php include __DIR__ . '/partials/admin_bottom.php'; ?>
</body>
</html>
