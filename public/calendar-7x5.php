<?php
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';
require __DIR__ . '/../includes/managers/VenueManager.php';
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

$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);
$venueManager = new VenueManager($pdo, $uploadDir);
$photoManager = new PhotoManager($pdo, $uploadDir, 5242880);

$currentUser = $_SESSION['current_user'] ?? 'Demo User';

$defaultMonth = (int) ($_GET['month'] ?? date('n'));
$defaultYear = (int) ($_GET['year'] ?? date('Y'));

$month = max(1, min(12, $defaultMonth));
$year = max(1970, $defaultYear);

$firstDayOfMonth = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = (int) $firstDayOfMonth->format('t');
$startingWeekdayIndex = (int) $firstDayOfMonth->format('w'); // 0 (Sun) - 6 (Sat)
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

$allDbEvents = $eventManager->all();
$allPhotos = $photoManager->all();
$today = new DateTimeImmutable('today');

$events = array_map(function($event) use ($today, $currentUser) {
    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $event['event_date']);
    
    $status = 'upcoming';
    if ($eventDate && $eventDate < $today) {
        $status = 'past';
    } elseif ($eventDate && $eventDate->format('Y-m-d') === $today->format('Y-m-d')) {
        $status = 'happening';
    }
    
    return [
        'title' => $event['title'],
        'venue' => $event['venue_name'] ?? 'TBA',
        'date' => $event['event_date'],
        'start_time' => $event['start_time'] ?? '',
        'end_time' => '',
        'creator' => $event['owner'],
        'is_creator' => strcasecmp($event['owner'], $currentUser) === 0,
        'status' => $status,
        'description' => $event['description'] ?? '',
        'tags' => $event['tags'] ?? []
    ];
}, $allDbEvents);

$eventsByDay = [];
foreach ($events as $event) {
    $eventDate = DateTime::createFromFormat('Y-m-d', $event['date']);
    if (!$eventDate || (int) $eventDate->format('n') !== $month || (int) $eventDate->format('Y') !== $year) {
        continue;
    }

    $dayIndex = (int) $eventDate->format('j');
    $eventsByDay[$dayIndex][] = $event;
}

$eventImages = array_map(function($photo) {
    return [
        'src' => 'uploads/' . $photo['filename'],
        'caption' => $photo['caption'] ?? $photo['event_id'] ?? 'Event Photo'
    ];
}, array_slice($allPhotos, 0, 6));

