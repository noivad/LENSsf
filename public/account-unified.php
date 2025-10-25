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

$pdo = Database::connect();
$siteName = SITE_NAME;
$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$userEmail = $_SESSION['user_email'] ?? 'demo@example.com';
$userPhone = $_SESSION['user_phone'] ?? '';
$userAddress = $_SESSION['user_address'] ?? '';

$activeTab = $_GET['tab'] ?? 'info';

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
        redirect('account-unified.php?tab=info');
    } elseif ($action === 'update_account') {
        $newUsername = trim($_POST['username'] ?? '');
        $newPassword = trim($_POST['password'] ?? '');
        
        if ($newUsername !== '') {
            $_SESSION['current_user'] = $newUsername;
            set_flash('Account information updated successfully!');
        } else {
            set_flash('Username is required.', 'error');
        }
        redirect('account-unified.php?tab=info');
    }
}

$stmt = $pdo->prepare('SELECT e.*, v.name as venue_name FROM events e LEFT JOIN venues v ON e.venue_id = v.id WHERE e.owner = ? AND e.event_date < ? ORDER BY e.event_date DESC');
$stmt->execute([$currentUser, date('Y-m-d')]);
$pastEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::My Account</title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="dark">
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
                        <span>All Events</span>
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
            <h1 class="header-title">My Account</h1>
            <div class="user-controls">
                <div class="searchbar">
                    <input id="calendar-search" type="text" placeholder="Search events/venues/descriptions or #tag" />
                </div>
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="theme-icon">☀️</span> Toggle Theme
                </button>
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/150?img=33" alt="User Avatar" class="user-avatar" onclick="toggleUserDropdown()">
                    <div class="user-dropdown" id="userDropdown">
                        <a href="account-unified.php?tab=info" class="dropdown-item">
                            ⚙️ Account & Contact Info
                        </a>
                        <a href="account-unified.php?tab=notifications" class="dropdown-item">
                            🔔 Notifications
                        </a>
                        <a href="account-unified.php?tab=past-events" class="dropdown-item">
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
            <div class="account-tabs">
                <a href="?tab=info" class="account-tab <?= $activeTab === 'info' ? 'active' : '' ?>">
                    ⚙️ Account & Contact Info
                </a>
                <a href="?tab=notifications" class="account-tab <?= $activeTab === 'notifications' ? 'active' : '' ?>">
                    🔔 Notifications
                </a>
                <a href="?tab=past-events" class="account-tab <?= $activeTab === 'past-events' ? 'active' : '' ?>">
                    📜 My Past Events
                </a>
            </div>

            <div class="account-content">
                <?php foreach (get_flashes() as $flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($activeTab === 'info'): ?>
                    <div class="account-section">
                        <div class="account-info-grid">
                            <div class="account-card">
                                <h2 class="account-section-title">Account Information</h2>
                                <form method="post" class="account-form">
                                    <input type="hidden" name="action" value="update_account">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Username *</label>
                                        <input type="text" name="username" value="<?= e($currentUser) ?>" class="form-input" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current">
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn-primary">Update Account</button>
                                    </div>
                                </form>
                            </div>

                            <div class="account-card">
                                <h2 class="account-section-title">Contact Information</h2>
                                <form method="post" class="account-form">
                                    <input type="hidden" name="action" value="update_contact">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" value="<?= e($userEmail) ?>" class="form-input" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" name="phone" value="<?= e($userPhone) ?>" class="form-input" placeholder="+1 (555) 123-4567">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" rows="3" class="form-textarea" placeholder="Street address, city, state, zip"><?= e($userAddress) ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn-primary">Update Contact Info</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php elseif ($activeTab === 'notifications'): ?>
                    <div class="account-section">
                        <h2 class="account-section-title">Notifications</h2>
                        <div class="notifications-list">
                            <div class="notification-item">
                                <div class="notification-icon">📅</div>
                                <div class="notification-content">
                                    <div class="notification-title">Event Reminder</div>
                                    <div class="notification-text">Jazz Concert at Blue Note is tomorrow at 8:00 PM</div>
                                    <div class="notification-time">2 hours ago</div>
                                </div>
                            </div>
                            <div class="notification-item">
                                <div class="notification-icon">👥</div>
                                <div class="notification-content">
                                    <div class="notification-title">New Follower</div>
                                    <div class="notification-text">Alice started following your events</div>
                                    <div class="notification-time">5 hours ago</div>
                                </div>
                            </div>
                            <div class="notification-item">
                                <div class="notification-icon">🎉</div>
                                <div class="notification-content">
                                    <div class="notification-title">Event Published</div>
                                    <div class="notification-text">Your event "Summer Festival" has been published</div>
                                    <div class="notification-time">1 day ago</div>
                                </div>
                            </div>
                            <div class="notification-item read">
                                <div class="notification-icon">💬</div>
                                <div class="notification-content">
                                    <div class="notification-title">New Comment</div>
                                    <div class="notification-text">Bob commented on "Art Exhibition"</div>
                                    <div class="notification-time">3 days ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($activeTab === 'past-events'): ?>
                    <div class="account-section">
                        <h2 class="account-section-title">My Past Events</h2>
                        <?php if (empty($pastEvents)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📜</div>
                                <p>You have no past events yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="past-events-grid">
                                <?php foreach ($pastEvents as $event): ?>
                                    <div class="event-card">
                                        <div class="event-card-image">
                                            <?php $imgUrl = 'https://picsum.photos/seed/' . rawurlencode($event['title']) . '/400/250'; ?>
                                            <img src="<?= $imgUrl ?>" alt="<?= e($event['title']) ?>">
                                        </div>
                                        <div class="event-card-body">
                                            <h3 class="event-card-title"><?= e($event['title']) ?></h3>
                                            <div class="event-card-detail">📅 <?= e(date('F j, Y', strtotime($event['event_date']))) ?></div>
                                            <div class="event-card-detail">🕐 <?= e($event['start_time'] ?? 'TBA') ?></div>
                                            <div class="event-card-detail">📍 <?= e($event['venue_name'] ?? 'TBA') ?></div>
                                            <?php if (!empty($event['description'])): ?>
                                                <p class="event-card-description"><?= e($event['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <p>© <?php echo date('Y'); ?> LENS - Local Event Network Service | Built with ❤️ for the community</p>
        </footer>
    </div>

    <script src="js/calendar-7x5.js"></script>
</body>
</html>
