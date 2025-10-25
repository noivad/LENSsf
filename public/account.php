<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';
require __DIR__ . '/../includes/managers/PhotoManager.php';

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
$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';
$maxUploadSize = defined('MAX_UPLOAD_SIZE') ? (int) MAX_UPLOAD_SIZE : 5_242_880;

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);
$photoManager = new PhotoManager($pdo, $uploadDir, $maxUploadSize);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newNickname = trim($_POST['nickname'] ?? '');
        $newFirstName = trim($_POST['first_name'] ?? '');
        $newLastName = trim($_POST['last_name'] ?? '');
        
        if ($newUsername !== '' && $newEmail !== '') {
            $_SESSION['current_user'] = $newUsername;
            $_SESSION['user_email'] = $newEmail;
            $_SESSION['user_nickname'] = $newNickname;
            $_SESSION['user_first_name'] = $newFirstName;
            $_SESSION['user_last_name'] = $newLastName;
            set_flash('Profile updated successfully!');
        } else {
            set_flash('Please provide both username and email.', 'error');
        }
        redirect('account.php');
    }

    if ($action === 'update_password') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword === '') {
            set_flash('Password cannot be empty.', 'error');
        } elseif ($newPassword !== $confirmPassword) {
            set_flash('Passwords do not match.', 'error');
        } else {
            $_SESSION['user_password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            set_flash('Password updated successfully!');
        }
        redirect('account.php');
    }

    if ($action === 'add_photo_comment') {
        $photoId = $_POST['photo_id'] ?? '';
        $comment = trim($_POST['comment'] ?? '');
        
        if ($photoId !== '' && $comment !== '') {
            $photoManager->addComment($photoId, $currentUser, $comment);
            set_flash('Comment added successfully!');
        } else {
            set_flash('Please provide a comment.', 'error');
        }
        redirect('account.php');
    }

    if ($action === 'delete_photo') {
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        
        if ($photoId > 0) {
            $stmt = $pdo->prepare('DELETE FROM photos WHERE id = :id AND uploaded_by = :user');
            $stmt->execute([':id' => $photoId, ':user' => $currentUser]);
            set_flash('Photo deleted successfully!');
        } else {
            set_flash('Invalid photo ID.', 'error');
        }
        redirect('account.php');
    }
}

$userEvents = array_filter($eventManager->all(), function ($event) use ($currentUser) {
    return strcasecmp($event['owner'], $currentUser) === 0;
});

usort($userEvents, function ($a, $b) {
    return strcmp($b['event_date'] ?? '', $a['event_date'] ?? '');
});

$allPhotos = $photoManager->all();
$userPhotos = array_filter($allPhotos, function ($photo) use ($currentUser) {
    return strcasecmp($photo['uploaded_by'], $currentUser) === 0;
});

