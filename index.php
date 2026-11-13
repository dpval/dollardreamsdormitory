<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dollar Dreams Dormitory - Manila</title>

<!-- Preload Hero Image to Prevent Initial Render Delay -->
<link rel="preload" as="image" href="image/herobg.jpg">

<!-- Leaflet Map CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<!-- Swiper.js 3D Carousel CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<style>
/* =========================
   GLOBAL STYLES & HARDWARE ACCELERATION
========================= */
:root {
  --primary: #183823;
  --primary-light: #245336;
  --accent: #2ecc71;
  --text-dark: #2c3e50;
  --text-muted: #666666;
  --bg-gray: #f4f6fa;
  --card-shadow: 0 8px 25px rgba(0,0,0,0.06);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  background: var(--bg-gray);
  color: var(--text-dark);
  line-height: 1.6;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* =========================
   TOP NAVIGATION & LIGHTWEIGHT MOON EFFECT
========================= */
.topbar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 30px 20px;
  background: linear-gradient(180deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
}

.brand {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  text-decoration: none;
  transform: translateZ(0);
}

.brand::before {
  content: "";
  position: absolute;
  width: 130px;
  height: 130px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.85) 0%, rgba(46, 204, 113, 0.5) 50%, rgba(24, 56, 35, 0) 75%);
  filter: blur(8px);
  z-index: -1;
  will-change: transform, opacity;
  animation: moonGlow 3s ease-in-out infinite alternate;
}

@keyframes moonGlow {
  0% {
    opacity: 0.6;
    transform: scale(0.95);
  }
  100% {
    opacity: 0.95;
    transform: scale(1.1);
  }
}

.brand video {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 50%;
  box-shadow: 0 0 20px rgba(255, 255, 255, 0.4);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor: pointer;
  position: relative;
  z-index: 2;
  background: transparent;
  transform: translateZ(0);
  will-change: transform;
}

.brand video:hover {
  transform: scale(1.06);
  box-shadow: 0 0 30px rgba(46, 204, 113, 0.7);
}

/* =========================
   HERO SECTION
========================= */
.hero {
  position: relative;
  height: 90vh;
  min-height: 550px;
  background: url("image/herobg.jpg") center/cover no-repeat;
  background-attachment: scroll;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  text-align: center;
  padding: 0 20px;
  backface-visibility: hidden;
  transform: translateZ(0);
}

.hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.6));
}

.hero-content {
  position: relative;
  z-index: 1;
  max-width: 850px;
  animation: fadeUp 0.8s ease-out;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.hero h1 {
  font-size: 46px;
  font-weight: 800;
  margin-bottom: 20px;
  line-height: 1.25;
  text-shadow: 0 2px 10px rgba(0,0,0,0.4);
  letter-spacing: 1px;
}

.hero p {
  font-size: 20px;
  margin-bottom: 35px;
  color: #e0e0e0;
  font-weight: 300;
}

.btn-primary {
  display: inline-block;
  padding: 15px 40px;
  background: var(--accent);
  color: white;
  text-decoration: none;
  font-size: 16px;
  font-weight: 700;
  border-radius: 30px;
  box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
  will-change: transform;
}

.btn-primary:hover {
  background: #27ae60;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(46, 204, 113, 0.5);
}

/* =========================
   LOCATION BENEFITS CARDS
========================= */
.info-section {
  padding: 80px 20px;
  text-align: center;
  background: #ffffff;
}

.section-title {
  font-size: 32px;
  color: var(--primary);
  font-weight: 700;
  margin-bottom: 10px;
}

.section-subtitle {
  font-size: 16px;
  color: var(--text-muted);
  margin-bottom: 40px;
}

.cards {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 25px;
  max-width: 1200px;
  margin: 0 auto;
}

@property --border-angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}

