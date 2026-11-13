-- Run ONCE in phpMyAdmin (InfinityFree control panel -> phpMyAdmin -> your database -> SQL tab).
-- If a line says "Duplicate column name", that step was already done - that's fine.

-- 1. Room for hashed admin passwords (the password is converted at your next login).
ALTER TABLE admin MODIFY password VARCHAR(255) NOT NULL;

-- 2. Keep the GCash payment proof with each booking (shown in the Booking Tracker).
ALTER TABLE bookings ADD COLUMN payment_proof VARCHAR(255) NULL AFTER id_upload;

-- 3. Old guest ID files were moved from uploads/ to private_uploads/ - point the bookings at the new place.
UPDATE bookings
SET id_upload = REPLACE(id_upload, 'uploads/', 'private_uploads/')
WHERE id_upload LIKE 'uploads/%';
