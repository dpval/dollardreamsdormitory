<?php
/* Public landing page. Rooms and free beds come from the database; the page still works if it is down. */
$DB_OPTIONAL = true;
include "db.php";

/*
 * Rooms grouped by kind (bedspace, solo, couple), with today's free and booked counts.
 * Room numbers, floors and prices are admin-only and never shown on the public pages.
 */
$kinds = [];
if ($conn) {
    foreach (get_unit_availability($conn) as $u) {
        $t = strtolower($u['room_type']);
        $key = $u['is_bedspace'] ? 'bed' : (str_contains($t, 'solo') ? 'solo' : (str_contains($t, 'couple') ? 'couple' : 'all'));
        $k = &$kinds[$key];
        $k['name']  = $k['name'] ?? ($u['is_bedspace'] ? 'Bedspace' : ucwords($t));
        $k['rooms'] = ($k['rooms'] ?? 0) + 1;
        $k['free']  = ($k['free'] ?? 0) + $u['available'];
        $k['total'] = ($k['total'] ?? 0) + $u['capacity'];
        $k['beds']  = $u['is_bedspace'];
        unset($k);
    }
}
$order = ['bed' => 0, 'solo' => 1, 'couple' => 2, 'all' => 3];
uksort($kinds, fn($a, $b) => $order[$a] <=> $order[$b]);
$freeToday = array_sum(array_column($kinds, 'free'));
$kindLine = [
    'bed'    => 'A bed of your own in a shared room. Some rooms have aircon, some a fan.',
    'solo'   => 'A room to yourself, with a shared CR or an exhaust-type room.',
    'couple' => 'A room for two, some with their own CR, aircon or a balcony.',
    'all'    => 'Rooms in high-rise units along Taft Avenue.',
];

/* The units' own photographs, with what each one shows */
$tiles = [
    ['25a16-17-coupleroomwithcr&ac.jpg', 'Couple room', 'Own CR', 'Aircon'],
    ['16a07-livingroom.jpg', 'Living room', 'Shared', 'City view'],
    ['16a07-kitchen.jpg', 'Kitchen', 'Shared', 'Fridge, stove'],
    ['19c12-bedspace2pax.jpg', 'Bedspace', '2 beds', 'Wall fan'],
    ['20c19-bedspace4pax.jpg', 'Bedspace', '4 beds', 'Shelves'],
    ['12b19-soloroomcommoncrwithac.jpg', 'Solo room', 'Common CR', 'Aircon'],
    ['16a07-coupleroomcommonwithbalcony.jpg', 'Couple room', 'Common CR', 'Balcony'],
    ['19c12-coupleroomwithcr.jpg', 'Couple room', 'Own CR', 'Sliding door'],
    ['16a07-stairs.jpg', 'Stairs', 'Loft unit', 'Two levels'],
    ['25a16-17SoloRoomExhaustType.jpg', 'Solo room', 'Exhaust fan', 'Built-ins'],
    ['FB_IMG_1787995613242.jpg', 'Dining', 'Shared', 'Dollar wall'],
    ['FB_IMG_1787995723292.jpg', 'Breakfast bar', 'Shared', 'Stools'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dollar Dreams Dormitory · Rooms on Taft Avenue, Malate</title>
<meta name="description" content="Bedspace, solo and couple rooms at 1655 Taft Ave, Malate, Manila. No deposit, a short walk from PGH and Pedro Gil LRT.">
<script>document.documentElement.classList.add('js')</script>
<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://cdn.fontshare.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/site.css">
<style>
/* ---------- hero: the card beside the room ---------- */
.hero { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 6fr); gap: 12px; padding: 0 var(--margin) var(--margin); min-height: calc(100svh - 4rem); }
.hero__card { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0 1.5rem 2rem; min-width: 0; overflow: hidden; }
.hero__card .stripe { margin-inline: -1.5rem; }
.hero__card .stripe { align-self: stretch; }
.hero__seal { width: clamp(7.5rem, 11vw, 10.5rem); aspect-ratio: 1; border-radius: 50%; margin-top: clamp(1.5rem, 4vh, 3rem); object-fit: cover; background: var(--paper); }
.hero h1 { margin-top: clamp(1.25rem, 3.5vh, 2.5rem); font-size: clamp(2.6rem, 4.4vw, 4.4rem); line-height: 0.95; max-width: 12ch; }
.hero__lead { margin-top: 2rem; max-width: 36ch; font-size: var(--step-lead); line-height: 1.35; }
.hero__card .button { margin-top: 2rem; }
.hero__free { margin-top: 1rem; font-weight: 500; }
.hero__links { display: flex; gap: 1.75rem; margin-top: auto; padding-top: 2rem; }
.hero__links a { text-decoration: underline; text-underline-offset: 0.22em; text-decoration-thickness: 0.08em; }
.hero__photo { position: sticky; top: 4rem; height: calc(100svh - 4rem - var(--margin)); overflow: hidden; background: var(--surface); }
.hero__photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 1.6s ease; }
.hero__photo img.is-on { opacity: 1; }
.hero__caption { position: absolute; left: 0; bottom: 0; padding: 0.6rem 1rem; background: var(--bg); color: var(--ink); font-size: var(--step-small); font-weight: 500; }

