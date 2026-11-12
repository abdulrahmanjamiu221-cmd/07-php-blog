<?php
declare(strict_types=1);

function require_admin(): void
{
    if (!isset($_SESSION['admin_id'], $_SESSION['admin_username'])) {
        set_flash('error', 'Please sign in to access the admin area.');
        redirect('admin/login.php');
    }

    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
    if ($lastActivity > 0 && time() - $lastActivity > 1800) {
        logout_admin();
        set_flash('error', 'Your session expired. Please sign in again.');
        redirect('admin/login.php');
    }

    $_SESSION['last_activity'] = time();
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}
