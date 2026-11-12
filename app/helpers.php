<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path === '' ? '' : '/' . $path);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function slugify(string $value): string
{
    $value = trim($value);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function page_header(string $title): void
{
    $flash = get_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | SWD Blog</title>
        <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
    </head>
    <body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(url()) ?>">SWD Blog</a>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="<?= e(url()) ?>">Home</a>
                <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
            </nav>
        </div>
    </header>
    <main class="container site-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function page_footer(): void
{
    ?>
    </main>
    <footer class="site-footer"><div class="container">SWD Blog</div></footer>
    </body>
    </html>
    <?php
}

function admin_header(string $title): void
{
    $flash = get_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | SWD Blog Admin</title>
        <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
    </head>
    <body class="admin-body">
    <div class="admin-shell">
        <aside class="sidebar">
            <a class="brand sidebar-brand" href="<?= e(url('admin/')) ?>">SWD Blog</a>
            <div class="sidebar-label">Administration</div>
            <nav class="sidebar-nav" aria-label="Administration">
                <a href="<?= e(url('admin/')) ?>">Dashboard</a>
                <a href="<?= e(url('admin/posts/')) ?>">Posts</a>
                <a href="<?= e(url('admin/categories/')) ?>">Categories</a>
                <a href="<?= e(url()) ?>" target="_blank" rel="noopener">View site</a>
                <form action="<?= e(url('admin/logout.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="nav-button" type="submit">Logout</button>
                </form>
            </nav>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar"><h1><?= e($title) ?></h1></header>
            <div class="admin-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
            </div>
        </main>
    </div>
    </body>
    </html>
    <?php
}

function store_cover_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image could not be uploaded.');
    }

    $maxBytes = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Choose an image smaller than 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if ($imageInfo === false || !isset($extensions[$mime])) {
        throw new RuntimeException('Only JPEG, PNG, and WebP images are allowed.');
    }

    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The upload directory is unavailable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The uploaded image could not be saved.');
    }

    return $filename;
}

function remove_cover_image(?string $filename): void
{
    if (!$filename || basename($filename) !== $filename) {
        return;
    }
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path)) {
        unlink($path);
    }
}
