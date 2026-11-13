<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dollar Dreams Dormitory - Book & Schedule Viewing</title>

<style>
/* ========================= GLOBAL VARIABLES & RESET ========================= */
:root {
  --primary: #183823;
  --primary-light: #245336;
  --accent: #2ecc71;
  --accent-hover: #27ae60;
  --warning: #f39c12;
  --danger: #e74c3c;
  --text-dark: #2c3e50;
  --bg-overlay: rgba(15, 23, 18, 0.75);
  --card-bg: rgba(255, 255, 255, 0.12);
  --card-border: rgba(255, 255, 255, 0.18);
  --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  color: #ffffff;
  background: url('image/hero.jpg') center/cover no-repeat fixed;
  min-height: 100vh;
  overflow-x: hidden;
  line-height: 1.6;
}

/* ========================= BACKDROP OVERLAY ========================= */
.overlay {
  position: fixed;
  inset: 0;
  background: var(--bg-overlay);
  backdrop-filter: blur(4px);
  z-index: -1;
}

/* ========================= HEADER ========================= */
header {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px 40px;
  background: rgba(0, 0, 0, 0.4);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

header .brand {
  display: flex;
  justify-content: center;
  align-items: center;
  text-decoration: none;
}

header img {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  object-fit: cover;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
  cursor: pointer;
  transition: transform 0.3s ease;
}

header img:hover {
  transform: scale(1.08);
}

/* ========================= MAIN CONTAINER ========================= */
.container {
  max-width: 1250px;
  margin: 0 auto;
  padding: 40px 20px 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 25px;
}

/* ========================= SCHEDULE DROPDOWN ========================= */
.schedule-wrapper {
  width: 100%;
  max-width: 450px;
}

#scheduleTop {
  font-size: 15px;
  font-weight: 600;
  padding: 14px 20px;
  border-radius: 30px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  width: 100%;
  background: rgba(255, 255, 255, 0.95);
  color: #111;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(0,0,0,0.25);
  outline: none;
  transition: all 0.3s ease;
}

#scheduleTop:hover {
  background: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

/* ========================= HERO INFO BOX ========================= */
.info-text {
  max-width: 750px;
  font-size: 17px;
  color: #f1f1f1;
  background: rgba(0, 0, 0, 0.45);
  padding: 25px 30px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  box-shadow: var(--glass-shadow);
  line-height: 1.7;
}

.info-text p {
  margin-bottom: 10px;
}

.highlight {
  color: var(--accent);
  font-weight: 700;
}

.badge-no-deposit {
  display: inline-block;
  margin-top: 10px;
  padding: 6px 16px;
  background: rgba(46, 204, 113, 0.15);
  border: 1px solid var(--accent);
  color: var(--accent);
  border-radius: 20px;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.5px;
}

/* ========================= CAROUSEL / GRID ========================= */
.carousel {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 25px;
  width: 100%;
  margin-top: 15px;
}

/* ========================= UNIT CARD ========================= */
.unit {
  background: var(--card-bg);
  border: 1px solid var(--card-border);
  border-radius: 16px;
  padding: 20px;
  backdrop-filter: blur(12px);
  box-shadow: var(--glass-shadow);
  display: flex;
  flex-direction: column;
  height: 100%;
  text-align: left;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.unit:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.4);
}

.unit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.unit-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #ffffff;
}

/* HIDDEN AVAIL / RESERVED / BOOKED BADGES */
.status-badges {
  display: none !important;
}

.badge-avail {
  background: rgba(46, 204, 113, 0.2);
  color: var(--accent);
  padding: 3px 8px;
  border-radius: 10px;
  border: 1px solid rgba(46, 204, 113, 0.3);
}

.badge-reserved {
  background: rgba(243, 156, 18, 0.2);
  color: var(--warning);
  padding: 3px 8px;
  border-radius: 10px;
  border: 1px solid rgba(243, 156, 18, 0.3);
}

.badge-booked {
  background: rgba(231, 76, 60, 0.2);
  color: #ff6b6b;
  padding: 3px 8px;
  border-radius: 10px;
  border: 1px solid rgba(231, 76, 60, 0.3);
}

/* IMAGE CONTAINER WITH STATUS OVERLAY */
.image-wrapper {
  position: relative;
  width: 100%;
  height: 220px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  cursor: pointer;
  display: block;
  transition: opacity 0.2s ease;
}

/* IMAGE OVERLAY BADGES */
.img-overlay-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #ffffff;
  box-shadow: 0 4px 15px rgba(0,0,0,0.4);
  z-index: 3;
}

