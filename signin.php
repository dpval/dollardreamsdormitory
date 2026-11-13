<?php
require_once "auth.php";
include "db.php";

if (is_admin()) {
    header("Location: dashboard.php");
    exit();
}

/*
 * Passwords are stored hashed. An old plain-text password is accepted once
 * and immediately replaced by a hash (needs the column widened by setup.sql).
 */
function check_admin_password($conn, $row, $pass) {
    $stored = (string)$row['password'];

    if (password_get_info($stored)['algo']) {
        if (!password_verify($pass, $stored)) {
            return false;
        }
        if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
            upgrade_admin_password($conn, $row['id'], $pass, $stored);
        }
        return true;
    }

    if (!hash_equals($stored, $pass)) {
        return false;
    }
    upgrade_admin_password($conn, $row['id'], $pass, $stored);
    return true;
}

function upgrade_admin_password($conn, $id, $pass, $oldValue) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
    $upd->bind_param("si", $hash, $id);
    $upd->execute();
    $upd->close();

    // If the column is too short the hash gets cut off - put the old value back so nobody is locked out.
    $chk = $conn->prepare("SELECT password FROM admin WHERE id = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $saved = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$saved || !password_verify($pass, $saved['password'])) {
        error_log("Admin password hash didn't fit - run setup.sql in phpMyAdmin.");
        $rb = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $rb->bind_param("si", $oldValue, $id);
        $rb->execute();
        $rb->close();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === '' || $pass === '') {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && check_admin_password($conn, $row, $pass)) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['admin_id'] = $row['id'];
            header("Location: dashboard.php");
            exit();
        }

        sleep(1);   // slows down password guessing
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff sign in · Dollar Dreams Dormitory</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://cdn.fontshare.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css">
<style>
body { min-height: 100svh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
.side { position: relative; overflow: hidden; }
.side img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
@media (prefers-reduced-motion: no-preference) { .side img { animation: drift 24s var(--ease-soft) infinite alternate; } }
@keyframes drift { from { transform: scale(1.08); } to { transform: scale(1); } }
.panel { display: flex; flex-direction: column; justify-content: center; padding: var(--margin); }
.login { width: min(100%, 28rem); margin: 0 auto; background: var(--paper); color: var(--paper-ink); }
.login .stripe { height: 1.5rem; }
.login .stripe span { flex-basis: 1.5rem; }
.login__body { padding: clamp(1.75rem, 4vw, 2.75rem); }
.login__seal { width: 4.5rem; border-radius: 50%; }
.login h1 { margin-top: 1.25rem; font-size: 2.5rem; line-height: 1; }
.login p { margin-top: 0.5rem; }
.login .button { width: 100%; margin-top: 1.75rem; }
.error { margin-top: 1.25rem; padding: 0.75rem 1rem; background: #f3d5cc; color: #6b1d0e; font-weight: 600; }
.back { display: inline-block; margin-top: 1.5rem; }
@media (max-width: 48em) {
  body { grid-template-columns: 1fr; grid-template-rows: 30svh auto; }
  .panel { padding-block: 0 2rem; }
  .login { margin-top: -3rem; position: relative; }
}
</style>
</head>
<body>

<div class="side" aria-hidden="true"><img src="image/herobg.jpg" alt="" width="1600" height="1000"></div>

<main class="panel">
  <div class="login on-paper">
    <div class="stripe" aria-hidden="true"><?php for ($i = 0; $i < 24; $i++) echo '<span></span>'; ?></div>
    <div class="login__body">
      <img class="login__seal" src="image/logo.jpg" alt="Dollar Dreams Dormitory" width="72" height="72">
      <h1>Staff sign in</h1>
      <p>Bookings, units and viewing slots.</p>

      <?php if (!empty($error)): ?>
        <p class="error" role="alert"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST" action="signin.php">
        <label class="field-label" for="username">Username</label>
        <input class="field" id="username" type="text" name="username" autocomplete="username" required autofocus>
        <label class="field-label" for="password">Password</label>
        <input class="field" id="password" type="password" name="password" autocomplete="current-password" required>
        <button type="submit" class="button login-btn">Sign in</button>
      </form>

      <a class="back link" href="index.php">&larr; Back to the website</a>
    </div>
  </div>
</main>

</body>
</html>
