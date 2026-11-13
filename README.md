# Dollar Dreams Dormitory

Booking website for Dollar Dreams Dormitory (PHP + MySQL, hosted on InfinityFree).

## Pages

| Page | Who | What it does |
|---|---|---|
| `index.php` | Public | Landing page: rooms with today's free beds, the stay price calculator, photos, location, FAQ |
| `book.php` | Public | Browse and filter rooms, reserve a room (with a price estimate), request a viewing |
| `signin.php` | Admin | Login |
| `dashboard.php` | Admin | Today: free beds, who is in which bed (timeline), payments to collect, viewing requests, revenue |
| `track_booking.php` | Admin | Bookings by status, edit dates/rate/payment, view guest ID / payment proof |
| `manage_units.php`, `unit_form.php` | Admin | List, add, edit and delete units |
| `viewing_sched.php` | Admin | Viewing time slots and guests' viewing requests |

## Actions (no page of their own)

| File | Who | `type` values |
|---|---|---|
| `load.php` | Public | `units`, `schedule` (GET, returns JSON for `book.php`) |
| `save.php` | Public / Admin | `booking`, `viewing` (public), `schedule` (admin) |
| `update.php` | Admin | `booking`, `viewing` (request status) |
| `delete.php` | Admin | `booking`, `unit`, `schedule`, `viewing` |
| `view_file.php` | Admin | Opens a guest's ID or payment proof |

Shared code: `db.php` (connection), `auth.php` (login + CSRF), `functions.php` (pricing, availability, uploads, email).
Prices are set in `functions.php` (`RATE_DAILY`, `RATE_WEEKLY`, `RATE_MONTHLY`); the landing page, booking form
and admin screens all read them from there.

Look and feel:
- `assets/home.css`, `assets/home.js`: the landing page (loading screen, stacking sections, free-today board,
  walking route, booking timeline). Smooth scrolling comes from Lenis on jsDelivr; everything still works without it.
- `assets/site.css`, `assets/site.js`: the booking and sign-in pages (green and coin-cream colours from the logo).
- `assets/admin.css`, `assets/charts.js`: admin screens.
- `partials/admin_top.php` / `partials/admin_bottom.php`: the admin frame (sidebar, top bar), included by every admin page.

## Testing on your own computer (XAMPP)

1. InfinityFree phpMyAdmin -> your database -> **Export** -> save the `.sql` file.
2. Local phpMyAdmin (`http://localhost/phpmyadmin`) -> create database `dorm_tracker` -> **Import** that file.
3. Put your XAMPP MySQL root password in `config.local.php` (`db_pass`).
4. Double-click `start-local.bat` (one folder up) and open `http://localhost:8000`.

When `config.local.php` exists, the site uses it instead of `config.php`.
**Never upload `config.local.php` to InfinityFree.** No emails are sent while testing locally.

## Automatic deploy from GitHub

Every push to `main` uploads the changed files to InfinityFree (`.github/workflows/deploy.yml`).
See each run under the repo's **Actions** tab; the live site updates about a minute after a push.

One-time setup, **in this order** (the new code needs `config.php` on the server, or the live site shows an error):

1. On InfinityFree (File Manager or FTP), put `config.php` in `htdocs` with the real database and Resend details.
2. Run `setup.sql` once in phpMyAdmin.
3. GitHub repo → Settings → Secrets and variables → Actions → add `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`
   (InfinityFree control panel → FTP Details).
4. Actions tab → Deploy to InfinityFree → Run workflow (or just push a change).

The deploy never touches `config.php`, `uploads/` or `private_uploads/` on the server, because they are not in the repo.

## Deploying to InfinityFree by hand

1. Upload every file and folder to `htdocs` (keep the `.htaccess` files in the root, `uploads/` and `private_uploads/`).
2. Create `config.php` from `config.example.php` with the database and Resend details.
   Never put `config.php` on GitHub (`.gitignore` blocks it).
3. Run `setup.sql` once in phpMyAdmin.
4. On the hosting, move every file in `uploads/` whose name contains `_id_` or `_pay_` into `private_uploads/`.
5. Delete the old action files from `htdocs` (they were merged into `load.php`, `save.php`, `update.php`, `delete.php`):
   `delete_booking.php`, `delete_schedule.php`, `delete_unit.php`, `delete_viewing_request.php`,
   `save_booking.php`, `save_schedule.php`, `save_viewing.php`, `update_booking.php`,
   `update_viewing_status.php`, `load_units.php`, `load_schedule.php`, and `partials/navbar.php`
   (replaced by `partials/admin_top.php` and `admin_bottom.php`).

Guest IDs and payment proofs are saved in `private_uploads/`, which browsers can't open directly;
admins open them from the Booking Tracker.
