# Anjuman E Ezzy

Private live-stream website built with core PHP, Bootstrap 5, jQuery, and AJAX. It is set up for local Laragon development with a MySQL database.

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

1. Place the project inside your Laragon `www` folder, for example `c:\laragon\www\Anjuman`.
2. Start Apache and MySQL from Laragon.
3. Import `database/schema.sql` into MySQL.
4. Make sure `config/config.php` uses your Laragon URL, for example `http://localhost/Anjuman`.
5. Make sure the `users` table contains at least one admin account from the seed data.
6. Log in at `login.php` using ITS Number `12345678`.
7. Open `admin/users.php` to create more users by ITS number only.
8. Open `admin/streams.php` and paste a YouTube live watch URL to start a stream.

## Stream workflow

- Start the broadcast manually in YouTube/OBS.
- Copy the public YouTube live URL.
- Paste it into the admin stream form.
- The app extracts the video ID, stores it, and embeds the live player on the site.

## Environment Variables

Set these values for local Laragon development:

```env
APP_ENV=development
BASE_URL=http://localhost/Anjuman
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=
DB_NAME=anjuman_ezzy
DB_USER=root
DB_PASS=
DB_SSLMODE=
DB_SCHEMA=anjuman_ezzy
```

If you use a different Laragon virtual host, update `BASE_URL` accordingly.

## Notes

- Streams are intended to be unlisted for privacy.
- The site uses CSRF-protected AJAX endpoints and prepared statements.
- Login is ITS-only and enforces a single active session per user.
- Users watch the stream entirely inside the website.
