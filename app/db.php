<?php
declare(strict_types=1);

$host = getenv('BLOG_DB_HOST') ?: '127.0.0.1';
$database = getenv('BLOG_DB_NAME') ?: 'swd_blog';
$username = getenv('BLOG_DB_USER') ?: 'root';
$password = getenv('BLOG_DB_PASSWORD') ?: '';

$dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Blog database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The site is temporarily unavailable. Check the database configuration.');
}