.card {
  position: relative;
  flex: 1;
  min-width: 240px;
  max-width: 270px;
  background: #ffffff;
  padding: 35px 20px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  will-change: transform;
  border: 2px solid transparent;
  background-image: 
    linear-gradient(#ffffff, #ffffff), 
    conic-gradient(from var(--border-angle), #f0f0f0, #f0f0f0);
  background-origin: border-box;
  background-clip: padding-box, border-box;
}

.card:hover {
  transform: translateY(-8px);
  box-shadow: 0 14px 30px rgba(46, 204, 113, 0.18);
  background-image: 
    linear-gradient(#ffffff, #ffffff), 
    conic-gradient(from var(--border-angle), var(--accent), var(--primary), #e2e8f0, var(--accent));
  animation: borderTrace 2.5s linear infinite;
}

@keyframes borderTrace {
  to {
    --border-angle: 360deg;
  }
}

.walk-icon {
  font-size: 42px;
  margin-bottom: 15px;
  display: inline-block;
  transition: transform 0.3s ease;
}

.card:hover .walk-icon {
  transform: scale(1.12);
}

.card h3 {
  font-size: 18px;
  color: var(--primary);
  margin-bottom: 8px;
  font-weight: 700;
}

.card p {
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 500;
}

/* =========================
   3D ROTATING CAROUSEL (COMMON AREAS)
========================= */
.common-areas-section {
  padding: 80px 20px;
  background: var(--bg-gray);
  text-align: center;
  overflow: hidden;
}

.carousel-3d-wrapper {
  max-width: 1200px;
  margin: 30px auto 0;
  position: relative;
  padding-bottom: 60px;
}

.swiper-3d-container {
  width: 100%;
  padding-top: 30px;
  padding-bottom: 50px;
  perspective: 1200px; /* Enhanced 3D Depth */
}

.swiper-slide-3d {
  background-position: center;
  background-size: cover;
  width: 340px;
  height: 280px;
  border-radius: 20px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 15px 35px rgba(0,0,0,0.2);
  transition: transform 0.4s ease, filter 0.4s ease, box-shadow 0.4s ease;
}

/* Modern High-Tech Glass Overlay & Beam */
.slide-card-content {
  position: relative;
  width: 100%;
  height: 100%;
}

.slide-card-content img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.slide-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(24, 56, 35, 0.85) 100%);
  opacity: 0.6;
  transition: opacity 0.3s ease;
}

/* Futuristic Hover Light Beam */
.slide-card-content::after {
  content: '';
  position: absolute;
  top: -100%;
  left: -100%;
  width: 300%;
  height: 300%;
  background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.25) 50%, rgba(255,255,255,0) 100%);
  transform: rotate(45deg);
  transition: all 0.8s ease;
  pointer-events: none;
}

.swiper-slide-3d:hover .slide-card-content::after {
  top: 100%;
  left: 100%;
}

.swiper-slide-3d:hover img {
  transform: scale(1.1);
}

.swiper-slide-3d:hover .slide-overlay {
  opacity: 0.9;
}

.swiper-slide-active {
  box-shadow: 0 20px 40px rgba(46, 204, 113, 0.35);
  border: 2px solid var(--accent);
}

/* Custom 3D Navigation Controls */
.swiper-3d-button-next,
.swiper-3d-button-prev {
  color: white;
  background: rgba(24, 56, 35, 0.85);
  backdrop-filter: blur(8px);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.2);
  box-shadow: 0 6px 15px rgba(0,0,0,0.2);
  transition: background 0.3s ease, transform 0.3s ease;
}

.swiper-3d-button-next:after,
.swiper-3d-button-prev:after {
  font-size: 20px;
  font-weight: bold;
}

.swiper-3d-button-next:hover,
.swiper-3d-button-prev:hover {
  background: var(--accent);
  transform: scale(1.1);
  color: white;
}

.swiper-3d-pagination .swiper-pagination-bullet {
  background: #cbd5e1;
  opacity: 1;
  transition: all 0.3s ease;
}

.swiper-3d-pagination .swiper-pagination-bullet-active {
  background: var(--accent);
  width: 26px;
  border-radius: 6px;
}

/* =========================
   MAP CONTAINER
========================= */
.map-section {
  padding: 40px 20px 80px;
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
}

#map {
  height: 450px;
  width: 100%;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  border: 1px solid #e0e0e0;
}

/* =========================
   FOOTER
========================= */
footer {
  background: var(--primary);
  color: #e0e0e0;
  text-align: center;
  padding: 30px 20px;
  font-size: 14px;
}

footer a {
  color: var(--accent);
  text-decoration: none;
}

/* =========================
   RESPONSIVE BREAKPOINTS
========================= */
@media (max-width: 768px) {
  .topbar {
    padding: 20px 15px;
  }
  .brand video {
    height: 90px;
    width: 90px;
  }
  .brand::before {
    width: 100px;
    height: 100px;
  }
  .hero h1 {
    font-size: 32px;
  }
  .hero p {
    font-size: 16px;
  }
  .section-title {
    font-size: 26px;
  }
  .swiper-slide-3d {
    width: 280px;
    height: 230px;
  }
}
</style>
</head>

<body>

<!-- CENTERED LOGO AS HIGH-PERFORMANCE VIDEO -->
<div class="topbar">
  <a href="index.php" class="brand">
    <video autoplay loop muted playsinline preload="auto" disablePictureInPicture id="logoVideo">
      <source src="video/dddloaading.mp4" type="video/mp4">
      <source src="image/dddloaading.mp4" type="video/mp4">
      <source src="dddloaading.mp4" type="video/mp4">
    </video>
  </a>
</div>

<!-- HERO SECTION -->
<div class="hero">
  <div class="hero-content">
    <h1>Welcome to <br> DOLLAR DREAMS DORMITORY</h1>
    <p>Your safe, affordable, and comfortable space in Manila.</p>
    <a href="book.php" class="btn-primary">Book Now</a>
  </div>
</div>

