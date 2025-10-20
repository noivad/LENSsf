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
    $password = (string) ($_POST['password'] ?? '');

    // Validate slider puzzle captcha (client sets this when solved)
    if (($_POST['slider_captcha'] ?? '') !== 'verified') {
        $errors[] = 'Please complete the slider puzzle verification.';
    }

    // Validate Turnstile token
    $turnstileToken = $_POST['cf-turnstile-response'] ?? '';
    if (!verify_turnstile_token($turnstileToken)) {
        $errors[] = 'Captcha verification failed, please try again.';
    }

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter your username and password.';
    }

    if (!$errors) {
        $user = $userManager->verify($username, $password);
        if (!$user) {
            $errors[] = 'Invalid username or password.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['current_user'] = $user['display_name'];
            // Redirect to the calendar 7x5 page on successful login
            redirect('../calendar-7x5.php');
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/longbow.slidercaptcha@1.1.0/dist/slidercaptcha.min.css" />
    <style>
        .auth-card { max-width: 520px; margin: 2rem auto; }
        .note { color: var(--text-subtle); font-size: 0.9rem; }
        .captcha-group { display: grid; gap: 1rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">Local Event Network Service</a></h1>
            <nav>
                <a href="register.php">Create Account</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="card auth-card">
            <h2>Sign in</h2>
            <p class="note">Your username is separate from your display name (what others see). Use your username to sign in.</p>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="form" id="loginForm">
                <div class="form-row">
                    <label>Username
                        <input type="text" name="username" autocomplete="username" required />
                    </label>
                </div>
                <div class="form-row">
                    <label>Display name (shown to others)
                        <input type="text" name="display_name" placeholder="e.g., Alex J." />
                    </label>
                </div>
                <div class="form-row">
                    <label>Password
                        <input type="password" name="password" autocomplete="current-password" required />
                    </label>
                </div>

                <div class="captcha-group">
                    <div id="sliderCaptcha"></div>
                    <input type="hidden" name="slider_captcha" id="slider_captcha" value="" />
                    <div class="cf-turnstile" data-sitekey="1x00000000000000000000AA"></div>
                </div>

                <button type="submit" class="button">Login</button>
            </form>

            <div class="card" style="margin-top:1rem;padding:1rem">
                <h3>Or sign in with</h3>
                <div class="actions" style="gap:0.5rem;display:flex;flex-wrap:wrap">
                    <a class="button-small" href="oauth_start.php?provider=google">Google</a>
                    <a class="button-small" href="oauth_start.php?provider=facebook">Facebook</a>
                    <a class="button-small" href="oauth_start.php?provider=apple">Apple</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Local Event Network Service</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/longbow.slidercaptcha@1.1.0/dist/slidercaptcha.min.js"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.sliderCaptcha) {
                sliderCaptcha({
                    id: 'sliderCaptcha',
                    width: 280,
                    height: 155,
                    sliderL: 42,
                    sliderR: 9,
                    offset: 5,
                    loadingText: 'Loading...',
                    failedText: 'Try again',
                    barText: 'Slide to match the puzzle piece',
                    repeatIcon: 'fa fa-redo',
                    onSuccess: function () {
                        document.getElementById('slider_captcha').value = 'verified';
                    }
                });
            }
        });
    </script>
</body>
</html>
