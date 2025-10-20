<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$provider = strtolower(trim((string) ($_GET['provider'] ?? '')));
if ($provider === '') {
    header('Location: login.php');
    exit;
}

// In a real implementation, redirect to the OAuth2 authorization URL for $provider
// For this demo, simulate success and bounce to the callback immediately.
$name = ucfirst($provider) . ' User';
header('Location: oauth_callback.php?provider=' . urlencode($provider) . '&mock=1&name=' . urlencode($name));
exit;