.img-overlay-badge.reserved {
  background: rgba(243, 156, 18, 0.9);
}

.img-overlay-badge.booked {
  background: rgba(231, 76, 60, 0.9);
}

.unit h4 {
  font-size: 15px;
  font-weight: 600;
  color: #e0e0e0;
  margin: 12px 0 8px;
  min-height: 22px;
}

/* THUMBNAILS CONTAINER */
.thumbnails {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 15px;
}

.thumb-box {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
}

.thumb-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  cursor: pointer;
  border: 2px solid transparent;
  opacity: 0.7;
  transition: all 0.2s ease;
}

.thumb-box img:hover {
  opacity: 1;
}

.thumb-box img.active {
  border-color: var(--accent);
  opacity: 1;
}

.thumb-status-dot {
  position: absolute;
  bottom: 3px;
  right: 3px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 1px solid white;
}

.thumb-status-dot.reserved { background: var(--warning); }
.thumb-status-dot.booked { background: var(--danger); }

/* BOOK BUTTON */
.unit button {
  margin-top: auto;
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 10px;
  background: var(--accent);
  color: #ffffff;
  cursor: pointer;
  font-weight: 700;
  font-size: 15px;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
}

.unit button:hover {
  background: var(--accent-hover);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(46, 204, 113, 0.5);
}

.unit button:disabled {
  background: #555555 !important;
  color: #aaaaaa !important;
  cursor: not-allowed !important;
  transform: none !important;
  box-shadow: none !important;
}

/* ========================= MODALS ========================= */
#bookingModal,
#viewingModal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(6px);
  justify-content: center;
  align-items: center;
  z-index: 999;
  padding: 20px;
}

#imageModal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.9);
  justify-content: center;
  align-items: center;
  z-index: 99999;
  padding: 20px;
}

#imageModal img {
  max-width: 95%;
  max-height: 85vh;
  object-fit: contain;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.8);
}

/* MODAL CONTENT CONTAINER */
.modal-content {
  background: #ffffff;
  color: var(--text-dark);
  width: 440px;
  max-width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 16px;
  padding: 25px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.4);
  position: relative;
}

.modal-content h3 {
  font-size: 20px;
  color: var(--primary);
  font-weight: 700;
  margin-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 10px;
}

.close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 20px;
  cursor: pointer;
  color: #888;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #000;
}

.modal-content label {
  display: block;
  margin-top: 12px;
  font-size: 13px;
  font-weight: 700;
  color: #444;
  text-align: left;
}

