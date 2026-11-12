# SWD Blog CMS

A server-rendered PHP 8 and MySQL blog with a public reading experience and an authenticated admin CMS.

## Features

- Public homepage with six newest published posts per page
- Pretty article and category URLs: `/post/{slug}` and `/category/{slug}`
- Article reading-time estimate, safe paragraph rendering, and up to three related posts
- Full-text search with a `LIKE` fallback for short queries
- Admin dashboard with post status counts, latest five posts, and post totals by category
- Post/category CRUD, draft and published statuses, excerpt support, unique slugs, and CSRF protection
- Cover image uploads limited to JPEG, PNG, or WebP and 2 MB
- Hardened admin sessions and password hashing

## Screenshots

Screenshots are not included yet. Add screenshots of the homepage and admin dashboard here when available.

## Setup on XAMPP

1. Place the project at `C:\xampp\htdocs\swd\07-php-blog`.
2. Start Apache and MySQL in the XAMPP Control Panel. Ensure `mod_rewrite` is enabled and Apache allows `.htaccess` overrides (`AllowOverride All`).
3. Open `http://localhost/phpmyadmin/`.
   - To restore a previously exported backup, select the `swd_blog` database and use **Import** to load the SQL backup from outside the web root (for example, `C:\xampp\database.sql`).
   - For a fresh install, import `database/schema.sql`; it creates the `swd_blog` database and tables.
   - If you have an older SWD Blog database, run `database/migrate-existing.sql`. If that installation also lacks the excerpt field, run `database/migrate-add-post-excerpt.sql`.
4. The default local connection is host `127.0.0.1`, database `swd_blog`, user `root`, and an empty password. Override these with `BLOG_DB_HOST`, `BLOG_DB_NAME`, `BLOG_DB_USER`, and `BLOG_DB_PASSWORD` environment variables if needed.
5. From Command Prompt or PowerShell, create the first administrator:

   ```text
   cd C:\xampp\htdocs\swd\07-php-blog
   C:\xampp\php\php.exe create-admin.php "abdulrahman" abdulrahman@example.com
   ```

   Enter and confirm a password of at least 12 characters when prompted.
6. Optionally add 12 sample posts across Tutorials, News, and Career:

   ```text
   C:\xampp\php\php.exe seed.php
   ```

   Seeding requires an admin account. Existing seed posts are skipped on subsequent runs.
7. Visit `http://localhost/swd/07-php-blog/`. Admin sign-in is at `http://localhost/swd/07-php-blog/admin/login.php`.

The `app/.htaccess` file denies web access to application internals. `uploads/.htaccess` prevents uploaded scripts from executing. The upload directory is created when the first cover image is uploaded.

## Exporting a database backup

In phpMyAdmin, select `swd_blog`, open **Export**, choose **Quick** and **SQL**, then click **Go**. Save the downloaded file as `database.sql` somewhere outside `C:\xampp\htdocs` (for example, `C:\xampp\database.sql`). To restore it later, select `swd_blog` in phpMyAdmin, open **Import**, choose that file, and click **Go**. Never store database exports under the web root: they contain administrator password hashes and any private content. The root `.htaccess` also denies direct downloads of SQL and common backup files as a defense in depth.

## Folder structure

```text
07-php-blog/
|-- .htaccess             # Pretty routes and backup-file access denial
|-- admin/
|   |-- categories/       # Category list, create, and edit
|   |-- posts/            # Post list, create, and edit
|   |-- index.php         # Admin dashboard
|   |-- login.php
|   `-- logout.php
|-- app/                  # Bootstrap, DB, auth, CSRF, and helpers (web-denied)
|-- assets/
|   `-- style.css
|-- database/
|   |-- schema.sql
|   `-- migrate-*.sql
|-- uploads/              # Cover images (PHP execution disabled)
|-- category.php
|-- create-admin.php      # CLI only
|-- index.php
|-- post.php
|-- search.php
`-- seed.php              # CLI only
```
