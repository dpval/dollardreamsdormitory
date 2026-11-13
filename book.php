<?php
/* Public booking page. Rooms and viewing slots load from load.php; forms post to save.php. */
require_once __DIR__ . '/functions.php';   // rates only, no database needed to render the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a room · Dollar Dreams Dormitory</title>
<meta name="description" content="See which beds and rooms are free today at Dollar Dreams Dormitory on Taft Avenue, then reserve on GCash or book a viewing.">
<script>document.documentElement.classList.add('js')</script>
<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://cdn.fontshare.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/site.css">
<style>
/* ---------- opening: title on the left, the viewing slip on the right ---------- */
.intro { display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); gap: var(--gutter); align-items: end; padding-top: clamp(3rem, 7vw, 6rem); }
.intro h1 { font-size: var(--step-display); line-height: 0.92; }
.intro__lead { margin-top: 1.5rem; font-size: var(--step-lead); line-height: 1.35; max-width: 36ch; }
.slip { background: var(--paper); color: var(--paper-ink); }
.slip .stripe { height: 1.25rem; }
.slip .stripe span { flex-basis: 1.25rem; }
.slip__body { padding: 1.5rem 1.75rem 1.75rem; }
.slip h2 { font-size: 2rem; line-height: 1; }
.slip p { margin-top: 0.5rem; }
.slip select { margin-top: 1rem; cursor: pointer; }

/* ---------- filters ---------- */
.filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-top: clamp(3rem, 6vw, 5rem); }
.filters__label { margin-right: 0.75rem; font-weight: 500; }
.filters button { min-height: 2.75rem; padding: 0 1.1rem; background: var(--surface); color: var(--ink); font-weight: 500; transition: background-color var(--t-hover) ease, color var(--t-hover) ease, transform var(--t-press) var(--ease-out); }
.filters button[aria-pressed=true] { background: var(--paper); color: var(--paper-ink); }
.filters button:active { transform: scale(0.96); }
@media (hover: hover) and (pointer: fine) { .filters button:not([aria-pressed=true]):hover { background: var(--tone-1); } }
.filters__count { margin-left: auto; color: var(--muted); }