/* ---------- rooms: key tags that fold into spines ---------- */
.rooms h2 { font-size: clamp(3rem, 4.2vw, 4rem); line-height: 1; }
.rooms__intro { margin-top: 1rem; max-width: 46ch; }
.rooms__deck { display: grid; gap: 12px; margin-top: 2.5rem; }
.tag { position: relative; display: flex; flex-direction: column; min-height: 22.5rem; padding: 2rem 2rem 2rem 4.75rem; color: var(--ink); text-decoration: none; overflow: hidden; }
.tag:nth-child(3n+1) { background: var(--tone-1); }
.tag:nth-child(3n+2) { background: var(--tone-2); }
.tag:nth-child(3n+3) { background: var(--tone-3); }
.tag::before { content: ""; position: absolute; left: 1.6rem; top: 2.2rem; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: var(--paper); } /* the key-ring hole */
.tag__code { font-size: clamp(2.5rem, 4vw, 4rem); line-height: 0.9; letter-spacing: -0.03em; font-weight: 700; white-space: nowrap; }
.tag__count { margin-top: 0.75rem; font-size: 1.25rem; font-weight: 500; white-space: nowrap; }
.tag__types { margin-top: 1rem; max-width: 30ch; }
.tag__foot { display: flex; justify-content: space-between; gap: 1rem; margin-top: auto; padding-top: 1.5rem; white-space: nowrap; }
.tag__free { font-weight: 600; }
.tag__go { text-decoration: underline; text-underline-offset: 0.22em; }
.rooms__count { margin-top: 3rem; font-size: var(--step-lead); }
.rooms__count a { font-weight: 600; }
@media (min-width: 64em) {
  .rooms.is-pinned .rooms__pin { position: relative; height: var(--pin-h, 260svh); }
  .rooms.is-pinned .rooms__stage { position: sticky; top: 4rem; height: calc(100svh - 4rem); display: flex; flex-direction: column; justify-content: center; }
  .rooms.is-pinned .rooms__deck { display: flex; gap: 12px; overflow: hidden; }
  .rooms.is-pinned .tag { flex: 0 0 auto; width: var(--w, 38.75rem); cursor: pointer; transition: transform var(--t-press) var(--ease-out); }
  .rooms.is-pinned .tag.is-spine .tag__count, .rooms.is-pinned .tag.is-spine .tag__types, .rooms.is-pinned .tag.is-spine .tag__foot { opacity: 0; visibility: hidden; }
  /* a folded tag reads like a book spine: its door code turns upright */
  .rooms.is-pinned .tag.is-spine { padding: 4.5rem 0 1.5rem; align-items: center; }
  .rooms.is-pinned .tag.is-spine::before { left: 50%; margin-left: -0.75rem; }
  .rooms.is-pinned .tag.is-spine .tag__code { writing-mode: vertical-rl; transform: rotate(180deg); font-size: 2.5rem; }
  .rooms.is-pinned .tag .tag__count, .rooms.is-pinned .tag .tag__types, .rooms.is-pinned .tag .tag__foot { transition: opacity 300ms ease; }
}
@media (hover: hover) and (pointer: fine) { .tag:hover .tag__go { text-decoration-thickness: 0.15em; } .tag:active { transform: scale(0.99); } }

/* ---------- rates: the banknote ---------- */
.rates { display: flex; justify-content: center; }
.note { position: relative; display: grid; grid-template-columns: 7fr 3fr; width: min(100%, 76rem); background: var(--paper); color: var(--paper-ink); }
.note::before, .note::after { content: ""; position: absolute; left: 70%; width: 28px; height: 28px; margin-left: -14px; border-radius: 50%; background: var(--bg); }
.note::before { top: -14px; } .note::after { bottom: -14px; }
.note__main { position: relative; padding: clamp(1.5rem, 3vw, 2.75rem); display: flex; flex-direction: column; gap: 1.5rem; }
.note__main h2 { font-size: clamp(2.4rem, 4vw, 4rem); line-height: 0.95; padding-right: clamp(5.5rem, 9vw, 8.5rem); }
.note__seal { position: absolute; top: clamp(1.5rem, 3vw, 2.75rem); right: clamp(1.5rem, 3vw, 2.75rem); width: clamp(5rem, 8vw, 7.5rem); border-radius: 50%; }
.note__terms { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem 2rem; margin-top: auto; font-size: 1.0625rem; }
.note__terms li { max-width: 32ch; }
.note__stub { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 0.5rem; padding: clamp(1.5rem, 3vw, 2.75rem) 1.25rem; border-left: 6px dotted var(--bg); }
.note__total { font-size: clamp(3rem, 5.6vw, 5.5rem); font-weight: 700; line-height: 1; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; }
.note__term { font-weight: 500; }
.note__stub .button { width: 100%; margin-top: 1rem; }

