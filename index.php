<?php
/*
 * Public landing page. Free beds and rooms come from the database; the page still renders if it is down.
 * Room numbers, floors and prices are admin-only and never appear here.
 * Look and motion: assets/home.css + assets/home.js. The "Book a viewing" dialog posts to save.php.
 */
$DB_OPTIONAL = true;
include "db.php";

/* ---------- free beds and rooms by kind ---------- */
$photo = [
    'bed'    => ['20c19-bedspace4pax.jpg', 'Four-bed bedspace with shelves'],
    'solo'   => ['12b19-soloroomcommoncrwithac.jpg', 'Solo room with aircon'],
    'couple' => ['25a16-17-coupleroomwithcr&ac.jpg', 'Couple room with its own CR and aircon'],
    'other'  => ['16a07-livingroom.jpg', 'Shared living room'],
];
$kinds = [];
if ($conn) {
    foreach (get_unit_availability($conn) as $u) {
        $t = strtolower($u['room_type']);
        $key = $u['is_bedspace'] ? 'bed' : (str_contains($t, 'solo') ? 'solo' : (str_contains($t, 'couple') ? 'couple' : 'other'));
        $k = &$kinds[$key];
        $k['name']  = $k['name'] ?? ($u['is_bedspace'] ? 'Bedspace' : ucwords($t));
        $k['free']  = ($k['free'] ?? 0) + $u['available'];
        $k['total'] = ($k['total'] ?? 0) + $u['capacity'];
        unset($k);
    }
}
$order = ['bed' => 0, 'solo' => 1, 'couple' => 2, 'other' => 3];
uksort($kinds, fn($a, $b) => $order[$a] <=> $order[$b]);
$freeToday = array_sum(array_column($kinds, 'free'));

/* the room carousel: ghost words, photo, caption per kind */
$slides = [
    'bed'    => ['words' => ['Your', 'Own', 'Bed', 'Space'], 'line' => 'A bed of your own in a shared room, with a fan or aircon.'],
    'solo'   => ['words' => ['A Room', 'To', 'Your-', 'self'], 'line' => 'A private room, with a shared CR or an exhaust-type room.'],
    'couple' => ['words' => ['Made', 'For', 'Two', 'People'], 'line' => 'A room for two, some with their own CR, aircon or a balcony.'],
];
$slideData = [];
foreach ($slides as $key => $s) {
    $k = $kinds[$key] ?? null;
    $slideData[] = [
        'key'   => $key,
        'words' => $s['words'],
        'img'   => 'image/' . $photo[$key][0],
        'alt'   => $photo[$key][1],
        'name'  => $k['name'] ?? ucfirst($key),
        'role'  => $k ? ($k['free'] . ' of ' . $k['total'] . ' free today') : 'See the booking page',
    ];
}

/* ways to stay: one row per kind, then a viewing */
$ways = [];
$n = 1;
foreach ($slides as $key => $s) {
    $k = $kinds[$key] ?? null;
    $ways[] = [sprintf('%02d', $n++), $k['name'] ?? ucfirst($key), $s['line'] . ($k ? " {$k['free']} free today." : ''), "book.php?show=$key#units"];
}
$ways[] = [sprintf('%02d', $n), 'Book a viewing', 'Pick a time, see the room, the kitchen and the CR before you decide.', 'book.php#viewing'];

$walk = [['3–5', 'min to PGH'], ['5–7', 'min to Pedro Gil LRT'], ['6–8', 'min to Robinsons Manila'], ['3–10', 'min to offices on Taft']];

