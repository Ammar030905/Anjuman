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
4. Edit the real `.env` file in the project root with your local Laragon or Hostinger values.
5. `config/config.php` reads from `.env` first, then falls back to server environment variables.
6. Make sure `BASE_URL` matches your site URL, for example `http://localhost/Anjuman` locally or `https://your-domain.com` on Hostinger.
7. Make sure the `users` table contains at least one admin account from the seed data.
8. Log in at `login.php` using ITS Number `12345678`.
9. Open `admin/users.php` to create more users by ITS number only.
10. Open `admin/streams.php` and paste a YouTube live watch URL to start a stream.

## Stream workflow

- Start the broadcast manually in YouTube/OBS.
- Copy the public YouTube live URL.
- Paste it into the admin stream form.
- The app extracts the video ID, stores it, and embeds the live player on the site.

## Environment Variables

Set these values in `.env` for local Laragon development or Hostinger deployment:

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

### Hostinger deployment notes

1. Upload the project files to `public_html` or your chosen app folder.
2. Edit the root `.env` file with your Hostinger database credentials and real `BASE_URL`.
3. Set `APP_ENV=production` in `.env`.
4. Import `database/schema.sql` into the Hostinger MySQL database.
5. Do not hardcode database credentials inside PHP files; keep them only in `.env`.
6. If you later switch domains, update only `BASE_URL` in `.env`.

## Notes

- Streams are intended to be unlisted for privacy.
- The site uses CSRF-protected AJAX endpoints and prepared statements.
- Login is ITS-only and enforces a single active session per user.
- Users watch the stream entirely inside the website.
