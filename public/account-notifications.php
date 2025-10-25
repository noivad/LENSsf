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

$notifications = [
    ['id' => 1, 'message' => 'Your event "Jazz Night" has 5 new attendees', 'time' => '2 hours ago', 'read' => false],
    ['id' => 2, 'message' => 'New event matching your interests: Tech Conference', 'time' => '5 hours ago', 'read' => false],
    ['id' => 3, 'message' => 'Event reminder: Art Exhibition Opening tomorrow at 6pm', 'time' => '1 day ago', 'read' => true],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Notifications</title>
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
            <h1 class="header-title">LENSsf - Notifications</h1>
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
                <h2>Notifications</h2>

                <section class="card">
                    <?php if (empty($notifications)): ?>
                        <p class="subtle">You have no notifications.</p>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?= $notification['read'] ? 'read' : 'unread' ?>" style="padding: 1rem; border-bottom: 1px solid var(--border-color); <?= !$notification['read'] ? 'background: var(--bg-color);' : '' ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div style="flex: 1;">
                                            <p style="margin: 0;"><?= e($notification['message']) ?></p>
                                            <small class="subtle"><?= e($notification['time']) ?></small>
                                        </div>
                                        <?php if (!$notification['read']): ?>
                                            <span style="width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; margin-left: 1rem; margin-top: 0.5rem;"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
