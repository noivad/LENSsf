<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$provider = strtolower(trim((string) ($_GET['provider'] ?? 'unknown')));
$name = trim((string) ($_GET['name'] ?? 'User'));

// Simulate setting an authenticated session
$_SESSION['user_id'] = $_SESSION['user_id'] ?? random_int(1000, 999999);
$_SESSION['username'] = ($provider ?: 'oauth') . '_' . preg_replace('/\s+/', '_', strtolower($name));
$_SESSION['display_name'] = $name;
// Also set demo current_user used by app UI
$_SESSION['current_user'] = $name;

header('Location: ../index.php');
exit;
