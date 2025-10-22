<?php
declare(strict_types=1);

$defaultMonth = 10;
$defaultYear = 2025;

$month = isset($_GET['month']) ? max(1, min(12, (int) $_GET['month'])) : $defaultMonth;
$year = isset($_GET['year']) ? max(1970, (int) $_GET['year']) : $defaultYear;

$firstDayOfMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
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

$events = [
    [
        'id' => 'evt-jazz-night',
        'title' => 'Jazz Night',
        'venue' => 'Blue Note Club',
        'venue_id' => 'ven-blue-note',
        'venue_url' => 'index.php?page=venue&id=ven-blue-note',
        'venue_location_query' => 'Blue Note Club, San Francisco, CA',
        'date' => sprintf('%04d-%02d-05', $year, $month),
        'start_time' => '19:00',
        'end_time' => '23:00',
        'creator' => 'Alice Johnson',
        'is_creator' => false,
        'status' => 'past',
        'visibility' => 'public',
        'description' => 'An evening of improvisation featuring local legends.',
        'tags' => ['jazz', 'music', 'live'],
        'recurrence' => [
            'frequency' => 'monthly',
            'pattern' => 'first-friday',
            'interval' => 1,
            'label' => 'Repeats on the first Friday of every month',
        ],
        'url' => 'index.php?page=event&id=evt-jazz-night',
        'request_url' => 'index.php?page=event&id=evt-jazz-night#request-invite',
        'add_to_calendar_url' => 'index.php?page=event&id=evt-jazz-night#add',
    ],
    [
        'id' => 'evt-art-exhibition',
        'title' => 'Art Exhibition Opening',
        'venue' => 'Modern Art Gallery',
        'venue_id' => 'ven-modern-art',
        'venue_url' => 'index.php?page=venue&id=ven-modern-art',
        'venue_location_query' => 'Modern Art Gallery, San Francisco, CA',
        'date' => sprintf('%04d-%02d-15', $year, $month),
        'start_time' => '18:00',
        'end_time' => '21:00',
        'creator' => 'You',
        'is_creator' => true,
        'status' => 'upcoming',
        'visibility' => 'public',
        'description' => 'Celebrate the launch of the "Lightscapes" collection with the artists.',
        'tags' => ['art', 'gallery', 'opening'],
        'recurrence' => [
            'frequency' => 'monthly',
            'pattern' => 'nth-weekday',
            'interval' => 1,
            'nth' => [3],
            'weekday' => 2, // Wednesday
            'label' => 'Every third Wednesday',
        ],
        'url' => 'index.php?page=event&id=evt-art-exhibition',
        'request_url' => 'index.php?page=event&id=evt-art-exhibition#request-invite',
        'add_to_calendar_url' => 'index.php?page=event&id=evt-art-exhibition#add',
    ],
    [
        'id' => 'evt-pizza-party',
        'title' => 'Pizza Party',
        'venue' => "Tony's Pizzeria",
        'venue_id' => 'ven-tonys',
        'venue_url' => 'index.php?page=venue&id=ven-tonys',
        'venue_location_query' => "Tony's Pizzeria, San Francisco, CA",
        'date' => sprintf('%04d-%02d-15', $year, $month),
        'start_time' => '19:30',
        'end_time' => '22:00',
        'creator' => 'Bob Smith',
        'is_creator' => false,
        'status' => 'upcoming',
        'visibility' => 'invitation-only',
        'description' => 'Community-organized meetup to celebrate the fall menu launch.',
        'tags' => ['food', 'pizza', 'community'],
        'recurrence' => [
            'frequency' => 'monthly',
            'pattern' => 'nth-weekday',
            'interval' => 1,
            'nth' => [2],
            'weekday' => 1, // Tuesday
            'label' => 'Second Tuesday of every month',
        ],
        'url' => 'index.php?page=event&id=evt-pizza-party',
        'request_url' => 'index.php?page=event&id=evt-pizza-party#request-invite',
        'add_to_calendar_url' => 'index.php?page=event&id=evt-pizza-party#add',
    ],
    [
        'id' => 'evt-tech-conference',
        'title' => 'Tech Conference',
        'venue' => 'Central Convention Center',
        'venue_id' => 'ven-central-convention',
        'venue_url' => 'index.php?page=venue&id=ven-central-convention',
        'venue_location_query' => 'Central Convention Center, San Francisco, CA',
        'date' => sprintf('%04d-%02d-17', $year, $month),
        'start_time' => '09:00',
        'end_time' => '18:00',
        'creator' => 'Tech Corp',
        'is_creator' => false,
        'status' => 'happening',
        'visibility' => 'public',
        'description' => 'Keynotes on emerging AI systems plus hands-on futuristic demos.',
        'tags' => ['tech', 'conference', 'ai'],
        'recurrence' => [
            'frequency' => 'yearly',
            'pattern' => 'same-day',
            'label' => 'Annual conference (same weekend each year)',
        ],
        'url' => 'index.php?page=event&id=evt-tech-conference',
        'request_url' => 'index.php?page=event&id=evt-tech-conference#request-invite',
        'add_to_calendar_url' => 'index.php?page=event&id=evt-tech-conference#add',
    ],
    [
        'id' => 'evt-theater-performance',
        'title' => 'Theater Performance',
        'venue' => 'City Theater',
        'venue_id' => 'ven-city-theater',
        'venue_url' => 'index.php?page=venue&id=ven-city-theater',
        'venue_location_query' => 'City Theater, San Francisco, CA',
        'date' => sprintf('%04d-%02d-20', $year, $month),
        'start_time' => '20:00',
        'end_time' => '22:30',
        'creator' => 'You',
        'is_creator' => true,
        'status' => 'upcoming',
        'visibility' => 'private',
        'description' => 'Premiere of the sci-fi stage play "Echoes of Tomorrow".',
        'tags' => ['theater', 'performance', 'premiere'],
        'recurrence' => [
            'frequency' => 'custom',
            'pattern' => 'limited-run',
            'label' => 'Limited run (three weekends only)',
        ],
        'url' => 'index.php?page=event&id=evt-theater-performance',
        'request_url' => 'index.php?page=event&id=evt-theater-performance#request-invite',
        'add_to_calendar_url' => 'index.php?page=event&id=evt-theater-performance#add',
    ],
    [
        'id' => 'evt-marathon',
        'title' => 'Marathon Event',
        'venue' => 'Eon City Park',
        'venue_id' => 'ven-eon-park',
        'venue_url' => 'index.php?page=venue&id=ven-eon-park',
        'venue_location_query' => 'Eon City Park, San Francisco, CA',
        'date' => sprintf('%04d-%02d-25', $year, $month),
        'start_time' => '06:00',
        'end_time' => '12:00',
        'creator' => 'Sports Club',
        'is_creator' => false,
        'status' => 'upcoming',
        'visibility' => 'public',
        'description' => 'City-wide marathon following the Skyline Nebula route.',
        'tags' => ['sports', 'marathon', 'outdoor'],
        'recurrence' => [
            'frequency' => 'yearly',
            'pattern' => 'nth-weekday',
            'interval' => 1,
            'nth' => [4],
            'weekday' => 0, // Sunday
            'label' => 'Fourth Sunday of October every year',
        ],
        'url' => 'index.php?page=event&id=evt-marathon',
        'request_url' => 'index.php?page=event&id=evt-marathon#request-invite',
        'add_to_calendar_url' => 'index.php?page=event&id=evt-marathon#add',
    ],
    [
        'id' => 'evt-halloween',
        'title' => 'Halloween Party',
        'venue' => 'Community Center',
        'venue_id' => 'ven-community-center',
        'venue_url' => 'index.php?page=venue&id=ven-community-center',
        'venue_location_query' => 'Community Center, San Francisco, CA',
        'date' => sprintf('%04d-%02d-28', $year, $month),
        'start_time' => '19:00',
        'end_time' => '00:00',
        'creator' => 'You',
        'is_creator' => true,
        'status' => 'upcoming',
        'visibility' => 'public',
        'description' => 'Costumes, synthwave DJs, and an augmented reality haunted maze.',
        'tags' => ['halloween', 'party', 'costumes'],
        'recurrence' => [
            'frequency' => 'yearly',
            'pattern' => 'specific-date',
            'label' => 'Every October 28th',
        ],
        'url' => 'index.php?page=event&id=evt-halloween',
        'request_url' => 'index.php?page=event&id=evt-halloween#request-invite',
        'add_to_calendar_url' => 'index.php?page=event&id=evt-halloween#add',
    ],
];

