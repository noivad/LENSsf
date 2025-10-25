<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';

autoloadSession();
ensureSiteName();

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$currentUserId = $_SESSION['user_id'] ?? 1;
$tab = $_GET['tab'] ?? 'shared_with_me';
$siteName = $_SESSION['site_name'] ?? 'Local Event Network Service';
$theme = $_SESSION['theme'] ?? 'dark';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Items - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/shared.css">
</head>
<body class="theme-<?= e($theme) ?>">
    <header>
        <div class="container">
            <h1><a href="index.php"><?= e($siteName) ?></a></h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="events-list-add-info.php">Events</a>
                <a href="calendar-7x5.php">Calendar</a>
                <a href="venue-info.php">Venues</a>
                <a href="shared.php" class="active">Shared</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <section>
            <h2>Shared</h2>

            <div class="card">
                <div class="actions shared-tabs">
                    <a href="?tab=shared_with_me" class="<?= $tab === 'shared_with_me' ? 'active' : '' ?>">Shared with me</a>
                    <a href="?tab=my_shares" class="<?= $tab === 'my_shares' ? 'active' : '' ?>">My shares</a>
                </div>

                <?php if ($tab === 'shared_with_me'): ?>
                    <div id="shared-with-me-tab" class="tab-content">
                        <h3>Events Shared With Me</h3>
                        <div id="shared-events-list" class="item-list">
                            <p class="loading">Loading shared events...</p>
                        </div>

                        <h3 class="section-heading">Venues Shared With Me</h3>
                        <div id="shared-venues-list" class="item-list">
                            <p class="loading">Loading shared venues...</p>
                        </div>
                    </div>
                <?php elseif ($tab === 'my_shares'): ?>
                    <div id="my-shares-tab" class="tab-content">
                        <h3>Events I've Shared</h3>
                        <div id="my-event-shares-list" class="item-list">
                            <p class="loading">Loading your event shares...</p>
                        </div>

                        <h3 class="section-heading">Venues I've Shared</h3>
                        <div id="my-venue-shares-list" class="item-list">
                            <p class="loading">Loading your venue shares...</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>

    <script>
    window.__CURRENT_USER_ID__ = <?= json_encode($currentUserId) ?>;
    window.__CURRENT_TAB__ = <?= json_encode($tab) ?>;
    </script>
    <script src="js/shared-page.js"></script>
</body>
</html>
