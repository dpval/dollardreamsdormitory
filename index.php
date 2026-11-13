<?php
/*
 * Public landing page. Free and booked rooms come from the database; the page still renders if it is down.
 * Room numbers, floors and prices are admin-only and never appear here.
 * Look and motion: assets/home.css + assets/home.js.
 */
$DB_OPTIONAL = true;
include "db.php";

/* ---------- rooms by kind, and one cell per bed or room for the "free today" board ---------- */
$kinds = [];
$nextSlot = null;
if ($conn) {
    foreach (get_unit_availability($conn) as $u) {
        $t = strtolower($u['room_type']);
        $key = $u['is_bedspace'] ? 'bed' : (str_contains($t, 'solo') ? 'solo' : (str_contains($t, 'couple') ? 'couple' : 'other'));
        $k = &$kinds[$key];
        $k['name']  = $k['name'] ?? ($u['is_bedspace'] ? 'Bedspace' : ucwords($t));
        $k['rooms'] = ($k['rooms'] ?? 0) + 1;
        $k['free']  = ($k['free'] ?? 0) + $u['available'];
        $k['total'] = ($k['total'] ?? 0) + $u['capacity'];
        $k['cells'] = array_merge($k['cells'] ?? [],
            array_fill(0, $u['available'], 'free'),
            array_fill(0, $u['reserved'], 'reserved'),
            array_fill(0, $u['booked'], 'booked'));
        unset($k);
    }
    $r = $conn->query("SELECT day, date, time_start FROM viewing_schedule
                       WHERE CONCAT(date, ' ', time_end) > NOW() ORDER BY date, time_start LIMIT 1");
    $nextSlot = $r ? $r->fetch_assoc() : null;
}
$order = ['bed' => 0, 'solo' => 1, 'couple' => 2, 'other' => 3];
uksort($kinds, fn($a, $b) => $order[$a] <=> $order[$b]);
$freeToday = array_sum(array_column($kinds, 'free'));
$allBeds   = array_sum(array_column($kinds, 'total'));

/* ---------- how booking works: one row per step ---------- */
$steps = [
    ['View',    'centre', 'FB_IMG_1787995625455.jpg', 'Come and see it.', 'Pick a viewing slot on the booking page and walk through the room, the kitchen and the CR.', 'Living area with kitchen counter'],
    ['Choose',  'right',  '25a16-17-coupleroomcommoncrwithac.jpg', '', '', 'Couple room with aircon'],
    ['Reserve', 'centre', 'QRDDD.jpg', 'No deposit.', 'Pay the reservation fee by GCash or InstaPay and upload the receipt. It comes off your balance.', 'GCash and InstaPay QR code'],
    ['Send ID', 'left',   '16a07-stairs.jpg', '', '', 'Stairs up to the loft'],
    ['Confirm', 'centre', 'FB_IMG_1787995734081.jpg', 'We call you.', 'We confirm your room and your dates by phone.', 'Window table in a common area'],
    ['Move in', 'right',  '19c12-coupleroomwithcr.jpg', '', '', 'Couple room with its own CR'],
];

/* ---------- the units' own photographs ---------- */
$tiles = [
    ['25a16-17-coupleroomwithcr&ac.jpg', 'Couple room', 'Own CR, aircon'],
    ['16a07-livingroom.jpg', 'Living room', 'Shared, city view'],
    ['16a07-kitchen.jpg', 'Kitchen', 'Fridge, stove'],
    ['20c19-bedspace4pax.jpg', 'Bedspace', 'Four beds, shelves'],
    ['12b19-soloroomcommoncrwithac.jpg', 'Solo room', 'Aircon'],
    ['16a07-coupleroomcommonwithbalcony.jpg', 'Couple room', 'Balcony'],
    ['FB_IMG_1787995613242.jpg', 'Dining', 'The dollar wall'],
    ['19c12-bedspace2pax.jpg', 'Bedspace', 'Two beds, wall fan'],
];

/* ---------- the walk from the door ---------- */
$walk = [
    ['Philippine General Hospital', '3–5'],
    ['Pedro Gil station, LRT-1', '5–7'],
    ['Robinsons Place Manila', '6–8'],
    ['Agencies and offices on Taft', '3–10'],
];

/* text the script reveals word by word or letter by letter; the plain copy stays for screen readers */
function words($text, $cls = '') {
    return '<span class="sr-only">' . e($text) . '</span><span class="tx ' . $cls . '" data-words aria-hidden="true">' . e($text) . '</span>';
}
function letters($text, $cls = '') {
    return '<span class="sr-only">' . e($text) . '</span><span class="tx ' . $cls . '" data-letters aria-hidden="true">' . e($text) . '</span>';
}
$brackets = str_repeat('<svg class="brk" viewBox="0 0 10.5 10.5" aria-hidden="true"><path d="M0 0.5H10V10.5"/></svg>', 4);
$arrow = '<svg class="arw" viewBox="0 0 13.7 10.7" aria-hidden="true"><path d="M0 5.35H13M8 10.35L13 5.35L8 0.35"/></svg>';
$plateLine = '<svg class="plate__line" viewBox="0 0 711 463" preserveAspectRatio="none" aria-hidden="true"><path d="M0.5 0.5H670L710.5 41V462.5H41L0.5 422Z"/></svg>';
$ctaPath = 'M220 42L212.9 50H0V0H220V42Z';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dollar Dreams Dormitory · Rooms on Taft Avenue, Malate</title>
<meta name="description" content="Bedspace, solo and couple rooms at 1655 Taft Ave, Malate, Manila. No deposit, a short walk from PGH and Pedro Gil LRT. See what is free today.">
<script>document.documentElement.classList.add('js'); if ('scrollRestoration' in history) history.scrollRestoration = 'manual';</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/home.css">
</head>
<body>

<!-- LOADING VEIL: the coin fills, the meter runs along the foot -->
<div class="veil" role="status" aria-label="Loading Dollar Dreams">
  <div class="veil__inner">
    <div class="veil__coin"><span class="veil__shell"></span><span class="veil__fill"></span><span class="veil__mark" aria-hidden="true">$</span></div>
    <p class="veil__name">Dollar Dreams</p>
  </div>
  <div class="veil__meter"><span></span></div>
</div>

<!-- MENU SHEET (below 1280px) -->
<div class="sheet" id="menuSheet" hidden>
  <div class="sheet__top">
    <a class="logo" href="index.php"><img src="image/logo.jpg" alt="" width="32" height="32"><span>Dollar Dreams</span></a>
    <button class="sheet__close" type="button" aria-label="Close menu"><span></span><span></span></button>
  </div>
  <nav class="sheet__nav" aria-label="Menu">
    <a href="#free">Free today</a>
    <a href="#how">How to book</a>
    <a href="#inside">Inside</a>
    <a href="#questions">Questions</a>
    <a href="book.php">Book a room</a>
  </nav>
</div>

<main>
<div class="stack">

  <!-- 1. HERO -->
  <section class="layer layer--pin hero" id="top" data-layer>
    <div class="layer__inner">
      <canvas class="contours" data-contours="ink" aria-hidden="true"></canvas>

      <figure class="hero__plate" data-tilt>
        <img class="is-on" src="image/25a16-17-coupleroomwithcr&amp;ac.jpg" alt="Couple room with its own CR and aircon" width="1200" height="900">
        <img src="image/16a07-livingroom.jpg" alt="Shared living room with a city view" width="1200" height="900">
        <img src="image/20c19-bedspace4pax.jpg" alt="Four-bed bedspace" width="1200" height="900">
        <?= $plateLine ?>
      </figure>

      <div class="hero__col">
        <header class="mast" data-rise="0">
          <a class="logo" href="index.php"><img src="image/logo.jpg" alt="" width="32" height="32"><span>Dollar Dreams</span></a>
          <nav class="mast__nav" aria-label="Main">
            <a href="#free">Free today</a>
            <a href="#how">How to book</a>
            <a href="#inside">Inside</a>
            <a href="#questions">Questions</a>
          </nav>
          <a class="mast__go" href="book.php"><span aria-hidden="true">[ </span>Book a room<span aria-hidden="true"> → ]</span></a>
          <button class="burger" type="button" aria-label="Open menu" aria-controls="menuSheet" aria-expanded="false"><span></span><span></span></button>
        </header>

        <div class="hero__mid">
          <div class="ident">
            <p class="ident__id" data-rise="180">1655 Taft Ave · Malate, Manila</p>
            <h1 class="ident__name"><?= words('Dollar Dreams') ?></h1>
            <ul class="ident__meta">
              <li data-rise="440"><span class="dot" aria-hidden="true"></span>Bedspace · solo · couple</li>
              <li data-rise="570"><span class="dot" aria-hidden="true"></span>No deposit</li>
              <li data-rise="700"><span class="dot" aria-hidden="true"></span>3–5 min walk to PGH</li>
            </ul>
          </div>

          <div class="panels" data-rise="900">
            <div class="bpanel"><?= $brackets ?>
              <p class="eyebrow">Free today</p>
              <dl class="stats">
                <?php if ($kinds): foreach (array_slice($kinds, 0, 3, true) as $k): ?>
                  <div><dt><?= e($k['name'] === 'Bedspace' ? 'Beds' : explode(' ', $k['name'])[0]) ?></dt><dd class="fig"><?= letters((string)$k['free']) ?></dd></div>
                <?php endforeach; else: ?>
                  <div><dt>Rooms</dt><dd class="fig">—</dd></div>
                <?php endif; ?>
              </dl>
            </div>
            <div class="bpanel"><?= $brackets ?>
              <p class="eyebrow">Next viewing</p>
              <dl class="slot">
                <?php if ($nextSlot): ?>
                  <dt><?= e($nextSlot['day']) ?></dt>
                  <dd><time datetime="<?= e($nextSlot['date']) ?>"><?= date('j M Y', strtotime($nextSlot['date'])) ?></time></dd>
                  <dd><?= date('g:i A', strtotime($nextSlot['time_start'])) ?></dd>
                <?php else: ?>
                  <dt>By appointment</dt><dd>Call or text us</dd>
                <?php endif; ?>
              </dl>
              <a class="slot__go" href="book.php#viewing">Pick a slot <?= $arrow ?></a>
            </div>
          </div>
        </div>

        <div class="acts" data-rise="1300">
          <a class="acts__see" href="#inside">
            <span class="play" aria-hidden="true"><svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="15.5"/><path d="M13 10.5 22 16l-9 5.5z"/></svg></span>
            <span class="acts__two"><b>See inside</b><span class="muted"><?= count($tiles) ?> photos</span></span>
          </a>
          <a class="cta" href="book.php">
            <svg class="cta__frame" viewBox="0 0 220 50" preserveAspectRatio="none" aria-hidden="true">
              <path class="cta__body" d="<?= $ctaPath ?>"/><path class="cta__flood" d="<?= $ctaPath ?>"/><path class="cta__ring" d="<?= $ctaPath ?>"/>
              <path class="cta__brk" d="M205 49.5H213L219.5 42V36M212 0.5H219.5V7M8 0.5H0.5V7M7.5 49.5H0.5V42.5"/>
            </svg>
            <span class="cta__label">Book a room <?= $arrow ?></span>
          </a>
          <div class="acts__call"><a href="tel:+639935103318">0993 510 3318</a><a href="tel:+639662291677">0966 229 1677</a></div>
        </div>
      </div>
    </div>
    <div class="layer__shade" aria-hidden="true"></div>
  </section>

  <!-- 2. FREE TODAY -->
  <section class="layer layer--pin today dark" id="free" data-layer>
    <div class="layer__inner">
      <canvas class="seam" data-carry="light" aria-hidden="true"></canvas>
      <canvas class="halftone" aria-hidden="true"></canvas>

      <div class="free__copy">
        <h2 class="head accent"><?= words("What's free") ?><br><?= words('today') ?><span class="stop">.</span></h2>
        <p class="free__lead" data-view><?= $kinds ? "$freeToday of $allBeds beds and rooms are free right now. Every square is one bed or one room." : 'Open the booking page to see what is free right now.' ?></p>

        <div class="board">
          <?php foreach ($kinds as $key => $k): ?>
            <div class="board__row" data-view>
              <div class="board__head">
                <h3><?= e($k['name']) ?></h3>
                <p><span class="fig"><?= letters((string)$k['free']) ?></span> <span class="muted">/ <?= $k['total'] ?> free</span></p>
              </div>
              <div class="cells" role="img" aria-label="<?= $k['free'] ?> free, <?= $k['total'] - $k['free'] ?> taken">
                <?php foreach ($k['cells'] as $i => $c): ?><span class="cell <?= $c ?>" style="--i:<?= $i ?>"></span><?php endforeach; ?>
              </div>
              <a class="board__go" href="book.php?show=<?= $key ?>#units">See <?= e(strtolower($k['name'])) ?> <?= $arrow ?></a>
            </div>
          <?php endforeach; ?>
          <p class="legend"><span class="cell free"></span>Free <span class="cell reserved"></span>Reserved <span class="cell booked"></span>Booked</p>
        </div>
      </div>

      <figure class="route" data-view>
        <svg viewBox="0 0 600 600" class="route__svg" aria-hidden="true">
          <g class="route__grid"><path d="M0 300H600M300 0V600"/></g>
          <g class="route__rings"><circle cx="300" cy="300" r="90"/><circle cx="300" cy="300" r="180"/><circle cx="300" cy="300" r="270"/></g>
          <g class="route__roads">
            <path d="M300 300V60"/><path d="M300 300H420V120"/><path d="M300 300V470H110"/><path d="M300 300H520V520"/>
          </g>
          <g class="route__lit">
            <path pathLength="1" d="M300 300V60" style="--d:0"/><path pathLength="1" d="M300 300H420V120" style="--d:1"/>
            <path pathLength="1" d="M300 300V470H110" style="--d:2"/><path pathLength="1" d="M300 300H520V520" style="--d:3"/>
          </g>
          <g class="route__stops">
            <g style="--d:0"><rect x="294" y="54" width="12" height="12"/><text x="316" y="66">PGH · 3–5 min</text></g>
            <g style="--d:1"><rect x="414" y="114" width="12" height="12"/><text x="436" y="126">Pedro Gil LRT · 5–7</text></g>
            <g style="--d:2"><rect x="104" y="464" width="12" height="12"/><text x="104" y="500">Robinsons · 6–8</text></g>
            <g style="--d:3"><rect x="514" y="514" width="12" height="12"/><text x="586" y="552" text-anchor="end">Offices · 3–10</text></g>
          </g>
          <g class="route__home"><circle class="ping" cx="300" cy="300" r="8"/><circle cx="300" cy="300" r="8"/><text x="316" y="290">Dollar Dreams</text></g>
        </svg>
        <figcaption>Minutes on foot from 1655 Taft Avenue</figcaption>
      </figure>
    </div>
    <div class="layer__shade" aria-hidden="true"></div>
  </section>

  <!-- 3. HOW TO BOOK: the last layer of the stack, it covers and is never covered -->
  <section class="layer how dark" id="how">
    <h2 class="head accent how__head"><?= words('From viewing') ?><br><?= words('to keys') ?><span class="stop">.</span></h2>
    <div class="how__rows">
      <svg class="rail" aria-hidden="true"><line class="rail__track" x1="8" x2="8" y1="0"/><rect class="rail__run" x="7.5" y="0" width="1" height="0"/><rect class="rail__mark" x="-4.5" y="-4.5" width="9" height="9"/></svg>
      <?php foreach ($steps as $i => [$word, $frame, $img, $lead, $rest, $alt]): ?>
        <article class="row row--<?= $frame ?>" data-view>
          <div class="row__plate" data-par="<?= $frame === 'centre' ? 20 : 60 ?>">
            <div class="plate"><div class="plate__fill"><img src="image/<?= e(rawurlencode($img)) ?>" alt="<?= e($alt) ?>" width="710" height="462" loading="lazy"></div><?= $plateLine ?></div>
          </div>
          <h3 class="row__word" data-par="<?= $frame === 'centre' ? 20 : 60 ?>"><?= letters($word) ?></h3>
          <?php if ($lead): ?><p class="row__copy" data-par="110"><b><?= e($lead) ?></b> <?= e($rest) ?></p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<!-- 4. INSIDE + QUESTIONS -->
<section class="inside" id="inside">
  <canvas class="seam" data-carry="dark" aria-hidden="true"></canvas>
  <canvas class="contours" data-contours="ink" aria-hidden="true"></canvas>
  <div class="inside__top">
    <h2 class="head"><?= words('Inside') ?><br><?= words('the units') ?></h2>
    <div class="inside__intro" data-view>
      <p>High-rise units on Taft Avenue with shared living rooms, kitchens and CRs. Rooms come with a fan or aircon, some with their own CR or a balcony.</p>
      <a class="cta" href="book.php">
        <svg class="cta__frame" viewBox="0 0 220 50" preserveAspectRatio="none" aria-hidden="true">
          <path class="cta__body" d="<?= $ctaPath ?>"/><path class="cta__flood" d="<?= $ctaPath ?>"/><path class="cta__ring" d="<?= $ctaPath ?>"/>
          <path class="cta__brk" d="M205 49.5H213L219.5 42V36M212 0.5H219.5V7M8 0.5H0.5V7M7.5 49.5H0.5V42.5"/>
        </svg>
        <span class="cta__label">See every room <?= $arrow ?></span>
      </a>
    </div>
  </div>

  <div class="gallery">
    <?php foreach ($tiles as $i => [$file, $room, $note]): ?>
      <figure class="shot" data-view style="--i:<?= $i % 4 ?>">
        <div class="plate"><div class="plate__fill"><img src="image/<?= e(rawurlencode($file)) ?>" alt="<?= e("$room: $note") ?>" width="600" height="750" loading="lazy"></div><?= $plateLine ?></div>
        <figcaption><b><?= e($room) ?></b><span><?= e($note) ?></span></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>

  <div class="qa" id="questions">
    <h2 class="head"><?= words('Questions') ?></h2>
    <div class="qa__list">
      <details data-view><summary>Is there a deposit?</summary><p>No. You pay a small reservation fee when you book, and it comes off your balance.</p></details>
      <details data-view><summary>How long can I stay?</summary><p>By the day, the week or the month. Choose your move-in and move-out dates when you book, and we confirm your rate when we call.</p></details>
      <details data-view><summary>What do I need to book?</summary><p>Your name, a mobile number, a valid government ID and the receipt for the reservation fee, as JPG, PNG or PDF up to 5 MB each.</p></details>
      <details data-view><summary>Can I see the room first?</summary><p>Yes. Pick a viewing slot on the booking page and we contact you to confirm it.</p></details>
      <details data-view><summary>What is a bedspace?</summary><p>A bed of your own in a shared room. The booking page shows how many beds are still free today.</p></details>
      <details data-view><summary>How do I know what is free?</summary><p>The board above and the booking page show every bed and room as free, reserved or booked, and they update as guests book.</p></details>
    </div>
  </div>

  <div class="strip" aria-label="Walking times from the dormitory">
    <div class="strip__home"><b>From the door</b><span>1655 Taft Ave</span></div>
    <?php foreach ($walk as $i => [$place, $min]): ?>
      <span class="strip__dash" aria-hidden="true"></span>
      <div class="strip__stop<?= $i === 0 ? ' is-live' : '' ?>"><b><?= $min ?> min</b><span><?= e($place) ?></span></div>
    <?php endforeach; ?>
  </div>
</section>

<!-- 5. FOOTER -->
<footer class="foot" id="visit">
  <div class="foot__panel">
    <canvas class="contours" data-contours="light" aria-hidden="true"></canvas>
    <a class="logo foot__logo" href="index.php"><img src="image/logo.jpg" alt="" width="32" height="32"><span>Dollar Dreams</span></a>
    <h2 class="head accent foot__head"><?= words('See you') ?><br><?= words('on Taft') ?><span class="stop">.</span></h2>
    <nav class="foot__nav" aria-label="Footer">
      <a href="book.php"><?= letters('Book a room') ?></a>
      <a href="book.php#viewing"><?= letters('Book a viewing') ?></a>
      <a href="#free"><?= letters('Free today') ?></a>
      <a href="https://www.openstreetmap.org/?mlat=14.5764&amp;mlon=120.9910#map=17/14.5764/120.9910" target="_blank" rel="noopener"><?= letters('Map') ?></a>
    </nav>
    <div class="foot__row">
      <p class="foot__copy">© 2026 Dollar Dreams Dormitory · 1655 Taft Avenue, Malate, City of Manila · <a href="signin.php">Staff sign in</a></p>
      <a class="cta cta--hollow" href="book.php#viewing">
        <svg class="cta__frame" viewBox="0 0 275 50" preserveAspectRatio="none" aria-hidden="true">
          <path class="cta__flood" d="M0.5 0.5H274.5V41.2L266.2 49.5H0.5Z"/><path class="cta__ring" d="M0.5 0.5H274.5V41.2L266.2 49.5H0.5Z"/>
        </svg>
        <span class="cta__label">Book a viewing <?= $arrow ?></span>
      </a>
      <div class="foot__call"><a href="tel:+639935103318">0993 510 3318</a><a href="tel:+639662291677">0966 229 1677</a></div>
    </div>
  </div>
</footer>
</main>

<script type="importmap">{ "imports": { "lenis": "https://cdn.jsdelivr.net/npm/lenis@1.3.26/dist/lenis.mjs" } }</script>
<script type="module" src="assets/home.js"></script>
</body>
</html>