<!-- LOCATION BENEFITS SECTION -->
<div class="info-section">
  <h2 class="section-title">📍 Prime Location Benefits</h2>
  <p class="section-subtitle">Everything you need is just a few minutes away</p>

  <div class="cards">
    <div class="card">
      <div class="walk-icon">🏥</div>
      <h3>PGH (PH General Hospital)</h3>
      <p>~3–5 minute walk</p>
    </div>

    <div class="card">
      <div class="walk-icon">🚉</div>
      <h3>Pedro Gil Station</h3>
      <p>~5–7 minute walk</p>
    </div>

    <div class="card">
      <div class="walk-icon">🛍</div>
      <h3>Robinsons Manila</h3>
      <p>~6–8 minute walk</p>
    </div>

    <div class="card">
      <div class="walk-icon">🏢</div>
      <h3>Agencies / Offices</h3>
      <p>~3–10 minute walk</p>
    </div>
  </div>
</div>

<!-- COMMON AREAS 3D ROTATING CAROUSEL -->
<div class="common-areas-section">
  <h2 class="section-title">🏢 Featuring Some Common Areas</h2>
  <p class="section-subtitle">Take a look inside our clean, spacious, and shared facilities</p>

  <div class="carousel-3d-wrapper">
    <div class="swiper swiper-3d-container">
      <div class="swiper-wrapper">
        
        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995569824.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995579047.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995587499.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995595300.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995603548.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995613242.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995625455.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995634264.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995654947.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995723292.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995726619.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995729664.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

        <div class="swiper-slide swiper-slide-3d">
          <div class="slide-card-content">
            <img src="image/FB_IMG_1787995734081.jpg" alt="Common Area">
            <div class="slide-overlay"></div>
          </div>
        </div>

      </div>

      <!-- Controls -->
      <div class="swiper-pagination swiper-3d-pagination"></div>
      <div class="swiper-button-next swiper-3d-button-next"></div>
      <div class="swiper-button-prev swiper-3d-button-prev"></div>
    </div>
  </div>
</div>

<!-- INTERACTIVE MAP SECTION -->
<div class="map-section">
  <h2 class="section-title" style="margin-bottom: 20px;">Explore the Neighborhood</h2>
  <div id="map"></div>
</div>

<!-- FOOTER -->
<footer>
  © 2026 Dollar Dreams Dormitory. All Rights Reserved. <br>
  📞 09935103318 | 09662291677 <br>
  📍 1655 Taft Ave, Malate, City of Manila, Metro Manila
</footer>

<!-- LEAFLET JS MAP ENGINE -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- SWIPER.JS ENGINE -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
// INSTANT UNMUTED PLAYBACK OVERRIDE FOR FAST VIDEO LOADING
document.addEventListener("DOMContentLoaded", function () {
  const video = document.getElementById("logoVideo");
  if (video) {
    video.muted = true;
    video.defaultMuted = true;
    
    let promise = video.play();
    if (promise !== undefined) {
      promise.catch(() => {
        document.body.addEventListener("touchstart", () => video.play(), { once: true });
      });
    }
  }
});

// INITIALIZE HIGH-TECH 3D ROTATING COVERFLOW CAROUSEL
const gallerySwiper = new Swiper('.swiper-3d-container', {
  effect: 'coverflow',
  grabCursor: true,
  centeredSlides: true,
  slidesPerView: 'auto',
  loop: true,
  speed: 800,
  autoplay: {
    delay: 2500,
    disableOnInteraction: false,
    pauseOnMouseEnter: true, // STOPS ROTATING ON HOVER
  },
  coverflowEffect: {
    rotate: 35,      // Slide rotation angle
    stretch: 0,       // Space between slides
    depth: 200,       // 3D Perspective Depth
    modifier: 1,      // Effect multiplier
    slideShadows: true, // High-tech 3D shadows
  },
  pagination: {
    el: '.swiper-3d-pagination',
    clickable: true,
  },
  navigation: {
    nextEl: '.swiper-3d-button-next',
    prevEl: '.swiper-3d-button-prev',
  },
});

// INITIALIZE LEAFLET MAP
var map = L.map('map').setView([14.5764, 120.9910], 16);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var dorm = L.marker([14.5764, 120.9910]).addTo(map)
  .bindPopup("<b>🏠 Dollar Dreams Dormitory</b><br>1655 Taft Ave, Malate").openPopup();

var pgh = L.marker([14.5791, 120.9886]).addTo(map)
  .bindPopup("🏥 Philippine General Hospital (PGH)");

var pedroGil = L.marker([14.5760, 120.9900]).addTo(map)
  .bindPopup("🚉 Pedro Gil LRT Station");

var robinsons = L.marker([14.5780, 120.9820]).addTo(map)
  .bindPopup("🛍 Robinsons Place Manila");

L.polyline([
  [14.5764, 120.9910],
  [14.5791, 120.9886]
], {color:'#e74c3c', weight: 4, opacity: 0.8}).addTo(map);

L.polyline([
  [14.5764, 120.9910],
  [14.5760, 120.9900]
], {color:'#183823', weight: 4, opacity: 0.8}).addTo(map);

L.polyline([
  [14.5764, 120.9910],
  [14.5780, 120.9820]
], {color:'#2ecc71', weight: 4, opacity: 0.8}).addTo(map);
</script>

</body>
</html>