# Anjuman E Ezzy

Private live-stream website built with core PHP, MySQL, Bootstrap 5, jQuery, and AJAX.

## What changed

- Removed Google OAuth and YouTube API dependencies
- Switched to embedded YouTube live URLs only
- Added local stream polling and embed playback
- Simplified admin user and stream management

## Folder structure

- `assets/` - styles and JavaScript
- `admin/` - admin dashboard, users, and streams
- `ajax/` - AJAX endpoints for users, streams, and live status
- `includes/` - auth, CSRF, database, and stream helpers
- `database/` - SQL schema
- `uploads/` - reserved for future assets

## Installation

1. Import `database/schema.sql` into MySQL.
	If your project already has the old email-based users table, run `database/migrate_users_to_its.sql` instead.
2. Update `config/config.php` with your database credentials and local `BASE_URL`.
3. Make sure the `users` table contains at least one admin account from the seed data.
4. Log in at `login.php` using ITS Number `12345678` and password `Admin@1234`.
5. Open `admin/users.php` to create more users.
6. Open `admin/streams.php` and paste a YouTube live watch URL to start a stream.

## Stream workflow

- Start the broadcast manually in YouTube/OBS.
- Copy the public YouTube live URL.
- Paste it into the admin stream form.
- The app extracts the video ID, stores it, and embeds the live player on the site.

## Notes

- Streams are intended to be unlisted for privacy.
- The site uses CSRF-protected AJAX endpoints and prepared statements.
- Users watch the stream entirely inside the website.