/* ---------- inside: photo tiles with label strips ---------- */
.inside h2, .steps h2, .faq h2, .foot h2 { font-size: 2.5rem; line-height: 1; }
.tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px 12px; margin-top: 2.5rem; }
.tile img { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; }
.tile figcaption { display: grid; gap: 0.3rem; min-height: 5.5rem; padding: 0.75rem; font-size: var(--step-small); font-variant-numeric: tabular-nums; }
.tile figcaption span { display: flex; justify-content: space-between; gap: 0.5rem; }
.tile b { font-weight: 700; }
.tile:nth-child(4n+1) figcaption { background: var(--paper-ink); color: var(--paper); }
.tile:nth-child(4n+2) figcaption { background: var(--paper-2); color: var(--paper-ink); }
.tile:nth-child(4n+3) figcaption { background: var(--tone-3); color: var(--paper); }
.tile:nth-child(4n+4) figcaption { background: #cfd8bf; color: var(--paper-ink); }
.tile__pic { display: block; overflow: hidden; }
@media (hover: hover) and (pointer: fine) { .tile img { transition: transform 900ms var(--ease-soft); } .tile:hover img { transform: scale(1.04); } }

/* ---------- location: the walking sheet beside the map ---------- */
.walk { display: grid; grid-template-columns: 5fr 7fr; gap: var(--gutter); align-items: stretch; }
.sheet { background: var(--paper); color: var(--paper-ink); padding: clamp(1.75rem, 4vw, 3.75rem); clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); }
.sheet h2 { font-size: clamp(2.4rem, 3.6vw, 3.5rem); line-height: 0.95; max-width: 10ch; }
.sheet__list { margin-top: 2rem; display: grid; gap: 1.25rem; }
.sheet__list li { display: grid; grid-template-columns: 7.5rem 1fr; align-items: baseline; gap: 1rem; }
.sheet__min { font-size: 2rem; font-weight: 700; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; white-space: nowrap; }
.sheet__tear { margin-top: 2.25rem; padding-top: 1.5rem; border-top: 6px dotted var(--bg); }
.sheet__tear a { font-weight: 600; }
#map { min-height: 34rem; height: 100%; background: var(--surface); z-index: 0; }

/* ---------- steps: drag row of moments ---------- */
.steps__top { display: flex; justify-content: space-between; align-items: end; gap: 1rem; }
.steps__nav { display: flex; gap: 8px; }
.steps__nav button { width: 3rem; height: 3rem; background: var(--paper-ink); color: var(--paper); font-size: 1.25rem; transition: background-color var(--t-hover) ease, transform var(--t-press) var(--ease-out); }
.steps__nav button:active { transform: scale(0.94); }
@media (hover: hover) and (pointer: fine) { .steps__nav button:hover { background: var(--tone-3); } }
.steps__bar { height: 6px; margin-top: 1.5rem; background: var(--paper-2); }
.steps__bar span { display: block; height: 100%; width: 20%; background: var(--paper-ink); transform-origin: left; }
.steps__row { display: flex; gap: 32px; margin-top: 2rem; padding-bottom: 1rem; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; }
.steps__row::-webkit-scrollbar { display: none; }
.step { flex: 0 0 17.5rem; scroll-snap-align: start; min-width: 0; display: flex; flex-direction: column; }
.step h3 { font-size: 1.25rem; letter-spacing: -0.01em; }
.step p { margin-top: 0.5rem; font-size: 1.0625rem; }
.step img { margin-top: auto; width: 100%; aspect-ratio: 4 / 5; object-fit: cover; }
.step:nth-child(1) .step__gap { height: 120px; } .step:nth-child(2) .step__gap { height: 80px; }
.step:nth-child(3) .step__gap { height: 140px; } .step:nth-child(4) .step__gap { height: 96px; }
.step:nth-child(5) .step__gap { height: 132px; } .step:nth-child(6) .step__gap { height: 88px; }

/* ---------- questions beside a tall photograph ---------- */
.faq { display: grid; grid-template-columns: 5fr 7fr; gap: var(--gutter); background: var(--surface); padding: 0 var(--margin); }
.faq__text { padding-block: calc(var(--section) / 2); }

