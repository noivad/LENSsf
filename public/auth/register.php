<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/helpers.php';
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/managers/UserManager.php';

if (file_exists(__DIR__ . '/../../config.php')) {
    require __DIR__ . '/../../config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

function verify_turnstile_token(?string $token): bool
{
    $token = trim((string) $token);
    if ($token === '') {
        return false;
    }

    $secret = getenv('TURNSTILE_SECRET') ?: (defined('TURNSTILE_SECRET') ? (string) TURNSTILE_SECRET : '');

    if ($secret === '') {
        // In development mode, allow if no secret configured.
        return true;
    }

    $data = http_build_query(['secret' => $secret, 'response' => $token]);
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 5,
        ],
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    if ($result === false) {
        return false;
    }

    $json = json_decode($result, true);
    return !empty($json['success']);
}

$pdo = Database::connect();
$userManager = new UserManager($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    if (($_POST['slider_captcha'] ?? '') !== 'verified') {
        $errors[] = 'Please complete the slider puzzle verification.';
    }

    $turnstileToken = $_POST['cf-turnstile-response'] ?? '';
    if (!verify_turnstile_token($turnstileToken)) {
        $errors[] = 'Captcha verification failed, please try again.';
    }

    if ($username === '' || $displayName === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = 'Please fill out all fields.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $user = $userManager->create($username, $displayName, $email, $password);
        if (!$user) {
            $errors[] = 'Could not create user (username or email may already be in use).';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            redirect('../calendar-7x5.php');
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/auth.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/longbow.slidercaptcha@1.1.0/dist/slidercaptcha.min.css" />
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">Local Event Network Service</a></h1>
            <nav>
                <a href="login.php">Login</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="card auth-card">
            <h2>Create your account</h2>
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="form" id="registerForm">
                <div class="form-row">
                    <label>Username
                        <input type="text" name="username" autocomplete="username" required />
                    </label>
                </div>
                <div class="form-row">
                    <label>Display name
                        <input type="text" name="display_name" required />
                    </label>
                </div>
                <div class="form-row">
                    <label>Email
                        <input type="email" name="email" autocomplete="email" required />
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-split">
                        <label>Password
                            <input type="password" name="password" autocomplete="new-password" required />
                        </label>
                        <label>Confirm
                            <input type="password" name="confirm" autocomplete="new-password" required />
                        </label>
                    </div>
                </div>

                <div class="captcha-group">
                    <div id="sliderCaptcha"></div>
                    <input type="hidden" name="slider_captcha" id="slider_captcha" value="" />
                    <div class="cf-turnstile" data-sitekey="1x00000000000000000000AA"></div>
                </div>

                <button type="submit" class="button">Create Account</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Local Event Network Service</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/longbow.slidercaptcha@1.1.0/dist/slidercaptcha.min.js"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script src="../js/auth.js"></script>
</body>
</html>