$userEmail = $_SESSION['user_email'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Account</title>
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
            <h1 class="header-title">LENSsf - My Account</h1>
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

                <h2>My Account</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($userEvents) ?></div>
                <div class="stat-label">Events Created</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($userPhotos) ?></div>
                <div class="stat-label">Photos Uploaded</div>
            </div>
        </div>

        <section class="card profile-section">
            <h3>Profile Settings</h3>
            
            <form method="post" class="form">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <label>
                        Username
                        <input type="text" name="username" value="<?= e($currentUser) ?>" required>
                    </label>
                </div>

                <div class="form-row">
                    <label>
                        Nickname
                        <input type="text" name="nickname" value="<?= e($_SESSION['user_nickname'] ?? '') ?>">
                        <small>A friendly display name (optional)</small>
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-split">
                        <label>
                            First Name
                            <input type="text" name="first_name" value="<?= e($_SESSION['user_first_name'] ?? '') ?>">
                        </label>
                        <label>
                            Last Name
                            <input type="text" name="last_name" value="<?= e($_SESSION['user_last_name'] ?? '') ?>">
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <label>
                        Email
                        <input type="email" name="email" value="<?= e($userEmail) ?>" required>
                    </label>
                </div>

                <div class="form-row">
                    <button type="submit" class="button">Update Profile</button>
                </div>
            </form>

            <hr style="margin: 2rem 0;">

            <h3>Change Password</h3>
            
            <form method="post" class="form">
                <input type="hidden" name="action" value="update_password">
                
                <div class="form-row">
                    <label>
                        New Password
                        <input type="password" name="new_password" required>
                    </label>
                </div>

                <div class="form-row">
                    <label>
                        Confirm New Password
                        <input type="password" name="confirm_password" required>
                    </label>
                </div>

                <div class="form-row">
                    <button type="submit" class="button">Update Password</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h3>My Events (<?= count($userEvents) ?>)</h3>
            
            <?php if (empty($userEvents)): ?>
                <p class="subtle">You haven't created any events yet. <a href="add-event.php">Create your first event</a>!</p>
            <?php else: ?>
                <div class="event-grid">
                    <?php foreach ($userEvents as $event): ?>
                        <div class="event-card">
                            <h4><?= e($event['title']) ?></h4>
                            
                            <?php if (!empty($event['image'])): ?>
                                <img src="uploads/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>">
                            <?php endif; ?>

                            <?php if (!empty($event['description'])): ?>
                                <p><?= e(substr($event['description'], 0, 100)) ?><?= strlen($event['description']) > 100 ? '...' : '' ?></p>
                            <?php endif; ?>

                            <div class="event-meta">
                                <div><strong>Date:</strong> <?= format_date($event['event_date']) ?></div>
                                <?php if (!empty($event['start_time'])): ?>
                                    <div><strong>Time:</strong> <?= format_time($event['start_time']) ?></div>
                                <?php endif; ?>
                                <?php
                                $eventPhotos = array_filter($allPhotos, fn($p) => $p['event_id'] == $event['id']);
                                $photoCount = count($eventPhotos);
                                if ($photoCount > 0):
                                ?>
                                    <div><strong>Photos:</strong> <?= $photoCount ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h3>My Photos (<?= count($userPhotos) ?>)</h3>
            
            <?php if (empty($userPhotos)): ?>
                <p class="subtle">You haven't uploaded any photos yet.</p>
            <?php else: ?>
                <div class="photo-grid">
                    <?php foreach ($userPhotos as $photo): ?>
                        <div class="photo-card">
                            <img src="uploads/<?= e($photo['filename']) ?>" alt="<?= e($photo['caption'] ?? 'Photo') ?>">
                            <div class="photo-content">
                                <?php if (!empty($photo['caption'])): ?>
                                    <p class="photo-caption"><?= e($photo['caption']) ?></p>
                                <?php endif; ?>
                                
                                <div class="photo-meta">
                                    Uploaded <?= e(date('M j, Y', strtotime($photo['uploaded_at']))) ?>
                                </div>

                                <div class="action-buttons">
                                    <form method="post" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this photo?');">
                                        <input type="hidden" name="action" value="delete_photo">
                                        <input type="hidden" name="photo_id" value="<?= e((string) $photo['id']) ?>">
                                        <button type="submit" class="button-small" style="background: #dc3545;">Delete</button>
                                    </form>
                                </div>

                                <?php if (!empty($photo['comments'])): ?>
                                    <div class="comments-section">
                                        <strong>Comments:</strong>
                                        <?php foreach ($photo['comments'] as $comment): ?>
                                            <div class="comment">
                                                <div class="comment-author"><?= e($comment['name']) ?></div>
                                                <div><?= e($comment['comment']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="comment-form">
                                    <form method="post">
                                        <input type="hidden" name="action" value="add_photo_comment">
                                        <input type="hidden" name="photo_id" value="<?= e((string) $photo['id']) ?>">
                                        <textarea name="comment" placeholder="Add a comment..." required></textarea>
                                        <button type="submit" class="button-small" style="margin-top: 0.5rem;">Add Comment</button>
                                    </form>
                                </div>
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