.modal-content input,
.modal-content select {
  width: 100%;
  padding: 10px 12px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.modal-content input:focus {
  border-color: var(--accent);
}

.modal-content input[readonly] {
  background: #f4f6fa;
  color: #666;
}

.modal-content button[type="submit"] {
  width: 100%;
  padding: 12px;
  margin-top: 20px;
  border-radius: 8px;
  border: none;
  background: var(--primary);
  color: white;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: background 0.2s;
}

.modal-content button[type="submit"]:hover {
  background: var(--primary-light);
}

/* ========================= FOOTER ========================= */
footer {
  background: rgba(0, 0, 0, 0.85);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  text-align: center;
  padding: 25px 20px;
  width: 100%;
  font-size: 13px;
  color: #cccccc;
  margin-top: 60px;
}

footer a {
  color: var(--accent);
  text-decoration: none;
}

/* ========================= RESPONSIVE BREAKPOINTS ========================= */
@media (max-width: 768px) {
  header {
    padding: 15px;
  }

  .container {
    padding: 20px 15px 60px;
  }

  .info-text {
    font-size: 15px;
    padding: 18px;
  }

  .carousel {
    grid-template-columns: 1fr;
  }

  .image-wrapper {
    height: 190px;
  }

  .thumb-box {
    width: 52px;
    height: 52px;
  }
}
</style>
</head>

<body>

<div class="overlay"></div>

<!-- IMAGE MODAL -->
<div id="imageModal" onclick="closeImageModal()">
  <img id="zoomImg" alt="Zoom View">
</div>

<!-- HEADER -->
<header>
  <a href="index.php" class="brand">
    <img src="image/logo.jpg" alt="Logo" onclick="openImage(this)">
  </a>
</header>

<div class="container">

  <!-- VIEWING SCHEDULE DROPDOWN -->
  <div class="schedule-wrapper">
    <select id="scheduleTop">
      <option value="">Loading viewing schedules...</option>
    </select>
  </div>

  <!-- HERO INFO TEXT -->
  <div class="info-text">
    <p>Looking for a safe, comfortable, and highly accessible place to stay in Malate, Manila?</p>
    <p>We offer <span class="highlight">Bed Space</span>, <span class="highlight">Couple Rooms</span>, and <span class="highlight">Solo Rooms</span>.</p>
    <div class="badge-no-deposit">✨ NO DEPOSIT REQUIRED</div>
  </div>

  <!-- UNITS GRID CAROUSEL -->
  <div class="carousel" id="units"></div>

</div>

<!-- VIEWING MODAL -->
<div id="viewingModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeViewing()">✖</span>
    <h3>Schedule Viewing</h3>

    <form id="viewingForm">
      <label>Your Name</label>
      <input name="name" placeholder="Enter Full Name" required>

      <label>Mobile Number</label>
      <input name="mobile" placeholder="e.g. 09171234567" required>

      <label>Selected Schedule Slot</label>
      <input id="selectedSchedule" name="schedule" readonly required>

      <button type="submit">Submit Viewing Request</button>
    </form>
  </div>
</div>

<!-- BOOKING MODAL -->
<div id="bookingModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">✖</span>
    <h3>Book Room Accommodation</h3>

    <form id="bookingForm" enctype="multipart/form-data">
      <input type="hidden" name="unit_id" id="unit_id">
      <input type="hidden" name="price" id="book_price" value="0">

      <label>Unit Name</label>
      <input name="unit" id="book_unit" readonly placeholder="Unit">

      <label>Room Category</label>
      <input name="book_type" id="book_type" readonly placeholder="Room Type">

      <label>Description / Details</label>
      <input name="description" id="description" readonly placeholder="Description">

      <label>Full Name</label>
      <input name="name" placeholder="Enter Full Name" required>

      <label>Mobile Number</label>
      <input name="mobile" placeholder="e.g. 09171234567" required>

      <label>Email Address</label>
      <input name="email" type="email" placeholder="email@example.com">

      <label>Reservation Fee (₱)</label>
      <input type="number" name="reservation_fee" value="500" min="500" placeholder="Minimum 500 PHP" required>

      <label>Move In Date</label>
      <input type="date" name="move_in" required>

      <label>Move Out Date</label>
      <input type="date" name="move_out" required>

      <label>Total Term Duration</label>
      <input id="total_days" readonly placeholder="0 day(s)">

      <label>Upload Valid Government ID</label>
      <input type="file" name="id_upload" required>

      <label>Upload GCash Payment Proof</label>
      <input type="file" name="payment_proof" required>

      <div style="text-align:center; margin-top:15px;">
        <p style="font-size:12px; color:#666; margin-bottom:5px;">Scan GCash QR Code to Pay Reservation</p>
        <img src="image/QRDDD.jpg" alt="Payment QR Code" style="width:100%;max-width:200px;height:auto;border-radius:10px;border:1px solid #ddd;">
      </div>

      <button type="submit">Submit Reservation</button>
    </form>
  </div>
</div>

<!-- FOOTER -->
<footer>
  © 2026 Dollar Dreams Dormitory. All Rights Reserved. <br> 
  📞 09935103318 | 09662291677 <br> 
  📍 1655 Taft Ave, Malate, City Of Manila, Metro Manila 
  <div style="margin-top:10px;"> 
    <a href="signin.php"> 
      <img src="image/logo.jpg" alt="Admin Sign In" style="width:32px; height:32px; border-radius:50%; cursor:pointer; opacity:0.8;"> 
    </a> 
  </div> 
</footer>

<script>
let allUnitsData = {};

/* ================= IMAGE ZOOM ================= */
function openImage(img){
  document.getElementById("zoomImg").src = img.src;
  document.getElementById("imageModal").style.display = "flex";
}

function closeImageModal(){
  document.getElementById("imageModal").style.display = "none";
}

/* ================= LOAD UNITS ================= */
function loadUnits(){
  fetch("load_units.php")
  .then(r => r.json())
  .then(d => {
    allUnitsData = d.units;
    renderUnits(d.units);
  })
  .catch(err => {
    console.error("Failed to load units:", err);
  });
}

/* ================= RENDER UNITS ================= */
function renderUnits(data){
  let html = "";

  for (let unit in data) {
    let groups = Object.values(data[unit]);
    let first = null;

    // Collect all items across groups
    let allItems = [];
    for (let g of groups) {
      if (Array.isArray(g.items)) {
        allItems = allItems.concat(g.items);
      }
    }

    // Default to the first AVAILABLE item; fallback to first item if all booked
    first = allItems.find(i => (i.status || "").toLowerCase() !== "booked" && (i.status || "").toLowerCase() !== "reserved") || allItems[0];

    let available = 0;
    let reserved = 0;
    let booked = 0;

    for (let g of groups) {
      let items = g.items || [];
      items.forEach(i => {
        let status = (i.status || "").toLowerCase();
        
        if (g.is_bedspace == 1) {
          let match = i.description?.match(/-(\d+)$/);
          let capacity = match ? parseInt(match[1]) : 1;
          
          if (status === "booked") {
            booked++;
            available += Math.max(0, capacity - 1);
          } else if (status === "reserved") {
            reserved++;
            available += Math.max(0, capacity - 1);
          } else {
            available += capacity;
          }
        } else {
          if (status === "booked") {
            booked++;
          } else if (status === "reserved") {
            reserved++;
          } else {
            available++;
          }
        }
      });
    }

    let roomLabel = groups[0]?.is_bedspace == 1 ? "" : (first?.room_type || "");
    let mainStatus = (first?.status || "").toLowerCase();
    
    // Check if whole unit is fully booked or if current selected item is booked
    let isUnitFullyBooked = available <= 0;
    let isCurrentItemBooked = mainStatus === "booked";
    let disableButton = isUnitFullyBooked || isCurrentItemBooked;

    // Status Tag on Main Image
    let mainStatusTag = "";
    if (mainStatus === "reserved") {
      mainStatusTag = `<div class="img-overlay-badge reserved">RESERVED</div>`;
    } else if (mainStatus === "booked" || isUnitFullyBooked) {
      mainStatusTag = `<div class="img-overlay-badge booked">BOOKED</div>`;
    }

    html += `
    <div class="unit">
      <div class="unit-header">
        <h3>${unit}</h3>
        <div class="status-badges">
          <span class="badge-avail">Avail: ${available}</span>
          ${reserved > 0 ? `<span class="badge-reserved">Reserved: ${reserved}</span>` : ''}
          <span class="badge-booked">Booked: ${booked}</span>
        </div>
      </div>

      <div class="image-wrapper">
        ${mainStatusTag}
        <img class="main-image" src="${first?.image || ''}" onclick="openImage(this)" alt="${unit}">
      </div>

      <h4>${roomLabel}</h4>

      <div class="thumbnails">
    `;

    // Render Thumbnails
    let thumbIndex = 0;
    for (let g of groups) {
      let items = g.items || [];
      items.forEach((i) => {
        let itemStatus = (i.status || "").toLowerCase();
        let dotClass = itemStatus === "reserved" ? "reserved" : (itemStatus === "booked" ? "booked" : "");
        let isActive = (i.id === first?.id);

        html += `
        <div class="thumb-box">
          <img src="${i.image}"
               onclick="setMainImage(this)"
               data-id="${i.id}"
               data-type="${i.room_type || ''}"
               data-description="${i.description || ''}"
               data-price="${i.price || 0}"
               data-status="${itemStatus}"
               class="${isActive ? 'active' : ''}">
          ${dotClass ? `<div class="thumb-status-dot ${dotClass}"></div>` : ''}
        </div>
        `;
        thumbIndex++;
      });
    }

    html += `
      </div>

      <button class="book-btn" onclick="openBook(this)" ${disableButton ? 'disabled' : ''}>
        ${disableButton ? (isCurrentItemBooked ? 'Booked' : 'Fully Booked') : 'Book Room Accommodation'}
      </button>
    </div>
    `;
  }

  document.getElementById("units").innerHTML = html;
}

/* ================= IMAGE SWITCH & BUTTON DISABLE UPDATE ================= */
function setMainImage(img){
  let u = img.closest(".unit");
  let mainImg = u.querySelector(".main-image");
  let wrapper = u.querySelector(".image-wrapper");
  let bookBtn = u.querySelector(".book-btn");
  
  mainImg.src = img.src;

  let unitName = u.querySelector("h3").innerText.toLowerCase();
  if(unitName.includes("bed")){
    u.querySelector("h4").innerText = "";
  } else {
    u.querySelector("h4").innerText = img.dataset.type;
  }

  // Update status badge on main image wrapper
  let status = (img.dataset.status || "").toLowerCase();
  let existingBadge = wrapper.querySelector(".img-overlay-badge");
  if (existingBadge) existingBadge.remove();

  if (status === "reserved") {
    wrapper.insertAdjacentHTML('afterbegin', `<div class="img-overlay-badge reserved">RESERVED</div>`);
  } else if (status === "booked") {
    wrapper.insertAdjacentHTML('afterbegin', `<div class="img-overlay-badge booked">BOOKED</div>`);
  }

  // UPDATE BUTTON CLICKABLE STATUS BASED ON SELECTED THUMBNAIL
  if (status === "booked") {
    bookBtn.disabled = true;
    bookBtn.innerText = "Booked";
  } else {
    bookBtn.disabled = false;
    bookBtn.innerText = "Book Room Accommodation";
  }

  u.dataset.sel = JSON.stringify({
    id: img.dataset.id,
    type: img.dataset.type,
    description: img.dataset.description,
    price: img.dataset.price || 0
  });

  u.querySelectorAll(".thumbnails img").forEach(i => i.classList.remove("active"));
  img.classList.add("active");
}

/* ================= OPEN BOOKING MODAL ================= */
function openBook(btn){
  let u = btn.closest(".unit");
  let s = JSON.parse(u.dataset.sel || "{}");
  let a = u.querySelector(".thumbnails img.active") || u.querySelector(".thumbnails img");

  document.getElementById("book_unit").value = u.querySelector("h3").innerText;
  document.getElementById("book_type").value = s.type || a.dataset.type || "";
  document.getElementById("description").value = s.description || a.dataset.description || "";
  document.getElementById("unit_id").value = s.id || a.dataset.id || "";
  document.getElementById("book_price").value = s.price || a.dataset.price || 0;

  // Always pre-fill and require at least 500 PHP
  document.querySelector("input[name='reservation_fee']").value = 500;

  calcDays();
  document.getElementById("bookingModal").style.display = "flex";
}

/* ================= CALCULATE DURATION ================= */
function calcDays(){
  let inD = document.querySelector("[name=move_in]");
  let outD = document.querySelector("[name=move_out]");
  let t = document.getElementById("total_days");

  function up(){
    if(inD.value){
      let s = new Date(inD.value);
      let e = outD.value ? new Date(outD.value) : s;
      let days = Math.floor((e - s) / 86400000) + 1;
      t.value = (days > 0 ? days : 1) + " day(s)";
    }
  }

  inD.onchange = up;
  outD.onchange = up;
}

function closeModal(){
  document.getElementById("bookingModal").style.display = "none";
}

/* ================= VIEWING SCHEDULE ================= */
document.getElementById("scheduleTop").onchange = function(){
  if(this.value){
    document.getElementById("selectedSchedule").value = this.value;
    document.getElementById("viewingModal").style.display = "flex";
  }
};

function closeViewing(){
  document.getElementById("viewingModal").style.display = "none";
}

function loadSchedules(){
  fetch("load_schedule.php")
  .then(res => res.json())
  .then(data => {
    let select = document.getElementById("scheduleTop");
    select.innerHTML = `<option value="">Select Viewing Schedule Slot</option>`;

    data.forEach(s => {
      let [year, month, day] = s.date.split("-");
      let dateObj = new Date(year, month - 1, day);

      let formattedDate = dateObj.toLocaleDateString('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric'
      });

      let formatted = `${s.day}, ${formattedDate} / ${formatTime(s.time_start)} - ${formatTime(s.time_end)}`;

      let option = document.createElement("option");
      option.value = formatted;
      option.textContent = formatted;
      select.appendChild(option);
    });
  })
  .catch(err => console.error("Schedule error:", err));
}

