# SWD Blog CMS

A server-rendered blog and admin CMS for PHP 8 and MySQL. It includes public post, category, and full-text search pages; an authenticated admin area for posts and categories; and image uploads.

## Requirements

- PHP 8.0+ with PDO MySQL, Fileinfo, and mbstring extensions
- MySQL 5.7+ (InnoDB and FULLTEXT support)
- Apache with `mod_rewrite` and `.htaccess` enabled (`AllowOverride All`)

## Setup on XAMPP

1. In phpMyAdmin, import `database/schema.sql`. It creates and selects the `swd_blog` database and its tables, including the posts FULLTEXT index. If you already have the earlier SWD Blog schema, run `database/migrate-existing.sql` against `swd_blog` instead; it preserves existing categories and posts.
2. The default local connection is `127.0.0.1`, database `swd_blog`, user `root`, and an empty password. To override these settings, define `BLOG_DB_HOST`, `BLOG_DB_NAME`, `BLOG_DB_USER`, and `BLOG_DB_PASSWORD` in the PHP process environment.
3. From Command Prompt or PowerShell, create the first administrator:

   ```text
   C:\xampp\php\php.exe create-admin.php "abdulrahman" abdulrahman@example.com
   ```

   The command securely prompts for and confirms a password (minimum 12 characters). The script refuses to run after an admin already exists.
4. Visit `http://localhost/swd/07-php-blog/`. The admin login is at `http://localhost/swd/07-php-blog/admin/login.php`.

Apache must allow the included access files: `app/.htaccess` denies web access to application internals, and `uploads/.htaccess` prevents uploaded scripts from being served as executable code. The upload directory is created automatically when the first cover image is uploaded.

## Features

- Draft and published post status, unique slugs, categories, author attribution, and unique cover image filenames
- Search over published post titles and bodies using MySQL FULLTEXT
- Pretty public routes at `/post/{slug}` and `/category/{slug}`, with six newest posts per homepage page
- Password hashing, strict cookie-only sessions, HTTP-only/SameSite cookies, session ID rotation, idle timeout, and CSRF-protected mutations
- Upload validation based on actual image content (JPEG, PNG, WebP; maximum 2 MB)
- Post and category administration with referential integrity; categories with assigned posts cannot be deleted

If you created the database from an earlier version of `database/schema.sql` that did not have an excerpt column, run `database/migrate-add-post-excerpt.sql` once.

### Cover-image upload checks

- The upload error code is checked before inspecting the temporary file, so incomplete or failed uploads are rejected.
- `is_uploaded_file()` ensures the temporary path came from PHP's HTTP upload mechanism, preventing an attacker from asking the app to move an arbitrary local file.
- A 2 MB limit bounds disk usage and reduces storage-exhaustion denial-of-service risk.
- `finfo` checks the file's actual MIME type and allows only JPEG, PNG, or WebP; the browser-provided filename and MIME type are not trusted, preventing extension/MIME spoofing and arbitrary file uploads.
- `getimagesize()` confirms the content has a recognizable image structure, rejecting many non-image files disguised as images.
- The stored extension is selected from the verified MIME type, and the filename is generated from 16 random bytes rather than user input, preventing path traversal, filename collisions, and overwriting an existing file.
- Files are moved only into the fixed `uploads/` directory, where `.htaccess` denies PHP execution, limiting the impact if an unexpected script-like file is ever stored.