.faq__facts { margin-top: 1.5rem; display: grid; gap: 0.4rem; font-size: 1.125rem; }
.faq__facts a { font-weight: 600; }
.faq details { margin-top: 1.75rem; }
.faq summary { position: relative; list-style: none; cursor: pointer; padding-right: 2.5rem; font-size: 1.25rem; font-weight: 600; min-height: 2.75rem; display: flex; align-items: center; }
.faq summary::-webkit-details-marker { display: none; }
.faq summary::after { content: ""; position: absolute; right: 0.6rem; top: 50%; width: 0.6rem; height: 0.6rem; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: translateY(-70%) rotate(45deg); transition: transform var(--t-ui) var(--ease-out); }
.faq details[open] summary::after { transform: translateY(-30%) rotate(-135deg); }
.faq details p { margin-top: 0.5rem; font-size: 1.0625rem; }
.faq__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 1.5rem; margin-top: 2.5rem; }
.faq__pics { position: relative; padding-bottom: 80px; }
.faq__tall { width: 100%; height: 100%; min-height: 40rem; object-fit: cover; object-position: center 75%; }
.faq__square { position: absolute; left: -40px; bottom: 40px; width: 34%; aspect-ratio: 1; object-fit: cover; }

/* ---------- visit card on the night photograph ---------- */
.visit { position: relative; min-height: 100svh; display: flex; justify-content: flex-end; align-items: flex-start; padding: 60px var(--margin); }
.visit > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.card { position: relative; width: 24rem; background: var(--paper); color: var(--paper-ink); padding: 2rem 2rem 0; z-index: 1; }
.card h2 { font-size: 2rem; line-height: 1.05; }
.card address { margin-top: 1rem; font-style: normal; }
.card dl { margin-top: 1.25rem; display: grid; grid-template-columns: auto 1fr; gap: 0.4rem 1rem; }
.card dd { text-align: right; font-variant-numeric: tabular-nums; }
.card dd a { font-weight: 600; }
.card .button { width: calc(100% + 4rem); margin: 1.75rem -2rem 0; min-height: 3.5rem; }

/* ---------- footer: the back of the card ---------- */
.foot { padding: 6rem var(--margin) var(--margin); }
.foot__panel { position: relative; width: min(100%, 76rem); margin: 0 auto; background: var(--paper); color: var(--paper-ink); padding: 4.5rem clamp(1.5rem, 4vw, 4rem) 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem 4rem; }
.foot__scallops { position: absolute; left: 0; top: -24px; width: 100%; height: 25px; color: var(--paper); }

.foot__msg { margin-top: 1rem; }
.foot__links { display: grid; gap: 12px; font-size: 1.125rem; align-content: start; }
.foot__links a { font-weight: 500; }
.foot__stamp { position: absolute; top: 2.75rem; right: clamp(1.5rem, 4vw, 4rem); width: 5.5rem; padding: 6px; background: var(--paper-2); }
.foot__stamp img { border-radius: 50%; }
.foot__imprint { grid-column: 1 / -1; max-width: none; text-align: center; font-size: var(--step-small); color: var(--paper-muted); }
.foot__imprint a { color: inherit; }

/* ---------- phone compositions ---------- */
@media (max-width: 64em) {
  .walk { grid-template-columns: 1fr; }
  #map { min-height: 22rem; }
  .tiles { grid-template-columns: repeat(2, 1fr); gap: 20px 8px; }
  .faq { grid-template-columns: 1fr; }
  .faq__pics { order: -1; padding: 0; margin: 0 calc(var(--margin) * -1); }
  .faq__tall { min-height: 0; aspect-ratio: 4 / 5; }
  .faq__square { left: auto; right: var(--margin); bottom: -2rem; width: 38%; }
}
@media (max-width: 48em) {
  .hero { grid-template-columns: 1fr; gap: 0; }
  .hero__photo { order: -1; position: relative; top: 0; height: 48svh; }
  .hero__card { margin-top: -24px; position: relative; z-index: 1; }
  .hero__card .stripe { height: 2.5rem; }
  .hero h1 { font-size: clamp(2.4rem, 10vw, 3.25rem); }
  .hero__links { flex-wrap: wrap; justify-content: center; gap: 1rem 1.5rem; }
  .rooms__deck { gap: 28px; }
  .tag { min-height: 0; padding: 1.5rem 1.5rem 1.5rem 3.75rem; }
  .tag::before { left: 1.25rem; top: 1.6rem; width: 1.25rem; height: 1.25rem; }
  .tag__code { font-size: 2.75rem; }
  .note { grid-template-columns: 1fr; }
  .note::before, .note::after { left: auto; top: 70%; margin: -14px 0 0; }
  .note::before { left: -14px; } .note::after { right: -14px; bottom: auto; }
  .note__stub { border-left: 0; border-top: 6px dotted var(--bg); padding-top: 3rem; }
  .note__terms { margin-bottom: 1rem; }
  .note__main h2 { font-size: 9vw; padding-right: 5rem; }
  .note__total { font-size: 16vw; }
  .note__terms { grid-template-columns: 1fr; }
  .note__seal { width: 4.5rem; }
  .steps__nav { display: none; }
  .step { flex-basis: calc((100% - 32px) / 2); }
  .step:nth-child(n) .step__gap { height: 40px; } .step:nth-child(odd) .step__gap { height: 64px; }
  .visit { min-height: 0; flex-direction: column; padding: 0; }
  .visit > img { position: relative; height: 70svh; }
  .card { position: relative; top: 0; width: auto; margin: -80px 16px 0; }
  .card .button { min-height: 3.5rem; }
  .foot__panel { grid-template-columns: 1fr; }
  .foot__stamp { width: 4rem; top: 2.5rem; }
  .foot h2 { padding-right: 4.5rem; }
}
@media (max-width: 40em) { .step { flex-basis: 72vw; } }
@media (prefers-reduced-motion: reduce) { .hero__photo img { transition: none; } }
</style>
</head>
<body>