/* ---------- the rooms: one key tag per door ---------- */
.carousel { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 22rem), 1fr)); gap: 28px 12px; margin-top: 1.5rem; }
.carousel > p { grid-column: 1 / -1; font-size: var(--step-lead); }
.unit { display: flex; flex-direction: column; background: var(--paper); color: var(--paper-ink); }
.unit[hidden] { display: none; }
.image-wrapper { position: relative; overflow: hidden; background: var(--paper-2); }
.main-image { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; cursor: zoom-in; transition: transform 900ms var(--ease-soft), opacity 300ms ease; }
@media (hover: hover) and (pointer: fine) { .image-wrapper:hover .main-image { transform: scale(1.04); } }
.img-overlay-badge { position: absolute; left: 0; top: 0; z-index: 1; padding: 0.5rem 1rem; font-weight: 700; background: var(--paper-ink); color: var(--paper); }
.img-overlay-badge.reserved { background: #e8c27a; color: #2b2210; }
.unit__body { position: relative; display: flex; flex-direction: column; flex: 1; padding: 1.5rem 1.5rem 1.5rem 3.75rem; }
.unit__body::before { content: ""; position: absolute; left: 1.35rem; top: 1.85rem; width: 1.25rem; height: 1.25rem; border-radius: 50%; background: var(--bg); } /* key-ring hole */
.unit h3 { font-size: clamp(2.5rem, 3.4vw, 3.25rem); line-height: 0.9; letter-spacing: -0.03em; }
.unit__count { margin-top: 0.4rem; font-weight: 500; }
.status-badges { display: flex; flex-wrap: wrap; gap: 0.35rem 1.25rem; margin-top: 1rem; font-variant-numeric: tabular-nums; }
.badge-avail { font-weight: 700; }
.unit h4 { margin-top: 1.25rem; font-size: 1.25rem; line-height: 1.2; }
.thumbnails { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 1rem; }
.thumb-box { position: relative; }
.thumbnails img { width: 4.5rem; height: 4.5rem; object-fit: cover; cursor: pointer; opacity: 0.65; outline: 3px solid transparent; outline-offset: -3px; transition: opacity var(--t-hover) ease, outline-color var(--t-hover) ease, transform var(--t-press) var(--ease-out); }
.thumbnails img.active { opacity: 1; outline-color: var(--paper-ink); }
.thumbnails img:active { transform: scale(0.94); }
@media (hover: hover) and (pointer: fine) { .thumbnails img:hover { opacity: 1; } }
.thumb-status-dot { position: absolute; right: 4px; top: 4px; width: 0.75rem; height: 0.75rem; border-radius: 50%; background: #c0392b; }
.thumb-status-dot.reserved { background: #d49a2a; }
.book-btn { margin-top: auto; }
.unit__foot { margin-top: 1.5rem; }
.unit__foot .button { width: 100%; }

/* ---------- dialogs: the booking sheet and the viewing slip ---------- */
.overlay { display: none; }
.sheet-wrap { position: fixed; inset: 0; z-index: 100; display: none; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 4vh var(--margin); background: rgba(8, 18, 12, 0.72); }
.sheet-wrap.is-open { display: flex; }
.modal-content { position: relative; width: min(100%, 44rem); background: var(--paper); color: var(--paper-ink); box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35); } /* taste-skill-ignore: a dialog really floats */
@media (prefers-reduced-motion: no-preference) {
  .sheet-wrap.is-open .modal-content { animation: sheet-in 420ms var(--ease-out); }
}
@keyframes sheet-in { from { opacity: 0; transform: translateY(28px); } }
.modal-content .stripe { height: 1.25rem; }
.modal-content .stripe span { flex-basis: 1.25rem; }
.modal-body { padding: clamp(1.5rem, 4vw, 2.5rem); }
.modal-content h3 { font-size: 2.25rem; line-height: 1; padding-right: 3rem; }
.close-btn { position: absolute; top: 2rem; right: 1rem; width: 3rem; height: 3rem; display: grid; place-items: center; color: var(--paper-ink); transition: transform var(--t-press) var(--ease-out); }
.close-btn:active { transform: scale(0.9); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
.form-grid .full { grid-column: 1 / -1; }
.picked { display: grid; grid-template-columns: repeat(2, auto); justify-content: start; gap: 0 2rem; margin-top: 1.25rem; }
.picked .field-label { margin-top: 0; }
.unit__desc { margin-top: 0.75rem; }
.pay { display: grid; grid-template-columns: 9rem 1fr; gap: 1.25rem; align-items: center; margin-top: 1.5rem; padding: 1.25rem; background: var(--paper-2); }
.pay img { width: 100%; }
.modal-content button[type=submit] { width: 100%; margin-top: 1.75rem; }
.done { display: none; padding-top: 1rem; }
.done p { margin-top: 0.75rem; font-size: var(--step-lead); }
.done .button { margin-top: 1.5rem; }
.modal-content.is-done form { display: none; }
.modal-content.is-done .done { display: block; }

/* zoomed photo */
#imageModal { position: fixed; inset: 0; z-index: 120; display: none; align-items: center; justify-content: center; padding: var(--margin); background: rgba(8, 18, 12, 0.9); cursor: zoom-out; }
#imageModal img { max-width: 100%; max-height: 92vh; object-fit: contain; }

/* ---------- footer ---------- */
.foot { margin-top: var(--section); background: var(--paper); color: var(--paper-ink); display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 3rem var(--margin); }
.foot h2 { font-size: 2.5rem; line-height: 1; }
.foot__links { display: grid; gap: 12px; font-size: 1.125rem; align-content: start; }
.foot__links a { font-weight: 500; }
.foot__imprint { grid-column: 1 / -1; font-size: var(--step-small); color: var(--paper-muted); }

@media (max-width: 52em) {
  .intro { grid-template-columns: minmax(0, 1fr); }
  .filters__count { margin-left: 0; width: 100%; }
  .form-grid, .picked { grid-template-columns: 1fr; }
  .pay { grid-template-columns: 7rem 1fr; }
  .foot { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<header class="bar">
  <a class="bar__brand" href="index.php"><img src="image/logo.jpg" alt="" width="40" height="40">Dollar Dreams</a>
  <nav class="bar__links" aria-label="Main">
    <a href="index.php#reserve">How to reserve</a>
    <a href="index.php#location">Location</a>
    <a href="index.php#questions">Questions</a>
    <a class="button button--small" href="#units">See rooms</a>
  </nav>
</header>

<main class="wrap">

  <section class="intro">
    <div>
      <h1 data-rise style="--i:0">Choose a room</h1>
      <p class="intro__lead" data-rise style="--i:1">Free beds and rooms are counted live. Pick one, choose your dates and reserve it with a small fee on GCash. No deposit.</p>
    </div>

    <!-- VIEWING SCHEDULE -->
    <div class="slip" id="viewing" data-rise style="--i:2">
      <div class="stripe" aria-hidden="true"><?php for ($i = 0; $i < 30; $i++) echo '<span></span>'; ?></div>
      <div class="slip__body">
        <h2>See it first</h2>
        <p>Pick a time to visit the unit. We contact you to confirm it.</p>
        <label class="sr-only" for="scheduleTop">Viewing slot</label>
        <select id="scheduleTop" class="field">
          <option value="">Loading viewing slots…</option>
        </select>
      </div>
    </div>
  </section>

  <section id="units-section" aria-labelledby="unitsHeading">
    <h2 class="sr-only" id="unitsHeading">Rooms and beds</h2>
    <div class="filters" role="group" aria-label="Show">
      <span class="filters__label">Show</span>
      <button type="button" data-filter="all" aria-pressed="true">All rooms</button>
      <button type="button" data-filter="bed" aria-pressed="false">Bedspace</button>
      <button type="button" data-filter="solo" aria-pressed="false">Solo</button>
      <button type="button" data-filter="couple" aria-pressed="false">Couple</button>
      <button type="button" data-filter="free" aria-pressed="false">Free now</button>
      <span class="filters__count" id="filterCount" aria-live="polite"></span>
    </div>
    <div class="carousel" id="units"><p>Loading rooms…</p></div>
  </section>

</main>

<div class="overlay"></div>

<!-- IMAGE ZOOM -->
<div id="imageModal" onclick="closeImageModal()" role="dialog" aria-label="Photo">
  <img id="zoomImg" alt="Room photo, enlarged">
</div>

<!-- VIEWING DIALOG -->
<div id="viewingModal" class="sheet-wrap" role="dialog" aria-modal="true" aria-labelledby="viewingTitle">
  <div class="modal-content">
    <div class="stripe" aria-hidden="true"><?php for ($i = 0; $i < 40; $i++) echo '<span></span>'; ?></div>
    <button type="button" class="close-btn" onclick="closeViewing()" aria-label="Close"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    <div class="modal-body">
      <h3 id="viewingTitle">Book a viewing</h3>
      <form id="viewingForm">
        <input type="hidden" name="type" value="viewing">
        <label class="field-label" for="selectedSchedule">Your slot</label>
        <input id="selectedSchedule" class="field" name="schedule" readonly required>
        <div class="form-grid">
          <div>
            <label class="field-label" for="vName">Full name</label>
            <input id="vName" class="field" name="name" autocomplete="name" required>
          </div>
          <div>
            <label class="field-label" for="vMobile">Mobile number</label>
            <input id="vMobile" class="field" name="mobile" type="tel" inputmode="tel" autocomplete="tel" placeholder="09171234567" pattern="(09|\+639)[0-9]{9}" title="11-digit mobile number starting with 09" required>
          </div>
        </div>
        <button type="submit" class="button button--green">Request this viewing</button>
      </form>
      <div class="done" role="status">
        <p>Viewing request sent. We will contact you to confirm it.</p>
        <button type="button" class="button button--green" onclick="closeViewing()">Done</button>
      </div>
    </div>
  </div>
</div>

<!-- BOOKING DIALOG -->
<div id="bookingModal" class="sheet-wrap" role="dialog" aria-modal="true" aria-labelledby="bookingTitle">
  <div class="modal-content">
    <div class="stripe" aria-hidden="true"><?php for ($i = 0; $i < 40; $i++) echo '<span></span>'; ?></div>
    <button type="button" class="close-btn" onclick="closeModal()" aria-label="Close"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    <div class="modal-body">
      <h3 id="bookingTitle">Reserve this room</h3>

      <form id="bookingForm" enctype="multipart/form-data">
        <input type="hidden" name="type" value="booking">
        <input type="hidden" name="unit_id" id="unit_id">

        <div class="picked">
          <div><label class="field-label" for="book_type">Room</label><input name="book_type" id="book_type" class="field" readonly></div>
          <div><label class="field-label" for="description">Details</label><input name="description" id="description" class="field" readonly></div>
        </div>

        <div class="form-grid">
          <div class="full">
            <label class="field-label" for="bName">Full name</label>
            <input id="bName" class="field" name="name" autocomplete="name" required>
          </div>
          <div>
            <label class="field-label" for="bMobile">Mobile number</label>
            <input id="bMobile" class="field" name="mobile" type="tel" inputmode="tel" autocomplete="tel" placeholder="09171234567" pattern="(09|\+639)[0-9]{9}" title="11-digit mobile number starting with 09" required>
          </div>
          <div>
            <label class="field-label" for="bEmail">Email (optional)</label>
            <input id="bEmail" class="field" name="email" type="email" autocomplete="email">
          </div>
          <div>
            <label class="field-label" for="bIn">Move in</label>
            <input id="bIn" class="field" type="date" name="move_in" required>
          </div>
          <div>
            <label class="field-label" for="bOut">Move out</label>
            <input id="bOut" class="field" type="date" name="move_out" required>
          </div>
          <div>
            <label class="field-label" for="bFee">Reservation fee (₱, at least <?= MIN_RESERVATION_FEE ?>)</label>
            <input id="bFee" class="field" type="number" name="reservation_fee" value="<?= MIN_RESERVATION_FEE ?>" min="<?= MIN_RESERVATION_FEE ?>" step="50" required>
          </div>
          <div>
            <label class="field-label" for="total_days">Length of stay</label>
            <input id="total_days" class="field" readonly placeholder="Choose your dates">
          </div>
        </div>

        <div class="pay">
          <img src="image/QRDDD.jpg" alt="GCash and InstaPay QR code for the reservation fee" width="300" height="400" loading="lazy">
          <p>Scan to pay the reservation fee by GCash or InstaPay, then upload the receipt below. We call you to confirm your room and your rate.</p>
        </div>

        <div class="form-grid">
          <div>
            <label class="field-label" for="bId">Valid government ID (JPG, PNG or PDF, up to 5 MB)</label>
            <input id="bId" class="field" type="file" name="id_upload" accept="image/jpeg,image/png,image/webp,application/pdf" required>
          </div>
          <div>
            <label class="field-label" for="bProof">GCash receipt (JPG, PNG or PDF, up to 5 MB)</label>
            <input id="bProof" class="field" type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
          </div>
        </div>

        <button type="submit" class="button button--green" id="bookingSubmit">Send my reservation</button>
      </form>

      <div class="done" role="status">
        <p>Reservation sent. We will contact you to confirm your room.</p>
        <button type="button" class="button button--green" onclick="closeModal()">Done</button>
      </div>
    </div>
  </div>
</div>

<footer class="foot">
  <div>
    <h2>See you on Taft</h2>
    <p>1655 Taft Avenue, Malate, City of Manila</p>
  </div>
  <div class="foot__links">
    <a class="link" href="tel:+639935103318">0993 510 3318</a>
    <a class="link" href="tel:+639662291677">0966 229 1677</a>
    <a class="link" href="index.php">Dollar Dreams home</a>
  </div>
  <p class="foot__imprint">© 2026 Dollar Dreams Dormitory · <a class="link" href="signin.php">Staff sign in</a></p>
</footer>

<script src="assets/site.js"></script>
<script>
/* ================= HELPERS ================= */
function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}
function todayISO() { return DD.iso(new Date()); }

/* ================= DIALOGS ================= */
let lastFocus = null;
function openSheet(id) {
  lastFocus = document.activeElement;
  const el = document.getElementById(id);
  el.querySelector(".modal-content").classList.remove("is-done");
  el.classList.add("is-open");
  document.body.style.overflow = "hidden";
  setTimeout(() => el.querySelector("input:not([type=hidden]):not([readonly])")?.focus(), 50);
}
function closeSheet(id) {
  document.getElementById(id).classList.remove("is-open");
  document.body.style.overflow = "";
  lastFocus?.focus?.();
}
document.querySelectorAll(".sheet-wrap").forEach(w => w.addEventListener("click", e => { if (e.target === w) closeSheet(w.id); }));
document.addEventListener("keydown", e => {
  if (e.key !== "Escape") return;
  closeImageModal();
  document.querySelectorAll(".sheet-wrap.is-open").forEach(w => w.id === "viewingModal" ? closeViewing() : closeModal());
});

/* ================= IMAGE ZOOM ================= */
function openImage(img) {
  document.getElementById("zoomImg").src = img.src;
  document.getElementById("imageModal").style.display = "flex";
}
function closeImageModal() { document.getElementById("imageModal").style.display = "none"; }

/* ================= LOAD ROOMS ================= */
function loadUnits() {
  fetch("load.php?type=units")
    .then(r => r.json())
    .then(d => renderRooms(d.rooms || []))
    .catch(err => {
      console.error("Failed to load rooms:", err);
      document.getElementById("units").innerHTML = "<p>Rooms could not be loaded. Please refresh the page.</p>";
    });
}

/* ================= RENDER ROOMS =================
   One card per room. Availability comes from the server (functions.php), so this page,
   the dashboard and the booking check all agree. Room numbers and prices stay in the admin. */
function freeText(r) {
  if (r.is_bedspace) return r.available > 0 ? `${r.available} of ${r.capacity} beds free` : (r.status === "reserved" ? "All beds reserved" : "All beds booked");
  return r.available > 0 ? "Free now" : (r.status === "reserved" ? "Reserved" : "Booked");
}
function renderRooms(rooms) {
  // free rooms first, then by type, so the page leads with what can be booked
  rooms.sort((a, b) => (b.available > 0) - (a.available > 0) || a.room_type.localeCompare(b.room_type));
  const html = rooms.map(r => {
    const full = r.available < 1;
    return `
    <article class="unit" id="room-${r.id}" data-types="${esc(r.room_type.toLowerCase())}" data-free="${r.available}" data-reveal>
      <div class="image-wrapper">
        ${full ? `<div class="img-overlay-badge ${esc(r.status)}">${r.status === "reserved" ? "Reserved" : "Booked"}</div>` : ""}
        <img class="main-image" src="${esc(r.image)}" onclick="openImage(this)" alt="${esc(r.room_type)}: ${esc(r.description)}" width="800" height="600" loading="lazy">
      </div>
      <div class="unit__body">
        <h3>${esc(r.room_type)}</h3>
        <div class="status-badges"><span class="${full ? "badge-booked" : "badge-avail"}">${freeText(r)}</span></div>
        <p class="unit__desc">${esc(r.description)}</p>
        <div class="unit__foot">
          <button class="button button--green book-btn" type="button" onclick="openBook(this)"
                  data-id="${r.id}" data-type="${esc(r.room_type)}" data-description="${esc(r.description)}"
                  ${full ? "disabled" : ""}>${full ? (r.status === "reserved" ? "Reserved" : "Booked") : "Reserve this room"}</button>
        </div>
      </div>
    </article>`;
  }).join("");

  const box = document.getElementById("units");
  box.innerHTML = html || "<p>No rooms are listed right now.</p>";
  box.querySelectorAll("[data-reveal]").forEach((el, i) => el.style.setProperty("--i", i % 3));
  DD.reveal(box);
  applyFilter();
}

/* ================= FILTERS ================= */
let currentFilter = "all";
function applyFilter() {
  const rooms = [...document.querySelectorAll(".unit")];
  let shown = 0;
  rooms.forEach(u => {
    const ok = currentFilter === "all"
      || (currentFilter === "free" && +u.dataset.free > 0)
      || (currentFilter !== "free" && u.dataset.types.includes(currentFilter));
    u.hidden = !ok;
    if (ok) shown++;
  });
  document.getElementById("filterCount").textContent = rooms.length ? `${shown} of ${rooms.length} rooms` : "";
}
function setFilter(name) {
  const b = document.querySelector(`[data-filter="${name}"]`);
  if (!b) return;
  currentFilter = name;
  document.querySelectorAll("[data-filter]").forEach(x => x.setAttribute("aria-pressed", String(x === b)));
  applyFilter();
}
document.querySelectorAll("[data-filter]").forEach(b => b.addEventListener("click", () => setFilter(b.dataset.filter)));
// Arriving from the landing page with ?show=bed (or solo, couple, free)
setFilter(new URLSearchParams(location.search).get("show") || "all");

/* ================= BOOKING DIALOG ================= */
function openBook(btn) {
  document.getElementById("book_type").value = btn.dataset.type;
  document.getElementById("description").value = btn.dataset.description;
  document.getElementById("unit_id").value = btn.dataset.id;
  updateStay();
  openSheet("bookingModal");
}
function closeModal() { closeSheet("bookingModal"); }

/* ================= STAY LENGTH ================= */
const inD = document.querySelector("[name=move_in]");
const outD = document.querySelector("[name=move_out]");
function updateStay() {
  const t = document.getElementById("total_days");
  if (inD.value) outD.min = inD.value;
  if (inD.value && outD.value) {
    const days = Math.round((new Date(outD.value) - new Date(inD.value)) / 86400000) + 1;
    t.value = days > 0 ? days + " day(s)" : "Move-out must be after move-in";
  } else {
    t.value = "";
  }
}
inD.min = outD.min = todayISO();
[inD, outD].forEach(el => { el.addEventListener("input", updateStay); el.addEventListener("change", updateStay); });

/* ================= VIEWING SCHEDULE ================= */
document.getElementById("scheduleTop").onchange = function () {
  if (this.value) {
    document.getElementById("selectedSchedule").value = this.value;
    openSheet("viewingModal");
  }
};
function closeViewing() {
  closeSheet("viewingModal");
  document.getElementById("scheduleTop").value = "";
}
function loadSchedules() {
  fetch("load.php?type=schedule")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("scheduleTop");
      select.innerHTML = data.length
        ? `<option value="">Choose a viewing slot</option>`
        : `<option value="">No viewing slots open right now</option>`;
      data.forEach(s => {
        const [year, month, day] = s.date.split("-");
        const formattedDate = new Date(year, month - 1, day).toLocaleDateString("en-US", { month: "long", day: "2-digit", year: "numeric" });
        const option = document.createElement("option");
        option.value = option.textContent = `${s.day}, ${formattedDate} / ${formatTime(s.time_start)} - ${formatTime(s.time_end)}`;
        select.appendChild(option);
      });
    })
    .catch(err => console.error("Schedule error:", err));
}
function formatTime(time) {
  if (!time) return "";
  const [h, m] = time.split(":");
  const hour = parseInt(h, 10);
  return `${hour % 12 || 12}:${m.padStart(2, "0")} ${hour >= 12 ? "PM" : "AM"}`; // taste-skill-ignore: a clock time
}

/* ================= FORM SUBMISSIONS ================= */
function submitForm(form, url, onSuccess) {
  const btn = form.querySelector("button[type=submit]");
  const label = btn.textContent;
  btn.disabled = true;   // stops double submissions
  btn.textContent = "Sending…";
  fetch(url, { method: "POST", body: new FormData(form) })
    .then(res => res.text())
    .then(data => {
      if (data.trim() === "success") {
        form.reset();
        onSuccess();
      } else {
        alert(data);
      }
    })
    .catch(err => {
      console.error(err);
      alert("Network error. Please check your connection and try again.");
    })
    .finally(() => { btn.disabled = false; btn.textContent = label; });
}

document.getElementById("bookingForm").addEventListener("submit", function (e) {
  e.preventDefault();
  submitForm(this, "save.php", () => {
    this.closest(".modal-content").classList.add("is-done");
    updateStay();
    loadUnits();
  });
});
document.getElementById("viewingForm").addEventListener("submit", function (e) {
  e.preventDefault();
  submitForm(this, "save.php", () => {
    this.closest(".modal-content").classList.add("is-done");
    document.getElementById("scheduleTop").value = "";
  });
});

loadUnits();
loadSchedules();
</script>
</body>
</html>