function formatTime(time){
  if(!time) return "";
  let [h, m] = time.split(":");
  let hour = parseInt(h, 10);
  let ampm = hour >= 12 ? "PM" : "AM";
  hour = hour % 12 || 12;
  let minPadded = m.padStart(2, "0");
  return `${hour}:${minPadded} ${ampm}`;
}

/* ================= FORM SUBMISSIONS ================= */
document.getElementById("bookingForm").addEventListener("submit", function(e){
  e.preventDefault();
  let formData = new FormData(this);

  fetch("save_booking.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    if (data.trim() === "success") {
      alert("Booking request saved successfully!");
      closeModal();
      this.reset();
      loadUnits();
    } else {
      alert("Error saving booking: " + data);
    }
  })
  .catch(err => {
    console.error(err);
    alert("Server error processing booking.");
  });
});

document.getElementById("viewingForm").addEventListener("submit", function(e){
  e.preventDefault();
  let formData = new FormData(this);

  fetch("save_viewing.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    if (data.trim() === "success") {
      alert("Viewing request saved!");
      closeViewing();
      this.reset();
      document.getElementById("scheduleTop").value = "";
    } else {
      alert("Error saving request: " + data);
    }
  })
  .catch(err => {
    console.error(err);
    alert("Server error processing request.");
  });
});

// Initialization
loadUnits();
loadSchedules();
</script>

</body>
</html>