<?php
/*
 * Admin shell, top half: <head>, the green frame (sidebar + top bar) and the opening of the white sheet.
 * Set before including:  $page  = 'dashboard' | 'bookings' | 'units' | 'viewings'
 *                        $title = text for <title> and the top bar
 * Close it with partials/admin_bottom.php.
 */
$nav = [
    'dashboard' => ['dashboard.php', 'Today', '<path d="M3 10.5 12 3l9 7.5V21H3z"/><path d="M9 21v-6h6v6"/>'],
    'bookings'  => ['track_booking.php', 'Bookings', '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/>'],
    'units'     => ['manage_units.php', 'Units', '<path d="M4 21V4h11v17M15 9h5v12M8 8h3M8 12h3M8 16h3"/>'],
    'viewings'  => ['viewing_sched.php', 'Viewings', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
];
$navCounts = $navCounts ?? [];
?>
<!DOCTYPE html>
<html lang="en-PH">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · Dollar Dreams admin</title>
<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://cdn.fontshare.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="app">

  <aside class="sidebar" id="sidebar" aria-label="Admin">
    <a class="brand" href="dashboard.php">
      <img src="image/logo.jpg" alt="" width="28" height="28">
      <span>Dollar Dreams<small>Admin</small></span>
    </a>
    <?php foreach ($nav as $key => [$href, $label, $icon]): ?>
      <a class="nav-item" href="<?= $href ?>"<?= $page === $key ? ' aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icon ?></svg>
        <?= $label ?>
        <?php if (!empty($navCounts[$key])): ?><span class="count" title="Waiting"><?= (int)$navCounts[$key] ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <div class="bottom">
      <a class="nav-item" href="index.php" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/></svg>
        View the website
      </a>
      <a class="nav-item" href="signout.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/></svg>
        Sign out
      </a>
    </div>
  </aside>

  <header class="topbar">
    <button class="btn ghost menu-toggle" type="button" aria-controls="sidebar" aria-expanded="false" onclick="toggleMenu()">Menu</button>
    <span class="crumb">Dollar Dreams · <b><?= e($title) ?></b></span>
    <nav aria-label="Account">
      <span class="stamp"><?= date('D j M, g:i A') ?></span>
    </nav>
  </header>

  <main class="sheet">