<header class="bar">
  <a class="bar__brand" href="index.php"><img src="image/logo.jpg" alt="" width="40" height="40">Dollar Dreams</a>
  <nav class="bar__links" aria-label="Main">
    <a href="#rooms">Rooms</a>
    <a href="#reserve">Reserve</a>
    <a href="#location">Location</a>
    <a href="#questions">Questions</a>
    <a class="button button--small" href="book.php">Book a room</a>
  </nav>
</header>

<main>

<!-- HERO: the printed card beside the room -->
<section class="hero">
  <div class="hero__card on-paper">
    <div class="stripe" aria-hidden="true"><?php for ($i = 0; $i < 40; $i++) echo '<span></span>'; ?></div>
    <video class="hero__seal" autoplay loop muted playsinline poster="image/logo.jpg" aria-hidden="true" data-rise style="--i:0">
      <source src="image/dddloaading.mp4" type="video/mp4">
    </video>
    <h1 data-rise style="--i:1">Rooms on Taft Avenue</h1>
    <p class="hero__lead" data-rise style="--i:2">Solo rooms, couple rooms and bedspace in a Malate high-rise, minutes on foot from PGH and Pedro Gil LRT. No deposit.</p>
    <a class="button" href="book.php" data-rise style="--i:3">See free rooms</a>
    <?php if ($kinds): ?>
      <p class="hero__free" data-rise style="--i:4"><?= $freeToday ?> <?= $freeToday === 1 ? 'bed or room' : 'beds and rooms' ?> free today</p>
    <?php endif; ?>
    <div class="hero__links">
      <a href="#reserve">How to reserve</a>
      <a href="#location">Where we are</a>
      <a href="book.php#viewing">View it first</a>
    </div>
  </div>

  <figure class="hero__photo" data-crossfade>
    <img class="is-on" src="image/25a16-17-coupleroomwithcr&amp;ac.jpg" alt="Couple room with its own CR and aircon" width="1600" height="1200" data-caption="Couple room with its own CR">
    <img src="image/16a07-livingroom.jpg" alt="Shared living room with city view" width="1600" height="1200" data-caption="Shared living room" loading="lazy">
    <img src="image/20c19-bedspace4pax.jpg" alt="Four-bed bedspace" width="1600" height="1200" data-caption="Bedspace for four" loading="lazy">
    <img src="image/16a07-kitchen.jpg" alt="Shared kitchen with dining table" width="1600" height="1200" data-caption="Shared kitchen" loading="lazy">
    <figcaption class="hero__caption">Couple room with its own CR</figcaption>
  </figure>
</section>

<!-- ROOMS: one key tag per kind of room, with what is free and what is booked today -->
<section class="rooms section on-paper" id="rooms">
  <div class="rooms__pin">
    <div class="rooms__stage wrap">
      <h2 data-reveal>The rooms</h2>
      <p class="rooms__intro" data-reveal style="--i:1">Free and booked rooms update as guests book. Pick a kind of room to see every one.</p>
      <div class="rooms__deck">
        <?php if ($kinds): $n = 0; foreach ($kinds as $key => $k): $booked = $k['total'] - $k['free']; ?>
          <a class="tag" href="book.php?show=<?= $key ?>#units" data-reveal style="--i:<?= $n++ ?>">
            <span class="tag__code"><?= e($k['name']) ?></span>
            <span class="tag__count"><?= $k['beds'] ? $k['total'] . ' beds in ' . $k['rooms'] . ($k['rooms'] === 1 ? ' room' : ' rooms') : $k['rooms'] . ($k['rooms'] === 1 ? ' room' : ' rooms') ?></span>
            <span class="tag__types"><?= e($kindLine[$key]) ?></span>
            <span class="tag__foot">
              <span class="tag__free"><?= $k['free'] ?> free · <?= $booked ?> booked today</span>
              <span class="tag__go">See them</span>
            </span>
          </a>
        <?php endforeach; else: ?>
          <a class="tag" href="book.php">
            <span class="tag__code">Rooms</span>
            <span class="tag__types">Bedspace, solo rooms and couple rooms.</span>
            <span class="tag__foot"><span class="tag__free">Live availability</span><span class="tag__go">Open the booking page</span></span>
          </a>
        <?php endif; ?>
      </div>
      <p class="rooms__count"><?= $freeToday ?> free today on Taft Avenue · <a class="link" href="book.php">See every room and bed</a></p>
    </div>
  </div>
