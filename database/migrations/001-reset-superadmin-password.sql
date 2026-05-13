-- Database migration: Reset the seeded Super Admin password.
-- Run this after pulling new branches if the default super admin password needs to be updated.

UPDATE users
SET password = '$2b$12$ZxvXynbDRT5kQ/MkN5J0h.WLkHNo1UU11uqeJA/BwZSDCHxddXTDK'
WHERE email = 'superadmin@agri.local';
