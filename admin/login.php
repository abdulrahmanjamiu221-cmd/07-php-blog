<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

if (isset($_SESSION['admin_id'])) {
    redirect('admin/');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare(
        'SELECT id, username, password_hash FROM admins WHERE username = :username OR email = :email LIMIT 1'
    );
    $statement->execute(['username' => $identity, 'email' => $identity]);
    $admin = $statement->fetch();
    $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    $passwordMatches = password_verify($password, $admin['password_hash'] ?? $dummyHash);

    if ($admin && $passwordMatches) {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['last_activity'] = time();
        redirect('admin/');
    }
    $error = 'The username/email or password is incorrect.';
}

$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in | SWD Blog</title>
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body class="admin-body">
<main class="login-wrap">
    <a class="brand login-brand" href="<?= e(url()) ?>">SWD Blog</a>
    <section class="login-card">
        <h1>Admin sign in</h1>
        <p class="post-meta">Sign in to manage your blog.</p>
        <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="identity">Username or email</label>
                <input id="identity" name="identity" type="text" autocomplete="username" required autofocus value="<?= e($_POST['identity'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="button" type="submit">Sign in</button>
        </form>
    </section>
</main>
</body>
</html>
