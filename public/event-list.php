<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';

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

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);

$allEvents = $eventManager->all();

$filterTags = [];
if (isset($_GET['tags']) && !empty($_GET['tags'])) {
    $filterTags = array_map('trim', explode(',', (string) $_GET['tags']));
    $filterTags = array_map('strtolower', array_filter($filterTags));
}

$defaultMonth = (int) ($_GET['month'] ?? date('n'));
$defaultYear = (int) ($_GET['year'] ?? date('Y'));

$month = max(1, min(12, $defaultMonth));
$year = max(1970, $defaultYear);

$firstDayOfMonth = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = (int) $firstDayOfMonth->format('t');
$startingWeekdayIndex = (int) $firstDayOfMonth->format('w');
$monthLabel = $firstDayOfMonth->format('F Y');

$previousMonth = $month - 1;
$previousYear = $year;
if ($previousMonth < 1) {
    $previousMonth = 12;
    $previousYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$previousYearSameMonth = $year - 1;
$nextYearSameMonth = $year + 1;

$today = new DateTimeImmutable('today');

$filteredEvents = array_filter($allEvents, function ($event) use ($today, $filterTags) {
    if (empty($event['event_date'])) {
        return false;
    }

    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $event['event_date']);
    if (!$eventDate || $eventDate < $today) {
        return false;
    }

    if (!empty($filterTags)) {
        $eventTags = array_map('strtolower', $event['tags'] ?? []);
        $hasMatchingTag = false;
        foreach ($filterTags as $filterTag) {
            if (in_array($filterTag, $eventTags, true)) {
                $hasMatchingTag = true;
                break;
            }
        }
        if (!$hasMatchingTag) {
            return false;
        }
    }

    return true;
});

usort($filteredEvents, function ($a, $b) {
    $dateA = $a['event_date'] ?? '';
    $dateB = $b['event_date'] ?? '';
    if ($dateA === $dateB) {
        return 0;
    }
    return $dateA < $dateB ? -1 : 1;
});

$eventsByDay = [];
foreach ($filteredEvents as $event) {
    $eventDate = DateTime::createFromFormat('Y-m-d', $event['event_date']);
    if (!$eventDate || (int) $eventDate->format('n') !== $month || (int) $eventDate->format('Y') !== $year) {
        continue;
    }

    $dayIndex = (int) $eventDate->format('j');
    $eventsByDay[$dayIndex][] = $event;
}

$allTags = [];
foreach ($allEvents as $event) {
    foreach ($event['tags'] ?? [] as $tag) {
        $tag = strtolower(trim($tag));
        if ($tag !== '') {
            $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
        }
    }
}
arsort($allTags);

