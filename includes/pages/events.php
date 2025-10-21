<?php
/** @var array $events */
/** @var array $venues */
?>
<section>
    <h2>Events</h2>

    <div class="card">
        <h3>Search</h3>
        <form method="get" class="form">
            <input type="hidden" name="page" value="events">
            <div class="form-row">
                <input type="text" name="q" placeholder="Search by title, venue, description or #tag" value="<?= e((string)($_GET['q'] ?? '')) ?>">
            </div>
            <div class="form-row">
                <label><input type="checkbox" name="show_past" value="1" <?= !empty($_GET['show_past']) ? 'checked' : '' ?>> Include past events</label>
            </div>
            <button type="submit" class="button-small">Search</button>
        </form>
    </div>

    <?php
    $query = strtolower(trim((string)($_GET['q'] ?? '')));
    $includePast = !empty($_GET['show_past']);
    $today = new DateTimeImmutable('today');
    $events = array_values(array_filter($events, static function(array $e) use ($query, $includePast, $today): bool {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $e['event_date'] ?? '') ?: null;
        if (!$includePast && $date instanceof DateTimeImmutable && $date < $today) {
            return false;
        }
        if ($query === '') return true;
        $haystack = strtolower(trim(($e['title'] ?? '') . ' ' . ($e['description'] ?? '') . ' ' . ($e['venue_name'] ?? '') . ' ' . implode(' ', $e['tags'] ?? [])));
        return strpos($haystack, $query) !== false;
    }));
    ?>

    <?php if (!is_guest()): ?>
    <div class="card" id="create">
        <h3>Create New Event</h3>
        <p class="card-subtext">Prefer a dedicated workspace? <a href="add-event.php">Open the full add event page</a>.</p>
        <form method="post" enctype="multipart/form-data" class="form">
            <input type="hidden" name="action" value="create_event">

            <div class="form-row">
                <label>
                    Event Title *
                    <input type="text" name="title" required>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Description
                    <textarea name="description" rows="4"></textarea>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Event Image
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                </label>
            </div>

            <div class="form-row">
                <div class="form-split">
                    <label>
                        Event Date *
                        <input type="date" name="event_date" required>
                    </label>
                    <label>
                        Start Time
                        <input type="time" name="start_time">
                    </label>
                </div>
            </div>

            <div class="form-row">
                <label>
                    Venue
                    <select name="venue_id">
                        <option value="">— No venue —</option>
                        <?php foreach ($venues as $venue): ?>
                            <option value="<?= e($venue['id']) ?>"><?= e($venue['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Event Owner Name *
                    <input type="text" name="owner" required>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Deputies (comma-separated names)
                    <input type="text" name="deputies" placeholder="Alice, Bob, Charlie">
                </label>
            </div>

            <div class="form-row">
                <label>
                    Tags (comma-separated)
                    <input type="text" name="tags" placeholder="music, festival, family">
                </label>
            </div>

            <button type="submit" class="button">Create Event</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>All Events</h3>
        <?php if ($events): ?>
            <?php foreach ($events as $event): ?>
                <div class="event-item" id="<?= e($event['id']) ?>">
                    <div class="event-header">
                        <h4><?= e($event['title']) ?></h4>
                        <span class="event-date"><?= format_date($event['event_date']) ?></span>
                    </div>

                    <?php if (!empty($event['image'])): ?>
                        <div class="event-image">
                            <img src="uploads/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['description'])): ?>
                        <p><?= e($event['description']) ?></p>
                    <?php endif; ?>

                    <div class="event-details">
                        <?php if (!empty($event['start_time'])): ?>
                            <span><strong>Time:</strong> <?= format_time($event['start_time']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($event['venue_name'])): ?>
                            <span><strong>Venue:</strong> <?= e($event['venue_name']) ?></span>
                        <?php endif; ?>
                        <span><strong>Owner:</strong> <?= e($event['owner']) ?></span>
                        <?php if (!empty($event['deputies'])): ?>
                            <span><strong>Deputies:</strong> <?= e(implode(', ', $event['deputies'])) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!is_guest() && !empty($event['calendar_entries'])): ?>
                        <div class="calendar-entries">
                            <strong>On Calendars:</strong>
                            <?php foreach ($event['calendar_entries'] as $entry): ?>
                                <span class="badge"><?= e($entry['name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (is_guest() && !empty($event['calendar_entries'])): ?>
                        <div class="calendar-entries">
                            <strong>On Calendars:</strong>
                            <span class="badge"><?= count($event['calendar_entries']) ?> added</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!is_guest() && !empty($event['shared_with'])): ?>
                        <div class="shared-with">
                            <strong>Shared With:</strong>
                            <?php foreach ($event['shared_with'] as $person): ?>
                                <span class="badge"><?= e($person) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="event-actions">
                        <a class="button-small" href="?page=event&id=<?= e($event['id']) ?>">View Event</a>
                        <?php if (!is_guest()): ?>
                            <button class="button-small" onclick="showCalendarForm('<?= e($event['id']) ?>')">Add to Calendar</button>
                            <button class="button-small" onclick="showShareForm('<?= e($event['id']) ?>')">Share Event</button>
                        <?php endif; ?>
                    </div>

                    <?php if (!is_guest()): ?>
                    <div id="calendar-form-<?= e($event['id']) ?>" class="hidden action-form">
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="add_to_calendar">
                            <input type="hidden" name="event_id" value="<?= e($event['id']) ?>">
                            <input type="text" name="name" placeholder="Your name" required>
                            <button type="submit" class="button-small">Add</button>
                        </form>
                    </div>

                    <div id="share-form-<?= e($event['id']) ?>" class="hidden action-form">
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="share_event">
                            <input type="hidden" name="event_id" value="<?= e($event['id']) ?>">
                            <input type="text" name="people" placeholder="Names (comma-separated)" required>
                            <button type="submit" class="button-small">Share</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No events yet. Create your first event above.</p>
        <?php endif; ?>
    </div>
</section>