</section>

<!-- RESERVE: the banknote, with today's free count on its stub -->
<section class="rates section wrap" id="reserve">
  <div class="note" data-reveal>
    <div class="note__main">
      <h2>Reserve, no deposit</h2>
      <img class="note__seal" src="image/logo.jpg" alt="" width="120" height="120">
      <ul class="note__terms">
        <li><b>Stay as long as you need:</b> by the day, the week or the month.</li>
        <li><b>Pay the reservation fee</b> by GCash or InstaPay. The QR code is on the booking form.</li>
        <li><b>Upload a valid ID</b> and your receipt, as a photo or a PDF.</li>
        <li><b>We call you</b> to confirm your room. The fee comes off your balance.</li>
      </ul>
    </div>
    <div class="note__stub">
      <output class="note__total"><?= $kinds ? $freeToday : '—' ?></output>
      <span class="note__term"><?= $freeToday === 1 ? 'bed or room' : 'beds and rooms' ?> free today</span>
      <a class="button button--green" href="book.php">Reserve a room</a>
    </div>
  </div>
</section>

<!-- INSIDE: the units' own photographs -->
<section class="inside section wrap on-paper" id="inside">
  <h2 data-reveal>Inside the units</h2>
  <div class="tiles">
    <?php foreach ($tiles as $i => [$file, $room, $a, $b]): ?>
      <figure class="tile" data-reveal style="--i:<?= $i % 4 ?>">
        <span class="tile__pic"><img src="image/<?= e(rawurlencode($file)) ?>" alt="<?= e("$room: $a, $b") ?>" width="800" height="1000" loading="lazy"></span>
        <figcaption>
          <span><b><?= e($room) ?></b><span><?= e($a) ?></span></span>
          <span><span><?= e($b) ?></span><span>Taft Ave, Malate</span></span>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>

<!-- LOCATION: minutes on foot, beside the live map -->
<section class="walk section wrap" id="location">
  <div class="sheet" data-reveal>
    <h2>Five minutes to PGH</h2>
    <ul class="sheet__list">
      <li><span class="sheet__min">3–5 min</span><span>Philippine General Hospital</span></li>
      <li><span class="sheet__min">5–7 min</span><span>Pedro Gil station, LRT-1</span></li>
      <li><span class="sheet__min">6–8 min</span><span>Robinsons Place Manila</span></li>
      <li><span class="sheet__min">3–10 min</span><span>Agencies and offices along Taft</span></li>
    </ul>
    <p class="sheet__tear">1655 Taft Avenue, Malate, City of Manila · <a class="link" href="https://www.openstreetmap.org/?mlat=14.5764&amp;mlon=120.9910#map=17/14.5764/120.9910" target="_blank" rel="noopener">Open the map</a></p>
  </div>
  <div id="map" data-ui role="img" aria-label="Map of Malate showing the dormitory, PGH, Pedro Gil station and Robinsons Place Manila" data-reveal style="--i:1"></div>
</section>

<!-- STEPS: viewing to keys -->
<section class="steps section wrap on-paper" id="steps">
  <div class="steps__top">
    <h2>From viewing to keys</h2>
    <div class="steps__nav">
      <button type="button" data-dir="-1" aria-label="Back">←</button>
      <button type="button" data-dir="1" aria-label="Forward">→</button>
    </div>
  </div>
  <div class="steps__bar" aria-hidden="true"><span></span></div>
  <div class="steps__row" tabindex="0" aria-label="How booking works">
    <article class="step" data-reveal style="--i:0"><h3>First, pick a viewing slot</h3><p>Choose a time on the booking page. We contact you to confirm it.</p><div class="step__gap"></div><img src="image/FB_IMG_1787995625455.jpg" alt="Living area with kitchen counter" width="560" height="700" loading="lazy"></article>
    <article class="step" data-reveal style="--i:1"><h3>Then see the unit</h3><p>Walk through the room, the kitchen and the CR before you decide.</p><div class="step__gap"></div><img src="image/16a07-livingroom.jpg" alt="Shared living room" width="560" height="700" loading="lazy"></article>
    <article class="step" data-reveal style="--i:2"><h3>Choose your dates</h3><p>By the day, the week or the month. Pick a free room on the booking page.</p><div class="step__gap"></div><img src="image/25a16-17-coupleroomcommoncrwithac.jpg" alt="Couple room with aircon" width="560" height="700" loading="lazy"></article>
    <article class="step" data-reveal style="--i:3"><h3>Pay the reservation fee</h3><p>By GCash or InstaPay, then upload the receipt. No deposit.</p><div class="step__gap"></div><img src="image/QRDDD.jpg" alt="Dollar Dreams payment QR code" width="560" height="700" loading="lazy"></article>
    <article class="step" data-reveal style="--i:4"><h3>Send a valid ID</h3><p>Any government ID, as a photo or a PDF up to 5 MB. We call you to confirm.</p><div class="step__gap"></div><img src="image/FB_IMG_1787995734081.jpg" alt="Window table in a common area" width="560" height="700" loading="lazy"></article>
    <article class="step" data-reveal style="--i:5"><h3>Last, move in</h3><p>Come on your move-in date. Your reservation fee is already off the balance.</p><div class="step__gap"></div><img src="image/19c12-coupleroomwithcr.jpg" alt="Couple room with its own CR" width="560" height="700" loading="lazy"></article>
  </div>