function buildDayClasses(array $eventsForDay): string
{
    if (empty($eventsForDay)) {
        return '';
    }

    $classes = ['has-event'];
    $statuses = array_unique(array_column($eventsForDay, 'status'));

    if (in_array('happening', $statuses, true)) {
        $classes[] = 'happening-now';
    } elseif (!in_array('upcoming', $statuses, true)) {
        $classes[] = 'past-event';
    }

    return implode(' ', $classes);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Calendar</title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
</head>
<body data-theme="dark">
    <div class="app-container">
        <nav class="sidebar-nav">
            <div class="nav-logo">LENS</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="calendar-7x5.php" class="nav-link active">
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
            <h1 class="header-title">My Calendar</h1>
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
            <div class="calendar-wrapper">
                <div class="calendar-header">
                    <div class="year-nav">
                        <a class="calendar-btn" href="?month=<?php echo $month; ?>&amp;year=<?php echo $previousYearSameMonth; ?>">« <?php echo $previousYearSameMonth; ?></a>
                        <div class="year-display"><?php echo $year; ?></div>
                        <a class="calendar-btn" href="?month=<?php echo $month; ?>&amp;year=<?php echo $nextYearSameMonth; ?>"><?php echo $nextYearSameMonth; ?> »</a>
                    </div>
                    <div class="month-nav">
                        <a class="calendar-btn" href="?month=<?php echo $previousMonth; ?>&amp;year=<?php echo $previousYear; ?>">← Previous</a>
                        <div class="month-display"><?php echo htmlspecialchars($monthLabel, ENT_QUOTES); ?></div>
                        <a class="calendar-btn" href="?month=<?php echo $nextMonth; ?>&amp;year=<?php echo $nextYear; ?>">Next →</a>
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
                        $totalCells = 35; // 7 columns x 5 rows
                        for ($cell = 0; $cell < $totalCells; $cell++) {
                            $dayNumber = $cell - $startingWeekdayIndex + 1;
                            $isValidDay = $dayNumber >= 1 && $dayNumber <= $daysInMonth;
                            $dayEvents = $isValidDay && isset($eventsByDay[$dayNumber]) ? $eventsByDay[$dayNumber] : [];
                            $dayClasses = $isValidDay ? buildDayClasses($dayEvents) : '';
                            // Gather top tags for this day
                            $dayTags = [];
                            foreach ($dayEvents as $de) {
                                foreach (($de['tags'] ?? []) as $t) {
                                    $t = strtolower((string)$t);
                                    if ($t === '') continue;
                                    $dayTags[$t] = ($dayTags[$t] ?? 0) + 1;
                                }
                            }
                            arsort($dayTags);
                            $dayTopTags = array_slice(array_keys($dayTags), 0, 3);
                            ?>
                            <div class="calendar-day<?php echo $dayClasses ? ' ' . $dayClasses : ''; ?>">
                                <?php if ($isValidDay): ?>
                                    <div class="day-number"><?php echo $dayNumber; ?></div>
                                    <?php if (!empty($dayEvents)): ?>
                                        <div class="flag-row">
                                            <?php foreach ($dayEvents as $event): ?>
                                                <?php
                                                $flagClass = 'event-flag';
                                                if ($event['status'] === 'happening') {
                                                    $flagClass .= ' happening';
                                                } elseif ($event['status'] === 'past') {
                                                    $flagClass .= ' past';
                                                }
                                                ?>
                                                <span class="<?php echo $flagClass; ?>" title="Toggle details"></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (!empty($dayTopTags)): ?>
                                            <div class="day-tags">
                                                <?php foreach ($dayTopTags as $tg): ?>
                                                    <span class="tag-chip">#<?php echo htmlspecialchars($tg, ENT_QUOTES); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="event-popover">
                                            <?php foreach ($dayEvents as $idx => $event): ?>
                                                <?php
                                                $tagsStr = implode(',', array_map('strtolower', $event['tags'] ?? []));
                                                $descStr = (string)($event['description'] ?? '');
                                                ?>
                                                <div class="event-item" data-title="<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>" data-venue="<?php echo htmlspecialchars($event['venue'], ENT_QUOTES); ?>" data-description="<?php echo htmlspecialchars($descStr, ENT_QUOTES); ?>" data-tags="<?php echo htmlspecialchars($tagsStr, ENT_QUOTES); ?>">
                                                    <div class="event-media" style="margin-bottom: 0.6rem;">
                                                        <?php $imgUrl = 'https://picsum.photos/seed/' . rawurlencode($event['title']) . '/360/200'; ?>
                                                        <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?> image" style="width:100%; border-radius:12px; border:1px solid var(--border-color);">
                                                    </div>
                                                    <div class="event-title">
                                                        <a href="event-info.php?event=<?php echo urlencode($event['title']); ?>" style="color: inherit; text-decoration: none;">
                                                            <?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>
                                                        </a>
                                                    </div>
                                                    <div class="event-detail event-location" data-location="<?php echo htmlspecialchars($event['venue'] . ', San Francisco, CA', ENT_QUOTES); ?>">
                                                        <a href="venue-detail.php?venue=<?php echo urlencode($event['venue']); ?>" style="color: inherit; text-decoration: none;">
                                                            📍 <?php echo htmlspecialchars($event['venue'], ENT_QUOTES); ?>
                                                        </a>
                                                        <small style="opacity:.7">(hover to preview map)</small>
                                                    </div>
                                                    <div class="event-detail">🕐 <?php echo htmlspecialchars($event['start_time'] . ' - ' . $event['end_time'], ENT_QUOTES); ?></div>
                                                    <div class="event-detail">👤 Created by: <?php echo htmlspecialchars($event['creator'], ENT_QUOTES); ?></div>
                                                    <?php if (!empty($event['description'])): ?>
                                                        <div class="event-detail">🛈 <?php echo htmlspecialchars($event['description'], ENT_QUOTES); ?></div>
                                                    <?php endif; ?>
                                                    <div class="tag-chips">
                                                        <?php foreach (($event['tags'] ?? []) as $tg): ?>
                                                            <span class="tag-chip">#<?php echo htmlspecialchars(strtolower((string)$tg), ENT_QUOTES); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div id="map_<?php echo $dayNumber; ?>_<?php echo $idx; ?>" class="mini-map" style="display:none"></div>
                                                    <?php if ($event['status'] === 'happening'): ?>
                                                        <div class="event-detail emphasis">⚡ Happening Now</div>
                                                    <?php elseif ($event['status'] === 'past'): ?>
                                                        <div class="event-detail emphasis">🗓️ Past Event</div>
                                                    <?php else: ?>
                                                        <div class="event-detail emphasis">🚀 Upcoming Event</div>
                                                    <?php endif; ?>
                                                    <div class="event-actions">
                                                        <button class="event-action-btn" onclick="shareEvent('<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>')">📤 Share Event</button>
                                                        <?php if ($event['is_creator']): ?>
                                                            <button class="event-action-btn" onclick="editEvent('<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>')">✏️ Edit Event</button>
                                                            <button class="event-action-btn delete" onclick="confirmDelete('<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>')">🗑️ Delete Event</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>

                <?php
                // User's 3 main tags (in a real app, these would come from user preferences/history)
                $userMainTags = ['jazz', 'art', 'tech'];
                
                // Get upcoming events filtered by user's main tags
                $tagFilteredUpcoming = array_values(array_filter($events, static function(array $e) use ($userMainTags): bool {
                    if (!in_array($e['status'], ['upcoming', 'happening'], true)) {
                        return false;
                    }
                    $eventTags = array_map('strtolower', $e['tags'] ?? []);
                    foreach ($userMainTags as $mainTag) {
                        if (in_array(strtolower($mainTag), $eventTags, true)) {
                            return true;
                        }
                    }
                    return false;
                }));
                
                usort($tagFilteredUpcoming, static function(array $a, array $b): int {
                    return strcmp($a['date'], $b['date']);
                });
                ?>
                <?php if (!empty($tagFilteredUpcoming)): ?>
                    <div class="event-images" style="margin-top: 1rem;">
                        <h2 class="images-title">🎯 Upcoming Events Based on Your Top Tags (<?php echo implode(', ', array_map(function($t) { return '#' . $t; }, $userMainTags)); ?>)</h2>
                        <div class="upcoming-events-list">
                            <?php foreach ($tagFilteredUpcoming as $e): ?>
                                <?php $img = 'https://picsum.photos/seed/' . rawurlencode($e['title']) . '/400/300'; ?>
                                <div class="upcoming-event-card">
                                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($e['title'], ENT_QUOTES) ?>" class="event-card-img">
                                    <div class="event-card-content">
                                        <h3 class="event-card-title">
                                            <a href="event-info.php?event=<?php echo urlencode($e['title']); ?>" style="color: inherit; text-decoration: none;">
                                                <?= htmlspecialchars($e['title'], ENT_QUOTES) ?>
                                            </a>
                                        </h3>
                                        <div class="event-card-detail">📅 <?= htmlspecialchars(date('F j, Y', strtotime($e['date'])), ENT_QUOTES) ?></div>
                                        <div class="event-card-detail">🕐 <?= htmlspecialchars($e['start_time'] . ' - ' . $e['end_time'], ENT_QUOTES) ?></div>
                                        <div class="event-card-detail">
                                            <a href="venue-detail.php?venue=<?php echo urlencode($e['venue']); ?>" style="color: inherit; text-decoration: none;">
                                                📍 <?= htmlspecialchars($e['venue'], ENT_QUOTES) ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($e['description'])): ?>
                                            <p class="event-card-desc"><?= htmlspecialchars($e['description'], ENT_QUOTES) ?></p>
                                        <?php endif; ?>
                                        <div class="tag-chips" style="margin-top: 0.5rem;">
                                            <?php foreach (($e['tags'] ?? []) as $tg): ?>
                                                <span class="tag-chip">#<?php echo htmlspecialchars(strtolower((string)$tg), ENT_QUOTES); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <p>© <?php echo date('Y'); ?> LENS - Local Event Network Service | Built with ❤️ for the community</p>
        </footer>
    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h2 class="modal-title">⚠️ Confirm Delete</h2>
            <p class="modal-text">Are you sure you want to delete "<span id="eventToDelete"></span>"? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="modal-btn cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-btn confirm" onclick="deleteEvent()">Delete Event</button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
      window.__CAL_EVENTS__ = <?php echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="js/calendar-7x5.js"></script>
</body>
</html>