function buildDayClasses(array $eventsForDay): string
{
    if (empty($eventsForDay)) {
        return '';
    }

    return 'has-event';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Events</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/event-list.css">
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
                    <a href="event-list.php" class="nav-link active">
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
            <h1 class="header-title">LENSsf - Events</h1>
            <div class="user-controls">
                <div class="searchbar">
                    <input id="event-search" type="text" placeholder="Search events..." />
                </div>
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

                <div class="calendar-wrapper" style="margin-bottom: 2rem;">
                    <div class="calendar-header">
                        <div class="year-nav">
                            <a class="calendar-btn" href="?month=<?= $month ?>&year=<?= $previousYearSameMonth ?><?= !empty($filterTags) ? '&tags=' . urlencode(implode(',', $filterTags)) : '' ?>">« <?= $previousYearSameMonth ?></a>
                            <div class="year-display"><?= $year ?></div>
                            <a class="calendar-btn" href="?month=<?= $month ?>&year=<?= $nextYearSameMonth ?><?= !empty($filterTags) ? '&tags=' . urlencode(implode(',', $filterTags)) : '' ?>"><?= $nextYearSameMonth ?> »</a>
                        </div>
                        <div class="month-nav">
                            <a class="calendar-btn" href="?month=<?= $previousMonth ?>&year=<?= $previousYear ?><?= !empty($filterTags) ? '&tags=' . urlencode(implode(',', $filterTags)) : '' ?>">← Previous</a>
                            <div class="month-display"><?= htmlspecialchars($monthLabel, ENT_QUOTES) ?></div>
                            <a class="calendar-btn" href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?><?= !empty($filterTags) ? '&tags=' . urlencode(implode(',', $filterTags)) : '' ?>">Next →</a>
                        </div>
                    </div>

                    <div class="calendar-grid-container">
                        <div class="weekday-labels">
                            <div class="weekday-label">Sun</div>
                            <div class="weekday-label">Mon</div>
                            <div class="weekday-label">Tue</div>
                            <div class="weekday-label">Wed</div>
                            <div class="weekday-label">Thu</div>
                            <div class="weekday-label">Fri</div>
                            <div class="weekday-label">Sat</div>
                        </div>

                        <div class="calendar-grid">
                            <?php
                            $totalCells = 35;
                            for ($cell = 0; $cell < $totalCells; $cell++) {
                                $dayNumber = $cell - $startingWeekdayIndex + 1;
                                $isValidDay = $dayNumber >= 1 && $dayNumber <= $daysInMonth;
                                $dayEvents = $isValidDay && isset($eventsByDay[$dayNumber]) ? $eventsByDay[$dayNumber] : [];
                                $dayClasses = $isValidDay ? buildDayClasses($dayEvents) : '';
                                ?>
                                <div class="calendar-day<?= $dayClasses ? ' ' . $dayClasses : '' ?>">
                                    <?php if ($isValidDay): ?>
                                        <div class="day-number"><?= $dayNumber ?></div>
                                        <?php if (!empty($dayEvents)): ?>
                                            <div class="day-event-count"><?= count($dayEvents) ?> event<?= count($dayEvents) > 1 ? 's' : '' ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <section class="card">
                    <h2>Filter by Tag</h2>
                    
                    <div class="filter-tags" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem;">
                        <?php foreach (array_slice(array_keys($allTags), 0, 20) as $tag): ?>
                            <a href="?<?= !empty($filterTags) && in_array($tag, $filterTags) ? 'tags=' . urlencode(implode(',', array_diff($filterTags, [$tag]))) : 'tags=' . urlencode(empty($filterTags) ? $tag : implode(',', array_merge($filterTags, [$tag]))) ?>&month=<?= $month ?>&year=<?= $year ?>" 
                               class="tag-badge<?= in_array($tag, $filterTags) ? ' active' : '' ?>" 
                               style="padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; <?= in_array($tag, $filterTags) ? 'background: var(--primary-color); color: white;' : 'background: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color);' ?>">
                                #<?= e($tag) ?> (<?= $allTags[$tag] ?>)
                            </a>
                        <?php endforeach; ?>
                        <?php if (!empty($filterTags)): ?>
                            <a href="?month=<?= $month ?>&year=<?= $year ?>" class="button-small">Clear Filters</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($filterTags)): ?>
                        <div class="filter-bar" style="margin-bottom: 1rem;">
                            <div class="active-filters">
                                <strong>Active filters:</strong>
                                <?php foreach ($filterTags as $tag): ?>
                                    <span class="filter-badge" style="display: inline-block; background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; margin: 0.25rem;">
                                        #<?= e($tag) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h2>Upcoming Events</h2>

                    <?php if (empty($filteredEvents)): ?>
                        <div class="no-events">
                            <p><strong>No upcoming events found.</strong></p>
                            <?php if (!empty($filterTags)): ?>
                                <p>Try <a href="event-list.php?month=<?= $month ?>&year=<?= $year ?>">clearing your filters</a> or <a href="tags.php">browse all tags</a>.</p>
                            <?php else: ?>
                                <p>Check back soon or <a href="add-event.php">create an event</a>!</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="events-list">
                            <?php foreach ($filteredEvents as $event): ?>
                                <div class="event-card">
                                    <div class="event-header">
                                        <div style="flex: 1;">
                                            <h3 class="event-title"><?= e($event['title']) ?></h3>
                                            
                                            <?php if (!empty($event['tags'])): ?>
                                                <div class="event-tags">
                                                    <?php foreach ($event['tags'] as $tag): ?>
                                                        <a href="event-list.php?tags=<?= urlencode(strtolower($tag)) ?>&month=<?= $month ?>&year=<?= $year ?>" class="tag-badge">
                                                            #<?= e(strtolower($tag)) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($event['description'])): ?>
                                                <div class="event-description"><?= e($event['description']) ?></div>
                                            <?php endif; ?>

                                            <div class="event-meta">
                                                <span><strong>Date:</strong> <?= format_date($event['event_date']) ?></span>
                                                <?php if (!empty($event['start_time'])): ?>
                                                    <span><strong>Time:</strong> <?= format_time($event['start_time']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($event['venue_name'])): ?>
                                                    <span><strong>Venue:</strong> <?= e($event['venue_name']) ?></span>
                                                <?php endif; ?>
                                                <span><strong>By:</strong> <?= e($event['owner']) ?></span>
                                            </div>
                                        </div>
                                        <?php if (!empty($event['image'])): ?>
                                            <img src="uploads/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>" class="event-image">
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

    <script>
        window.__CURRENT_FILTER_TAGS__ = <?= json_encode($filterTags) ?>;
    </script>
    <script src="js/main.js"></script>
    <script src="js/event-list.js"></script>
    <script src="js/calendar-7x5.js"></script>
</body>
</html>