</section>

<!-- QUESTIONS beside a tall photograph -->
<section class="faq" id="questions">
  <div class="faq__text">
    <h2 data-reveal>Good to know</h2>
    <div class="faq__facts" data-reveal style="--i:1">
      <span>1655 Taft Ave, Malate, City of Manila</span>
      <span>Call or text <a class="link" href="tel:+639935103318">0993 510 3318</a> or <a class="link" href="tel:+639662291677">0966 229 1677</a></span>
      <span>Viewings by appointment, from the booking page</span>
    </div>
    <details data-reveal><summary>Is there a deposit?</summary><p>No. You pay a small reservation fee when you book, and it comes off your balance.</p></details>
    <details data-reveal><summary>How long can I stay?</summary><p>By the day, the week or the month. Choose your move-in and move-out dates when you book, and we confirm your rate when we call.</p></details>
    <details data-reveal><summary>How do I know what is free?</summary><p>The booking page shows every room as free, reserved or booked, and it updates as guests book.</p></details>
    <details data-reveal><summary>What do I need to book?</summary><p>Your name, a mobile number, a valid government ID and the GCash receipt for the reservation fee, as JPG, PNG or PDF up to 5 MB each.</p></details>
    <details data-reveal><summary>Can I see the room first?</summary><p>Yes. Pick a viewing slot on the booking page and we contact you to confirm it.</p></details>
    <details data-reveal><summary>What is a bedspace?</summary><p>A bed in a shared room. The booking page shows how many beds in each room are still free today.</p></details>
    <div class="faq__actions">
      <a class="button" href="book.php">See free rooms</a>
      <a class="link" href="tel:+639935103318">Call 0993 510 3318</a>
    </div>
  </div>
  <div class="faq__pics">
    <img class="faq__tall" src="image/FB_IMG_1787995613242.jpg" alt="Dining area with a framed dollar bill on the wall" width="900" height="1200" loading="lazy">
    <img class="faq__square" src="image/FB_IMG_1787995595300.jpg" alt="Living room with a city view" width="400" height="400" loading="lazy">
  </div>
</section>

<!-- VISIT card on the night view -->
<section class="visit" id="visit">
  <img src="image/herobg.jpg" alt="Malate at night, seen from the building" width="1600" height="1000" loading="lazy">
  <div class="card">
    <h2>Come and see it</h2>
    <address>1655 Taft Avenue<br>Malate, City of Manila</address>
    <dl>
      <dt>Viewings</dt><dd>By appointment</dd>
      <dt>Mobile</dt><dd><a class="link" href="tel:+639935103318">0993 510 3318</a></dd>
      <dt>Mobile</dt><dd><a class="link" href="tel:+639662291677">0966 229 1677</a></dd>
    </dl>
    <a class="button button--green" href="book.php#viewing">Pick a viewing slot</a>
  </div>
</section>

</main>

<!-- FOOTER: the back of the card -->
<footer class="foot">
  <div class="foot__panel">
    <svg class="foot__scallops" viewBox="0 0 1200 50" preserveAspectRatio="none" aria-hidden="true"><g fill="currentColor"><circle cx="50" cy="50" r="50"/><circle cx="150" cy="50" r="50"/><circle cx="250" cy="50" r="50"/><circle cx="350" cy="50" r="50"/><circle cx="450" cy="50" r="50"/><circle cx="550" cy="50" r="50"/><circle cx="650" cy="50" r="50"/><circle cx="750" cy="50" r="50"/><circle cx="850" cy="50" r="50"/><circle cx="950" cy="50" r="50"/><circle cx="1050" cy="50" r="50"/><circle cx="1150" cy="50" r="50"/></g></svg>
    <div>
      <h2>See you on Taft</h2>
      <p class="foot__msg">Dollar Dreams Dormitory rents bedspace, solo and couple rooms by the day, the week or the month, in a high-rise on Taft Avenue.</p>
    </div>
    <div class="foot__links">
      <a class="link" href="tel:+639935103318">0993 510 3318</a>
      <a class="link" href="tel:+639662291677">0966 229 1677</a>
      <a class="link" href="#location">1655 Taft Ave, Malate, Manila</a>
      <a class="link" href="book.php">Book a room or a viewing</a>
    </div>
    <div class="foot__stamp"><img src="image/logo.jpg" alt="Dollar Dreams Dormitory seal" width="80" height="80" loading="lazy"></div>
    <p class="foot__imprint">© 2026 Dollar Dreams Dormitory · Map © OpenStreetMap contributors · <a href="signin.php">Staff sign in</a></p>
  </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/site.js"></script>