/* the real logo (image/logo.jpg), cropped to its coin */
$mark = '<span class="logo-mark" aria-hidden="true"><img src="image/logo.jpg" alt="" width="40" height="40"></span>';
$arrow = '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
$xIcon = '<svg class="ico x" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
function lines($tag, $id, array $lines, $cls = '', $extra = '') {
    $plain = e(implode(' ', $lines));
    $out = "<$tag" . ($id ? " id=\"$id\"" : '') . " class=\"lines $cls\" $extra><span class=\"sr-only\">$plain</span>";
    foreach ($lines as $l) $out .= '<span class="ln" aria-hidden="true"><span>' . e($l) . '</span></span>';
    return $out . "</$tag>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dollar Dreams Dormitory · Rooms on Taft Avenue, Malate</title>
<meta name="description" content="Bedspace, solo and couple rooms at 1655 Taft Ave, Malate, Manila. No deposit, a short walk from PGH and Pedro Gil LRT. See what is free today and book a viewing.">
<script>document.documentElement.classList.add('js'); if ('scrollRestoration' in history) history.scrollRestoration = 'manual';</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500&display=swap" rel="stylesheet">
<link rel="icon" href="image/logo.jpg">
<link rel="stylesheet" href="assets/home.css">
</head>
<body>

<!-- LOADER: the logo animation (image/dddloaading.mp4) with a filling line, then the curtain slides up -->
<div class="loader" role="status" aria-label="Loading Dollar Dreams">
  <video class="loader__video" muted playsinline preload="auto" poster="image/logo.jpg" aria-hidden="true"><source src="image/dddloaading.mp4" type="video/mp4"></video>
  <p class="sr-only">Dollar Dreams Dormitory</p>
  <div class="loader__track"><span></span></div>
</div>

<main>

<!-- 1. HERO -->
<section class="hero" aria-labelledby="hero-title">
  <div class="hero__bg" aria-hidden="true"><div class="hero__plate" data-parallax="0,12"><img src="image/herobg.jpg" alt="" fetchpriority="high"></div></div>

  <header class="site-head">
    <nav class="site-head__left" aria-label="Main">
      <a href="#rooms">Rooms &amp; availability</a>
      <a href="#location">Location</a>
    </nav>
    <a class="brand" href="index.php"><?= $mark ?><span>Dollar Dreams</span></a>
    <div class="site-head__right">
      <a class="textbtn" href="book.php#viewing" data-open-visit>Book a viewing</a>
      <button class="burger" type="button" data-open-menu aria-label="Open menu" aria-expanded="false"><span></span><span></span></button>
    </div>
  </header>

  <div class="hero__title"><h1 id="hero-title" class="words" data-gate><span class="sr-only">Rooms on Taft</span><span class="w" aria-hidden="true"><span>Rooms</span></span> <span class="w" aria-hidden="true"><span>on</span></span> <span class="w" aria-hidden="true"><span>Taft</span></span></h1></div>

  <div class="hero__foot">
    <?= lines('p', '', ['Affordable, No-Deposit stays', 'right in the heart of the city—', 'minutes away from PGH, LRT,', 'malls, and recruitment agencies'], 'tagline', 'data-gate') ?>
    <div class="hero__cards">
      <div class="slider" data-inview="650" data-gate-inview>
        <div class="slider__stage">
          <?php foreach ($slideData as $i => $s): ?>
            <a class="glass slide<?= $i === 0 ? ' is-on' : '' ?>" href="book.php?show=<?= $s['key'] ?>#units" <?= $i ? 'aria-hidden="true" tabindex="-1"' : '' ?>>
              <img src="<?= e($s['img']) ?>" alt="<?= e($s['alt']) ?>" width="112" height="112" loading="lazy">
              <span class="slide__txt"><b><?= e($s['name']) ?></b><span><?= e($s['role']) ?></span><u>See rooms →</u></span>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="dots dots--light" data-dots="slider"><?php foreach ($slideData as $i => $s): ?><button type="button" aria-label="Show <?= e($s['name']) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>><span></span></button><?php endforeach; ?></div>
      </div>
      <article class="glass free-card" data-inview="780" data-gate-inview>
        <div class="free-card__left">
          <p class="free-card__num"><?= $kinds ? $freeToday : '—' ?></p>
          <div class="pips" aria-hidden="true"><span style="--c:#22d47e"></span><span style="--c:#c2e029"></span><span style="--c:#9cbfa6"></span><span style="--c:#ffffff"></span></div>
          <p class="free-card__cap">Beds and rooms free today</p>
        </div>
        <img src="image/16a07-livingroom.jpg" alt="Shared living room with a city view" width="128" height="170" loading="lazy">
      </article>
    </div>
  </div>
</section>

<!-- 2. ROOMS (carousel with ghost words) -->
<section class="rooms" id="rooms" aria-labelledby="rooms-title">
  <div class="rooms__badges">
    <div class="pct" data-inview="0" data-from="scale"><b>No</b><span>deposit to move in</span></div>
    <article class="badge-card" data-inview="120">
      <span class="badge-card__idx">#01</span>
      <div><h3>Close to everything</h3><p>Bedspace, solo and couple rooms in a Malate high-rise, minutes on foot from PGH, Pedro Gil LRT and Robinsons.</p></div>
    </article>
  </div>

  <div class="rooms__stage">
  <h2 id="rooms-title" class="ghost" aria-live="polite">
    <span class="sr-only" data-ghost-plain><?= e(implode(' ', $slideData[0]['words'])) ?></span>
    <span class="ghost__row" aria-hidden="true"><span class="gw" data-gx="-3,3"><span><?= e($slideData[0]['words'][0]) ?></span></span><span class="gw" data-gx="3,-3"><span><?= e($slideData[0]['words'][1]) ?></span></span></span>
    <span class="ghost__row" aria-hidden="true"><span class="gw ink" data-gx="-2,4"><span><?= e($slideData[0]['words'][2]) ?></span></span><span class="gw" data-gx="4,-3"><span><?= e($slideData[0]['words'][3]) ?></span></span></span>
  </h2>

  <figure class="room-card" data-inview="0" data-from="card">
    <img src="<?= e($slideData[0]['img']) ?>" alt="<?= e($slideData[0]['alt']) ?>" width="512" height="683" loading="lazy">
    <figcaption class="cap"><b data-cap-name><?= e($slideData[0]['name']) ?></b><span data-cap-role><?= e($slideData[0]['role']) ?></span></figcaption>
  </figure>
  </div>

  <div class="rooms__ctrl">
    <button class="arrowbtn" type="button" data-room-step="-1" aria-label="Previous room"><svg class="ico flip" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
    <div class="dots" data-dots="rooms"><?php foreach ($slideData as $i => $s): ?><button type="button" aria-label="Show <?= e($s['name']) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>><span></span></button><?php endforeach; ?></div>
    <button class="arrowbtn solid" type="button" data-room-step="1" aria-label="Next room"><?= $arrow ?></button>
  </div>
  <script type="application/json" id="room-slides"><?= json_encode($slideData) ?></script>
</section>

<!-- 3. WAYS TO STAY -->
<section class="ways" id="stay" aria-labelledby="ways-title">
  <p class="eyebrow">Ways to stay</p>
  <?= lines('h2', 'ways-title', ['Pick the room', 'that fits'], 'h2') ?>
  <ul class="ways__list">
    <?php foreach ($ways as $i => [$idx, $name, $desc, $href]): ?>
      <li><a class="way" href="<?= e($href) ?>" <?= $href === 'book.php#viewing' ? 'data-open-visit' : '' ?> data-inview="<?= $i * 90 ?>">
        <span class="way__idx"><?= $idx ?></span>
        <span class="way__txt"><b><?= e($name) ?></b><span><?= e($desc) ?></span></span>
        <span class="way__go"><span data-hover-arrow><?= $arrow ?></span></span>
      </a></li>
    <?php endforeach; ?>
  </ul>
</section>

<!-- 4. INSIDE -->
<section class="inside" id="inside" aria-labelledby="inside-title">
  <div class="inside__grid">
    <div class="inside__intro">
      <img class="inside__icon" src="image/16a07-kitchen.jpg" alt="Shared kitchen" width="128" height="128" loading="lazy" data-inview="0" data-from="scale">
      <?= lines('h2', 'inside-title', ['Look inside', 'the units'], 'h2') ?>
      <p class="fade-words" data-words>Shared living rooms, kitchens and CRs in high-rise units along Taft Avenue. Book a viewing and walk through before you decide.</p>
    </div>
    <div class="inside__cards">
      <figure class="tile" data-inview="0"><div class="tile__img" data-hover-scale><img src="image/FB_IMG_1787995613242.jpg" alt="Dining area with the framed dollar bill" width="600" height="800" loading="lazy"></div>
        <figcaption class="cap"><b>The dollar wall</b><span>Shared dining, with the framed dollar on the wall.</span></figcaption></figure>
      <figure class="tile tile--low" data-inview="140"><div class="tile__img" data-hover-scale><img src="image/16a07-livingroom.jpg" alt="Shared living room with a city view" width="600" height="800" loading="lazy"></div>
        <figcaption class="cap cap--tint"><b>Living room</b><span>A shared sofa and TV, with the city outside the window.</span></figcaption></figure>
    </div>
  </div>
</section>

<!-- 5. LOCATION: the walking-route map and the minutes from the door -->
<section class="where" id="location" aria-labelledby="where-title">
  <div class="where__grid">
    <div>
      <p class="eyebrow eyebrow--light">From the door</p>
      <?= lines('h2', 'where-title', ['Minutes from', 'everything'], 'h2') ?>
      <p class="where__addr">1655 Taft Avenue, Malate, City of Manila</p>
      <dl class="stats">
        <?php foreach ($walk as $i => [$min, $label]): ?>
          <div class="stat" data-inview="<?= $i * 110 ?>"><dt class="sr-only"><?= e($label) ?></dt><dd><b><?= $min ?></b><span><?= e($label) ?></span></dd></div>
        <?php endforeach; ?>
      </dl>
    </div>

    <figure class="route" data-route>
      <svg viewBox="0 0 600 600" class="route__svg" role="img" aria-label="Walking routes from Dollar Dreams to PGH, Pedro Gil LRT, Robinsons Manila and offices on Taft">
        <g class="route__grid"><path d="M0 300H600M300 0V600"/></g>
        <g class="route__rings"><circle cx="300" cy="300" r="90"/><circle cx="300" cy="300" r="180"/><circle cx="300" cy="300" r="270"/></g>
        <g class="route__roads"><path d="M300 300V60"/><path d="M300 300H420V120"/><path d="M300 300V470H110"/><path d="M300 300H520V520"/></g>
        <g class="route__lit">
          <path pathLength="1" d="M300 300V60" style="--d:0"/><path pathLength="1" d="M300 300H420V120" style="--d:1"/>
          <path pathLength="1" d="M300 300V470H110" style="--d:2"/><path pathLength="1" d="M300 300H520V520" style="--d:3"/>
        </g>
        <g class="route__stops">
          <g style="--d:0"><rect x="294" y="54" width="12" height="12"/><text x="316" y="66">PGH · 3–5 min</text></g>
          <g style="--d:1"><rect x="414" y="114" width="12" height="12"/><text x="436" y="126">Pedro Gil LRT · 5–7</text></g>
          <g style="--d:2"><rect x="104" y="464" width="12" height="12"/><text x="104" y="500">Robinsons · 6–8</text></g>
          <g style="--d:3"><rect x="514" y="514" width="12" height="12"/><text x="502" y="526" text-anchor="end">Offices · 3–10</text></g>
        </g>
        <g class="route__home"><circle class="ping" cx="300" cy="300" r="8"/><circle cx="300" cy="300" r="8"/><text x="316" y="290">Dollar Dreams</text></g>
      </svg>
      <figcaption>Minutes on foot from 1655 Taft Avenue · <a href="https://www.openstreetmap.org/?mlat=14.5764&amp;mlon=120.9910#map=17/14.5764/120.9910" target="_blank" rel="noopener">Open the street map</a></figcaption>
    </figure>
  </div>
</section>

<!-- 6. HOW BOOKING WORKS (three cards) -->
<section class="how" id="how" aria-labelledby="how-title">
  <p class="eyebrow">How booking works</p>
  <?= lines('h2', 'how-title', ['From viewing', 'to keys'], 'h2') ?>
  <ul class="how__cards">
    <li data-inview="0"><article class="qcard" data-hover-lift><span class="qcard__n">1</span><h3>See it first</h3><p>Pick a viewing slot and walk through the room, the kitchen and the CR.</p><footer><b>Viewing</b><span>By appointment</span></footer></article></li>
    <li data-inview="120"><article class="qcard" data-hover-lift><span class="qcard__n">2</span><h3>Reserve, no deposit</h3><p>Pay the reservation fee by GCash or InstaPay and upload the receipt and a valid ID. The fee comes off your balance.</p><footer><b>Reserve</b><span>On the booking page</span></footer></article></li>
    <li data-inview="240"><article class="qcard" data-hover-lift><span class="qcard__n">3</span><h3>We call, you move in</h3><p>We confirm your room, dates and rate by phone, then you move in on your date.</p><footer><b>Confirm</b><span>0993 510 3318</span></footer></article></li>
  </ul>
</section>

<!-- 7. FOOTER -->
<footer class="foot" id="contact">
  <div class="foot__cta">
    <div>
      <p class="eyebrow eyebrow--light">Get started</p>
      <?= lines('p', '', ['Ready to', 'move in?'], 'foot__big') ?>
    </div>
    <div data-inview="150"><a class="pill light" href="book.php#viewing" data-open-visit>Book a viewing <span data-hover-arrow><?= $arrow ?></span></a></div>
  </div>
  <div class="foot__cols">
    <div class="foot__brand">
      <a class="brand" href="index.php"><?= $mark ?><span>Dollar Dreams</span></a>
      <p>Bedspace, solo and couple rooms by the day, the week or the month, in a high-rise on Taft Avenue.</p>
      <address><a href="tel:+639935103318">0993 510 3318</a><a href="tel:+639662291677">0966 229 1677</a><span>1655 Taft Avenue, Malate, City of Manila</span></address>
    </div>
    <nav aria-label="Rooms"><h3>Rooms</h3><ul><li><a href="book.php?show=bed#units">Bedspace</a></li><li><a href="book.php?show=solo#units">Solo room</a></li><li><a href="book.php?show=couple#units">Couple room</a></li><li><a href="book.php?show=free#units">Free now</a></li></ul></nav>
    <nav aria-label="Visit"><h3>Visit</h3><ul><li><a href="book.php">Book a room</a></li><li><a href="book.php#viewing" data-open-visit>Book a viewing</a></li><li><a href="#location">Location</a></li><li><a href="#how">How booking works</a></li></ul></nav>
    <nav aria-label="Dorm"><h3>Dorm</h3><ul><li><a href="#inside">Inside the units</a></li><li><a href="#rooms">Free today</a></li><li><a href="signin.php">Staff sign in</a></li></ul></nav>
  </div>
  <div class="foot__bar"><p>© 2026 Dollar Dreams Dormitory. All rights reserved.</p><p>Malate, Manila</p></div>
</footer>
</main>

<!-- MENU OVERLAY -->
<div class="menu" id="menu" aria-hidden="true">
  <div class="menu__bg" data-close-menu></div>
  <div class="menu__panel" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="menu__top"><a class="brand" href="index.php"><?= $mark ?><span>Dollar Dreams</span></a><button class="roundbtn" type="button" data-close-menu aria-label="Close menu"><?= $xIcon ?></button></div>
    <nav class="menu__nav" aria-label="Menu">
      <a href="#rooms">Rooms</a><a href="#inside">Inside</a><a href="#location">Location</a><a href="#how">How to book</a>
    </nav>
    <div class="menu__bottom"><a class="pill light" href="book.php#viewing" data-open-visit>Book a viewing <span data-hover-arrow><?= $arrow ?></span></a><a href="book.php">See every room</a></div>
  </div>
</div>

<!-- BOOK A VIEWING (posts to save.php, the same as the booking page) -->
<div class="modal" id="viewing" aria-hidden="true">
  <div class="modal__bg" data-close-visit></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="visit-title">
    <div class="modal__head">
      <div><p class="eyebrow">Book a viewing</p><?= lines('h2', 'visit-title', ['Come see', 'the rooms'], 'modal__title') ?></div>
      <button class="roundbtn dark" type="button" data-close-visit aria-label="Close"><?= $xIcon ?></button>
    </div>
    <form class="visit" id="visitForm" novalidate>
      <input type="hidden" name="type" value="viewing">
      <label class="fld"><span>Viewing slot</span><select name="schedule" id="visitSlot" required><option value="">Loading slots…</option></select></label>
      <label class="fld"><span>Full name</span><input name="name" autocomplete="name" placeholder="Juan Dela Cruz" required></label>
      <label class="fld"><span>Mobile number</span><input name="mobile" type="tel" inputmode="tel" autocomplete="tel" placeholder="09171234567" pattern="(09|\+639)[0-9]{9}" required></label>
      <p class="visit__err" role="alert" hidden></p>
      <button class="pill solid" type="submit">Request a viewing</button>
      <p class="visit__alt">No slot that suits you? Call or text <a href="tel:+639935103318">0993 510 3318</a>.</p>
    </form>
    <div class="visit__done" hidden>
      <span class="okdot"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg></span>
      <p class="visit__done-title">Request received</p>
      <p class="visit__done-sub"></p>
      <button class="pill solid" type="button" data-close-visit>Done</button>
    </div>
  </div>
</div>

<script type="importmap">{ "imports": { "lenis": "https://cdn.jsdelivr.net/npm/lenis@1.3.26/dist/lenis.mjs" } }</script>
<script type="module" src="assets/home.js"></script>
</body>
</html>
