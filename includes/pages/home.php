<?php
/** @var array $upcomingEvents */
/** @var array $venues */
/** @var array $events */
?>

<?php if (function_exists('is_guest') && is_guest()): ?>
<section class="card">
    <h2>Upcoming Public Events</h2>
    <div class="home-upcoming">
        <div class="home-upcoming-list">
            <?php if ($upcomingEvents): ?>
                <ul class="item-list">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <li>
                            <a href="?page=event&id=<?= e((string) $event['id']) ?>"><strong><?= e($event['title']) ?></strong></a>
                            <span>
                                <?= format_date($event['event_date']) ?>
                                <?php if (!empty($event['start_time'])): ?> at <?= format_time($event['start_time']) ?><?php endif; ?>
                            </span>
                            <?php if (!empty($event['venue_name'])): ?>
                                <span class="subtle">Venue: <?= e($event['venue_name']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No upcoming events yet.</p>
            <?php endif; ?>
        </div>
        <aside class="mini-calendar">
            <?php
            $month = isset($_GET['m']) ? max(1, min(12, (int) $_GET['m'])) : (int) date('n');
            $year  = isset($_GET['y']) ? max(1970, (int) $_GET['y']) : (int) date('Y');
            $firstDay = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
            $daysInMonth = (int) $firstDay->format('t');
            $startWeekday = (int) $firstDay->format('w'); // 0=Sun
            $monthLabel = $firstDay->format('F Y');

            $prevMonth = $month - 1; $prevYear = $year; if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
            $nextMonth = $month + 1; $nextYear = $year; if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

            $daysWithEvents = [];
            foreach ($events as $ev) {
                $d = $ev['event_date'] ?? null;
                if (!$d) continue;
                $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
                if (!$dt) continue;
                if ((int)$dt->format('n') === $month && (int)$dt->format('Y') === $year) {
                    $day = (int)$dt->format('j');
                    $daysWithEvents[$day] = ($daysWithEvents[$day] ?? 0) + 1;
                }
            }
            ?>
            <div class="mini-cal-header">
                <a href="?page=home&m=<?= $prevMonth ?>&y=<?= $prevYear ?>" aria-label="Previous month">&#8249;</a>
                <div class="mini-cal-title"><?= e($monthLabel) ?></div>
                <a href="?page=home&m=<?= $nextMonth ?>&y=<?= $nextYear ?>" aria-label="Next month">&#8250;</a>
            </div>
            <div class="mini-cal-grid mini-cal-weekdays">
                <div class="mini-cal-weekday">Sun</div>
                <div class="mini-cal-weekday">Mon</div>
                <div class="mini-cal-weekday">Tue</div>
                <div class="mini-cal-weekday">Wed</div>
                <div class="mini-cal-weekday">Thu</div>
                <div class="mini-cal-weekday">Fri</div>
                <div class="mini-cal-weekday">Sat</div>
            </div>
            <div class="mini-cal-grid">
                <?php for ($cell = 0; $cell < 35; $cell++): ?>
                    <?php
                        $day = $cell - $startWeekday + 1;
                        $isValid = $day >= 1 && $day <= $daysInMonth;
                        $hasEvent = $isValid && isset($daysWithEvents[$day]);
                    ?>
                    <div class="mini-cal-cell<?= $isValid ? '' : ' dim' ?><?= $hasEvent ? ' has-event' : '' ?>">
                        <?= $isValid ? $day : '' ?>
                        <?php if ($hasEvent): ?><span class="dot" title="<?= e((string) $daysWithEvents[$day]) ?> event(s)"></span><?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </aside>
    </div>
</section>
<?php else: ?>
<section class="dashboard">
    <div class="grid">
        <div class="card stat">
            <span class="label">Total Events</span>
            <span class="value"><?= count($events) ?></span>
        </div>
        <div class="card stat">
            <span class="label">Upcoming Events</span>
            <span class="value"><?= count($upcomingEvents) ?></span>
        </div>
        <div class="card stat">
            <span class="label">Venues</span>
            <span class="value"><?= count($venues) ?></span>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h2>Quick Actions</h2>
            <div class="actions">
                <a class="button" href="?page=events#create">Create Event</a>
                <a class="button" href="?page=venues#create">Add Venue</a>
                <a class="button" href="?page=photos#upload">Upload Photo</a>
                <a class="button" href="?page=calendar">View Calendar</a>
            </div>
        </div>
        <div class="card">
            <h2>Upcoming Events</h2>
            <?php if ($upcomingEvents): ?>
                <ul class="item-list">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <li>
                            <strong><?= e($event['title']) ?></strong>
                            <span><?= format_date($event['event_date']) ?> at <?= format_time($event['start_time']) ?></span>
                            <?php if (!empty($event['venue_name'])): ?>
                                <span class="subtle">Venue: <?= e($event['venue_name']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No upcoming events yet. <a href="?page=events#create">Create your first event</a>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>About Local Event Network Service</h2>
        <p>
            Local Event Network Service helps you organize community happenings. Create events, manage venues and
            deputize trusted friends so everything keeps moving even when you are offline. Share events with your
            network, add them to personal calendars and keep memories alive with photo uploads and comments.
        </p>
    </div>
</section>
<?php endif; ?>