$eventsByDay = [];
$eventsByDate = [];
$eventsForMonth = [];

foreach ($events as $event) {
    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $event['date']);
    if (!$eventDate || (int) $eventDate->format('n') !== $month || (int) $eventDate->format('Y') !== $year) {
        continue;
    }

    $dayIndex = (int) $eventDate->format('j');
    $eventsByDay[$dayIndex][] = $event;

    $dateKey = $eventDate->format('Y-m-d');
    $eventsByDate[$dateKey][] = $event;
    $eventsForMonth[$event['id']] = $event;
}

ksort($eventsByDay);
ksort($eventsByDate);

$eventImages = [
    ['src' => 'https://picsum.photos/400/400?random=10', 'caption' => 'Jazz Night @ Blue Note'],
    ['src' => 'https://picsum.photos/400/400?random=11', 'caption' => 'Summer Festival 2024'],
    ['src' => 'https://picsum.photos/400/400?random=12', 'caption' => 'Tech Meetup'],
    ['src' => 'https://picsum.photos/400/400?random=13', 'caption' => 'Art Workshop'],
    ['src' => 'https://picsum.photos/400/400?random=14', 'caption' => 'Food Festival'],
    ['src' => 'https://picsum.photos/400/400?random=15', 'caption' => 'Concert Night'],
];

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