<script>
const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- hero: slow crossfade between the rooms ---------- */
(() => {
  const box = document.querySelector('[data-crossfade]');
  const imgs = [...box.querySelectorAll('img')];
  const cap = box.querySelector('figcaption');
  if (reduced || imgs.length < 2) return;
  let i = 0;
  setInterval(() => {
    if (document.hidden) return;
    imgs[i].classList.remove('is-on');
    i = (i + 1) % imgs.length;
    imgs[i].classList.add('is-on');
    cap.textContent = imgs[i].dataset.caption;
  }, 5500);
})();

/* ---------- rooms: key tags fold into spines while the stage is pinned (desktop) ---------- */
(() => {
  const sec = document.querySelector('.rooms');
  const tags = [...sec.querySelectorAll('.tag')];
  const wide = matchMedia('(min-width: 64em)');
  if (tags.length < 3 || reduced) return;
  const FULL = 620, SPINE = 120;
  function setup() {
    sec.classList.toggle('is-pinned', wide.matches);
    sec.style.setProperty('--pin-h', Math.min(220, 100 + (tags.length - 1) * 30) + 'svh');
    update();
  }
  function update() {
    if (!wide.matches) { tags.forEach(t => { t.style.removeProperty('--w'); t.classList.remove('is-spine'); }); return; }
    const pin = sec.querySelector('.rooms__pin');
    const r = pin.getBoundingClientRect();
    const travel = r.height - (innerHeight - 64);
    const p = Math.min(1, Math.max(0, -r.top / Math.max(1, travel))) * (tags.length - 2);
    tags.forEach((t, i) => {
      let w = FULL;
      if (i < Math.floor(p)) w = SPINE;
      else if (i === Math.floor(p)) w = FULL - (FULL - SPINE) * (p - i);
      t.style.setProperty('--w', w + 'px');
      t.classList.toggle('is-spine', w < 260);
    });
  }
  // a click on a spine brings its card back
  tags.forEach((t, i) => t.addEventListener('click', (e) => {
    if (!t.classList.contains('is-spine')) return;
    e.preventDefault();
    const pin = sec.querySelector('.rooms__pin');
    const travel = pin.offsetHeight - (innerHeight - 64);
    scrollTo({ top: pin.getBoundingClientRect().top + scrollY + travel * (i / (tags.length - 2)), behavior: 'smooth' });
  }));
  addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
  wide.addEventListener('change', setup);
  setup();
})();

/* ---------- steps: buttons and the progress bar ---------- */
(() => {
  const row = document.querySelector('.steps__row');
  const bar = document.querySelector('.steps__bar span');
  const sync = () => {
    const max = row.scrollWidth - row.clientWidth;
    const seen = row.clientWidth / row.scrollWidth;
    bar.style.width = (seen * 100) + '%';
    bar.style.transform = 'translateX(' + (max > 0 ? (row.scrollLeft / max) * ((1 - seen) / seen) * 100 : 0) + '%)';
  };
  document.querySelectorAll('.steps__nav button').forEach(b => b.addEventListener('click', () => row.scrollBy({ left: +b.dataset.dir * 312, behavior: reduced ? 'auto' : 'smooth' })));
  row.addEventListener('scroll', () => requestAnimationFrame(sync), { passive: true });
  addEventListener('resize', sync);
  sync();
})();

/* ---------- map ---------- */
(() => {
  if (!window.L) return;
  const map = L.map('map', { scrollWheelZoom: false }).setView([14.5772, 120.9888], 16);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' }).addTo(map);
  const home = [14.5764, 120.9910];
  L.marker(home).addTo(map).bindPopup('<b>Dollar Dreams Dormitory</b><br>1655 Taft Ave, Malate').openPopup();
  [[[14.5791, 120.9886], 'Philippine General Hospital'], [[14.5760, 120.9900], 'Pedro Gil station, LRT-1'], [[14.5780, 120.9820], 'Robinsons Place Manila']]
    .forEach(([ll, name]) => { L.marker(ll).addTo(map).bindPopup(name); L.polyline([home, ll], { color: '#1f4a2e', weight: 4, opacity: 0.85 }).addTo(map); });
})();
</script>
</body>
</html>
