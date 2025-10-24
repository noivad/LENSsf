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
        $newName = trim($_POST['name'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        
        if ($newName !== '' && $newEmail !== '') {
            $_SESSION['current_user'] = $newName;
            $_SESSION['user_email'] = $newEmail;
            set_flash('Profile updated successfully!');
        } else {
            set_flash('Please provide both name and email.', 'error');
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
    <title>My Account - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-section {
            margin-bottom: 2rem;
        }
        .form-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .event-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            transition: box-shadow 0.2s ease;
        }
        .event-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .event-card h4 {
            margin: 0 0 0.5rem 0;
        }
        .event-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.75rem;
        }
        .event-meta {
            font-size: 0.9rem;
            color: var(--text-subtle);
        }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .photo-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        .photo-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .photo-content {
            padding: 1rem;
        }
        .photo-caption {
            margin: 0 0 0.5rem 0;
            font-weight: bold;
        }
        .photo-meta {
            font-size: 0.85rem;
            color: var(--text-subtle);
            margin-bottom: 0.75rem;
        }
        .comments-section {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }
        .comment {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .comment-author {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        .comment-form {
            margin-top: 0.5rem;
        }
        .comment-form textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            resize: vertical;
            min-height: 60px;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php"><?= e($siteName) ?></a></h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="event-list.php">Events</a>
                <a href="calendar-7x5.php">Calendar</a>
                <a href="venue-info.php">Venues</a>
                <a href="tags.php">Tags</a>
                <a href="account.php" class="active">Account</a>
                <a href="add-event.php">Add Event</a>
            </nav>
        </div>
    </header>

    <main class="container">
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
                        Name
                        <input type="text" name="name" value="<?= e($currentUser) ?>" required>
                    </label>
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
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>
</body>
</html>
