# SWD Blog CMS

A server-rendered blog and admin CMS for PHP 8 and MySQL. It includes public post, category, and full-text search pages; an authenticated admin area for posts and categories; and image uploads.

## Requirements

- PHP 8.0+ with PDO MySQL, Fileinfo, and mbstring extensions
- MySQL 5.7+ (InnoDB and FULLTEXT support)
- Apache with `.htaccess` enabled (`AllowOverride All`)

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
- Password hashing, strict cookie-only sessions, HTTP-only/SameSite cookies, session ID rotation, idle timeout, and CSRF-protected mutations
- Upload validation based on actual image content (JPEG, PNG, WebP; maximum 5 MB)
- Post and category administration with referential integrity; categories with assigned posts cannot be deleted
