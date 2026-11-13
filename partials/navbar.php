<style>
/* =========================
   NAVBAR BASE
========================= */
nav {
    background: #183823;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    position: sticky;
    top: 0;
    z-index: 999;
}

/* BRAND LOGO STYLING */
.nav-brand {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.nav-brand img {
    height: 48px;
    width: 48px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #2ecc71;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.nav-brand img:hover {
    transform: scale(1.08);
    box-shadow: 0 0 12px rgba(46, 204, 113, 0.6);
}

/* NAVIGATION LINKS CONTAINER */
.nav-links {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* NAVIGATION BUTTON STYLING */
.nav-links a {
    text-decoration: none;
}

.nav-links button {
    background: white;
    color: #183823;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.25s ease;
}

.nav-links button:hover {
    background: #2ecc71;
    color: white;
    transform: translateY(-2px);
}

/* ACTIVE PAGE HIGHLIGHT */
.nav-links a.active button {
    background: #2ecc71;
    color: white;
}

/* LOGOUT BUTTON / LINK */
.nav-links a.logout-link {
    color: white;
    font-weight: bold;
    margin-left: 10px;
    padding: 10px 14px;
    text-decoration: none;
    transition: color 0.2s ease;
}

.nav-links a.logout-link:hover {
    color: #2ecc71;
}

/* =========================
   MOBILE RESPONSIVE
========================= */
@media (max-width: 768px) {
    nav {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        padding: 15px;
    }

    .nav-links {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .nav-links a {
        width: 100%;
    }

    .nav-links button {
        width: 100%;
    }

    .nav-links a.logout-link {
        margin-left: 0;
        text-align: center;
        padding: 10px 0;
    }
}
</style>

<nav>
    <a href="dashboard.php" class="nav-brand">
        <img src="image/logo.jpg" alt="Dollar Dreams Dormitory Logo">
    </a>
    
    <div class="nav-links">
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <button type="button">Dashboard</button>
        </a>
        <a href="manage_units.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_units.php' ? 'active' : '' ?>">
            <button type="button">Manage Unit</button>
        </a>
        <a href="track_booking.php" class="<?= basename($_SERVER['PHP_SELF']) == 'track_booking.php' ? 'active' : '' ?>">
            <button type="button">Booking Tracker</button>
        </a>
        <a href="viewing_sched.php" class="<?= basename($_SERVER['PHP_SELF']) == 'viewing_sched.php' ? 'active' : '' ?>">
            <button type="button">Viewing Schedule</button>
        </a>
        <a href="signout.php" class="logout-link">Logout</a>
    </div>
</nav>