function formatTimeRange(?string $start, ?string $end): string
{
    $parts = [];
    if ($start) {
        $parts[] = formatSingleTime($start);
    }
    if ($end) {
        $parts[] = formatSingleTime($end);
    }
    return implode(' – ', $parts);
}

function formatSingleTime(string $time): string
{
    $dt = DateTimeImmutable::createFromFormat('H:i', $time);
    return $dt ? $dt->format('g:i A') : $time;
}

function formatVisibilityLabel(string $visibility): string
{
    return match ($visibility) {
        'private' => 'Private',
        'invitation-only' => 'Invitation only',
        default => 'Public',
    };
}

function formatStatusLabel(string $status): string
{
    return match ($status) {
        'happening' => 'Happening Now',
        'past' => 'Past Event',
        default => 'Upcoming Event',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENS Calendar - Dynamic PHP View</title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
</head>
<body data-theme="dark" class="calendar-personal">
    <div class="app-container">
        <nav class="sidebar-nav">
            <div class="nav-logo">LENS</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="calendar-7x5.php" class="nav-link active">
                        <span class="nav-icon">📅</span>
                        <span>Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="venue-info.php" class="nav-link">
                        <span class="nav-icon">📍</span>
                        <span>Venues</span>
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
                        <div class="dropdown-item" onclick="alert('Contact Info panel would open here.')">
                            📧 Contact Info
                        </div>
                        <div class="dropdown-item" onclick="alert('Notifications center would open here.')">
                            🔔 Notifications
                        </div>
                        <div class="dropdown-item" onclick="alert('Account settings would open here.')">
                            ⚙️ Account Info
                        </div>
                        <div class="dropdown-item" onclick="alert('Showing past events list.')">
                            📜 My Past Events
                        </div>
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
                            $dateIso = $isValidDay ? sprintf('%04d-%02d-%02d', $year, $month, $dayNumber) : '';
                            $dayEvents = $isValidDay && isset($eventsByDay[$dayNumber]) ? $eventsByDay[$dayNumber] : [];
                            $dayClasses = $isValidDay ? buildDayClasses($dayEvents) : '';
                            $eventCount = count($dayEvents);
                            $countLabel = $eventCount > 1 ? '+' . ($eventCount - 1) : '';
                        ?>
                            <div class="calendar-day<?php echo $dayClasses ? ' ' . $dayClasses : ''; ?>"<?php if ($isValidDay): ?> data-date="<?php echo htmlspecialchars($dateIso, ENT_QUOTES); ?>" data-event-count="<?php echo $eventCount; ?>"<?php endif; ?>>
                                <?php if ($isValidDay): ?>
                                    <div class="day-number"><?php echo $dayNumber; ?></div>
                                    <?php if (!empty($dayEvents)): ?>
                                        <div class="flag-row" data-event-count="<?php echo $eventCount; ?>" data-count-label="<?php echo htmlspecialchars($countLabel, ENT_QUOTES); ?>">
                                            <?php foreach ($dayEvents as $event): ?>
                                                <?php
                                                $flagClass = 'event-flag';
                                                if ($event['status'] === 'happening') {
                                                    $flagClass .= ' happening';
                                                } elseif ($event['status'] === 'past') {
                                                    $flagClass .= ' past';
                                                }
                                                if (!empty($event['is_creator'])) {
                                                    $flagClass .= ' mine';
                                                }
                                                ?>
                                                <span class="<?php echo $flagClass; ?>" data-event-id="<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>" title="Toggle details"></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="event-popover">
                                            <?php foreach ($dayEvents as $idx => $event): ?>
                                                <?php
                                                $tagsStr = implode(',', array_map('strtolower', $event['tags'] ?? []));
                                                $descStr = (string) ($event['description'] ?? '');
                                                $timeRange = formatTimeRange($event['start_time'] ?? null, $event['end_time'] ?? null);
                                                $statusLabel = formatStatusLabel($event['status']);
                                                $visibilityLabel = formatVisibilityLabel($event['visibility']);
                                                $imageUrl = 'https://picsum.photos/seed/' . rawurlencode($event['id']) . '/360/200';
                                                ?>
                                                <article class="event-item" data-event-id="<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>" data-event-url="<?php echo htmlspecialchars($event['url'], ENT_QUOTES); ?>" data-venue-url="<?php echo htmlspecialchars($event['venue_url'], ENT_QUOTES); ?>" data-description="<?php echo htmlspecialchars($descStr, ENT_QUOTES); ?>" data-tags="<?php echo htmlspecialchars($tagsStr, ENT_QUOTES); ?>" data-event-date="<?php echo htmlspecialchars($event['date'], ENT_QUOTES); ?>">
                                                    <div class="event-media" style="margin-bottom: 0.6rem;">
                                                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?> image" style="width:100%; border-radius:12px; border:1px solid var(--border-color);">
                                                    </div>
                                                    <header class="event-popover-header">
                                                        <a class="event-title" href="<?php echo htmlspecialchars($event['url'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?></a>
                                                        <span class="visibility-badge visibility-<?php echo htmlspecialchars($event['visibility'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($visibilityLabel, ENT_QUOTES); ?></span>
                                                    </header>
                                                    <div class="event-detail event-location">
                                                        <a href="<?php echo htmlspecialchars($event['venue_url'], ENT_QUOTES); ?>" data-location="<?php echo htmlspecialchars($event['venue_location_query'], ENT_QUOTES); ?>">📍 <?php echo htmlspecialchars($event['venue'], ENT_QUOTES); ?> <small style="opacity:.7">(hover for map)</small></a>
                                                    </div>
                                                    <?php if ($timeRange !== ''): ?>
                                                        <div class="event-detail">🕐 <?php echo htmlspecialchars($timeRange, ENT_QUOTES); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($event['recurrence']['label'])): ?>
                                                        <div class="event-detail">🔁 <?php echo htmlspecialchars($event['recurrence']['label'], ENT_QUOTES); ?></div>
                                                    <?php endif; ?>
                                                    <div class="event-detail">👤 Created by: <?php echo htmlspecialchars($event['creator'], ENT_QUOTES); ?><?php if (!empty($event['is_creator'])): ?> • You<?php endif; ?></div>
                                                    <?php if ($descStr !== ''): ?>
                                                        <div class="event-detail">🛈 <?php echo htmlspecialchars($descStr, ENT_QUOTES); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($event['tags'])): ?>
                                                        <div class="tag-chips">
                                                            <?php foreach (($event['tags'] ?? []) as $tg): ?>
                                                                <span class="tag-chip">#<?php echo htmlspecialchars(strtolower((string) $tg), ENT_QUOTES); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="mini-map" style="display:none"></div>
                                                    <div class="event-status"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES); ?></div>
                                                    <div class="event-actions">
                                                        <button class="event-action-btn" type="button" onclick="shareEvent('<?php echo htmlspecialchars(addslashes($event['title']), ENT_QUOTES); ?>')">📤 Share Event</button>
                                                        <?php if (!empty($event['is_creator'])): ?>
                                                            <a class="event-action-btn" href="<?php echo htmlspecialchars($event['url'], ENT_QUOTES); ?>#edit">✏️ Edit Event</a>
                                                        <?php endif; ?>
                                                        <?php if ($event['visibility'] === 'invitation-only'): ?>
                                                            <a class="event-action-btn" href="<?php echo htmlspecialchars($event['request_url'], ENT_QUOTES); ?>">Request invite</a>
                                                        <?php else: ?>
                                                            <button class="event-action-btn calendar-personal-only event-add-trigger" type="button" data-event-id="<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>" data-event-title="<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>">➕ Add to my calendar</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </article>
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

                <section class="calendar-event-feed" id="event-list">
                    <header class="calendar-event-feed__header">
                        <h2>Events for <?php echo htmlspecialchars($monthLabel, ENT_QUOTES); ?></h2>
                        <p class="subtle">Click on any day in the grid to jump directly to these details. RSVP responses update both this list and the calendar pips.</p>
                    </header>
                    <?php if ($eventsByDate): ?>
                        <?php foreach ($eventsByDate as $day => $items): ?>
                            <?php
                            $dayDate = DateTimeImmutable::createFromFormat('Y-m-d', $day);
                            $weekdayLabel = $dayDate ? $dayDate->format('l') : '';
                            $displayDate = $dayDate ? $dayDate->format('F j, Y') : $day;
                            ?>
                            <div class="event-day-group" data-event-date="<?php echo htmlspecialchars($day, ENT_QUOTES); ?>">
                                <div class="event-day-heading">
                                    <span class="event-day-weekday"><?php echo htmlspecialchars($weekdayLabel, ENT_QUOTES); ?></span>
                                    <span class="event-day-date"><?php echo htmlspecialchars($displayDate, ENT_QUOTES); ?></span>
                                </div>
                                <?php foreach ($items as $event): ?>
                                    <?php
                                    $timeRange = formatTimeRange($event['start_time'] ?? null, $event['end_time'] ?? null);
                                    $statusLabel = formatStatusLabel($event['status']);
                                    $visibilityLabel = formatVisibilityLabel($event['visibility']);
                                    ?>
                                    <article class="event-card" id="event-card-<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>" data-event-id="<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>" data-event-date="<?php echo htmlspecialchars($event['date'], ENT_QUOTES); ?>">
                                        <header class="event-card__header">
                                            <h3><a href="<?php echo htmlspecialchars($event['url'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?></a></h3>
                                            <span class="visibility-badge visibility-<?php echo htmlspecialchars($event['visibility'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($visibilityLabel, ENT_QUOTES); ?></span>
                                        </header>
                                        <ul class="event-card__meta">
                                            <?php if ($timeRange !== ''): ?>
                                                <li>🕐 <?php echo htmlspecialchars($timeRange, ENT_QUOTES); ?></li>
                                            <?php endif; ?>
                                            <li>📍 <a href="<?php echo htmlspecialchars($event['venue_url'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($event['venue'], ENT_QUOTES); ?></a></li>
                                            <?php if (!empty($event['recurrence']['label'])): ?>
                                                <li>🔁 <?php echo htmlspecialchars($event['recurrence']['label'], ENT_QUOTES); ?></li>
                                            <?php endif; ?>
                                            <li>👤 Hosted by <?php echo htmlspecialchars($event['creator'], ENT_QUOTES); ?><?php if (!empty($event['is_creator'])): ?> (you manage this)<?php endif; ?></li>
                                            <li class="status"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES); ?></li>
                                        </ul>
                                        <?php if (!empty($event['description'])): ?>
                                            <p class="event-card__description"><?php echo htmlspecialchars($event['description'], ENT_QUOTES); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($event['tags'])): ?>
                                            <div class="tag-chips">
                                                <?php foreach ($event['tags'] as $tag): ?>
                                                    <span class="tag-chip">#<?php echo htmlspecialchars(strtolower($tag), ENT_QUOTES); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="event-card__actions">
                                            <a class="event-action-btn" href="<?php echo htmlspecialchars($event['url'], ENT_QUOTES); ?>">View details</a>
                                            <?php if ($event['visibility'] === 'invitation-only'): ?>
                                                <a class="event-action-btn" href="<?php echo htmlspecialchars($event['request_url'], ENT_QUOTES); ?>">Request invite</a>
                                            <?php endif; ?>
                                            <button class="event-action-btn calendar-personal-only event-add-trigger" type="button" data-event-id="<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>" data-event-title="<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>">Add to my calendar</button>
                                        </div>
                                        <div class="event-rsvp calendar-personal-only" data-event-id="<?php echo htmlspecialchars($event['id'], ENT_QUOTES); ?>" role="group" aria-label="RSVP controls for <?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>">
                                            <span class="event-rsvp__label">RSVP:</span>
                                            <button class="rsvp-choice" type="button" data-rsvp="yes">Yes</button>
                                            <button class="rsvp-choice" type="button" data-rsvp="interested">Interested</button>
                                            <button class="rsvp-choice" type="button" data-rsvp="no">No</button>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="subtle">No events scheduled for this month yet.</p>
                    <?php endif; ?>
                </section>

                <section class="calendar-notifications" aria-live="polite">
                    <h3>Latest Updates</h3>
                    <div id="calendar-notifications"></div>
                </section>

                <div class="event-images">
                    <h2 class="images-title">🌟 Photos from Your Past Events</h2>
                    <div class="images-grid">
                        <?php foreach ($eventImages as $image): ?>
                            <div class="image-card">
                                <img src="<?php echo htmlspecialchars($image['src'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($image['caption'], ENT_QUOTES); ?>">
                                <div class="image-overlay">
                                    <div class="image-caption"><?php echo htmlspecialchars($image['caption'], ENT_QUOTES); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php
                $userUpcoming = array_values(array_filter($events, static function(array $e): bool {
                    return !empty($e['is_creator']) && in_array($e['status'], ['upcoming', 'happening'], true);
                }));
                usort($userUpcoming, static function(array $a, array $b): int {
                    return strcmp($a['date'], $b['date']);
                });
                ?>
                <?php if (!empty($userUpcoming)): ?>
                    <div class="event-images" style="margin-top: 1rem;">
                        <h2 class="images-title">📅 Your Upcoming Events</h2>
                        <div class="images-grid">
                            <?php foreach ($userUpcoming as $e): ?>
                                <?php $img = 'https://picsum.photos/seed/' . rawurlencode($e['id']) . '/400/400'; ?>
                                <div class="image-card" style="aspect-ratio: auto;">
                                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($e['title'], ENT_QUOTES); ?>">
                                    <div class="image-overlay">
                                        <div class="image-caption">
                                            <?php echo htmlspecialchars($e['title'], ENT_QUOTES); ?> — <?php echo htmlspecialchars(date('M j', strtotime($e['date'])), ENT_QUOTES); ?>
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
      window.__CAL_EVENTS__ = <?php echo json_encode(array_values($eventsForMonth), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="js/calendar-7x5.js"></script>
</body>
</html>
