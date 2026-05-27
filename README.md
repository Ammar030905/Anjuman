# Anjuman E Ezzy

Private live-stream website built with core PHP, Bootstrap 5, jQuery, and AJAX. It can run against a local MySQL database or a hosted Supabase PostgreSQL database for deployment.

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

1. For local development, import `database/schema.sql` into MySQL.
	If your project already has the old email-based users table, run `database/migrate_users_to_its.sql` instead.
2. For Supabase deployment, create a PostgreSQL database and import `database/schema_supabase.sql`.
3. Set these environment variables on Render or your hosting provider: `BASE_URL`, `APP_ENV`, `DB_DRIVER=pgsql`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `DB_SSLMODE=require`.
4. Make sure the `users` table contains at least one admin account from the seed data.
5. Log in at `login.php` using ITS Number `12345678` and password `Admin@1234`.
6. Open `admin/users.php` to create more users.
7. Open `admin/streams.php` and paste a YouTube live watch URL to start a stream.

## Stream workflow

- Start the broadcast manually in YouTube/OBS.
- Copy the public YouTube live URL.
- Paste it into the admin stream form.
- The app extracts the video ID, stores it, and embeds the live player on the site.

## Deployment Notes

- If Render is set to Docker deploy, it will now find the root [Dockerfile](Dockerfile) and build the PHP container automatically.
- Render should serve the PHP app from the repository root.
- Point the app at Supabase using the PostgreSQL connection values from the Supabase dashboard.
- Keep `BASE_URL` set to the public Render URL so redirects and embedded player origins resolve correctly.
- The database helpers avoid automatic schema rewrites on PostgreSQL, so the schema must be provisioned before first login.

## Environment Variables

Set these values in Render or your hosting provider for the PHP app:

```env
APP_ENV=production
BASE_URL=https://your-app.onrender.com
DB_DRIVER=pgsql
DB_HOST=aws-0-us-west-1.pooler.supabase.com
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASS=your_supabase_password
DB_SSLMODE=require
DB_SCHEMA=public
```

If you are still running locally with MySQL, keep `DB_DRIVER=mysql` and use the local database values instead.

## Notes

- Streams are intended to be unlisted for privacy.
- The site uses CSRF-protected AJAX endpoints and prepared statements.
- Users watch the stream entirely inside the website.
