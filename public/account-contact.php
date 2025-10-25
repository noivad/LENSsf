<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Local Event Network Service');
}

$siteName = SITE_NAME;
$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$userEmail = $_SESSION['user_email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? '';
$userAddress = $_SESSION['user_address'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_contact') {
        $newEmail = trim($_POST['email'] ?? '');
        $newPhone = trim($_POST['phone'] ?? '');
        $newAddress = trim($_POST['address'] ?? '');
        
        if ($newEmail !== '') {
            $_SESSION['user_email'] = $newEmail;
            $_SESSION['user_phone'] = $newPhone;
            $_SESSION['user_address'] = $newAddress;
            set_flash('Contact information updated successfully!');
        } else {
            set_flash('Email is required.', 'error');
        }
        redirect('account-contact.php');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Contact Info</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/account.css">
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="light">
    <div class="app-container">
        <nav class="sidebar-nav">
            <div class="nav-logo">LENS</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="calendar-7x5.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="event-list.php" class="nav-link">
                        <span class="nav-icon">📋</span>
                        <span>Events</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="venue-info.php" class="nav-link">
                        <span class="nav-icon">📍</span>
                        <span>Venues</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tags.php" class="nav-link">
                        <span class="nav-icon">🏷️</span>
                        <span>Tags</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="add-event.php" class="nav-link">
                        <span class="nav-icon">➕</span>
                        <span>Add Event</span>
                    </a>
                </li>
            </ul>
        </nav>

        <header class="top-header">
            <h1 class="header-title">LENSsf - Contact Information</h1>
            <div class="user-controls">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="theme-icon">☀️</span> Toggle Theme
                </button>
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/150?img=33" alt="User Avatar" class="user-avatar" onclick="toggleUserDropdown()">
                    <div class="user-dropdown" id="userDropdown">
                        <a href="account-contact.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            📧 Contact Info
                        </a>
                        <a href="account-notifications.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            🔔 Notifications
                        </a>
                        <a href="account.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            ⚙️ Account Info
                        </a>
                        <a href="account-past-events.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            📜 My Past Events
                        </a>
                        <div class="dropdown-item" onclick="alert('Logging out...')">
                            🚪 Logout
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <?php foreach (get_flashes() as $flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endforeach; ?>

                <h2>Contact Information</h2>

                <section class="card">
                    <h3>Update Contact Details</h3>
                    
                    <form method="post" class="form">
                        <input type="hidden" name="action" value="update_contact">
                        
                        <div class="form-row">
                            <label>
                                Email *
                                <input type="email" name="email" value="<?= e($userEmail) ?>" required>
                            </label>
                        </div>

                        <div class="form-row">
                            <label>
                                Phone Number
                                <input type="text" name="phone" value="<?= e($userPhone) ?>" placeholder="+1 (555) 123-4567">
                            </label>
                        </div>

                        <div class="form-row">
                            <label>
                                Address
                                <textarea name="address" rows="3" placeholder="Street address, city, state, zip"><?= e($userAddress) ?></textarea>
                            </label>
                        </div>

                        <div class="form-row">
                            <button type="submit" class="button">Update Contact Info</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; <?= date('Y') ?> LENSsf - Local Event Network Service | Built with ❤️ for the community</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
    <script src="js/calendar-7x5.js"></script>
</body>
</html>
