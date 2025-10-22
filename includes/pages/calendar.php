<?php
/** @var array $events */

$currentUser = $_SESSION['current_user'] ?? 'Demo User';

// Gather user's tags
$userTags = [];
foreach ($events as $ev) {
    if (($ev['owner'] ?? '') === $currentUser) {
        foreach (($ev['tags'] ?? []) as $tag) {
            $tagLower = strtolower(trim((string) $tag));
            if ($tagLower !== '') {
                $userTags[$tagLower] = true;
            }
        }
    }
}
$userTags = array_keys($userTags);

// Sample public tags
$sampleTags = ['Music', 'Professional Development', 'Night Clubs'];

// Filter events by user's tags if they have any, otherwise show all
$filteredEvents = $events;
if (!empty($userTags)) {
    $filteredEvents = array_values(array_filter($events, static function(array $e) use ($userTags): bool {
        $eventTags = array_map('strtolower', array_map('trim', ($e['tags'] ?? [])));
        foreach ($userTags as $userTag) {
            if (in_array($userTag, $eventTags, true)) {
                return true;
            }
        }
        return false;
    }));
}

$eventsByDate = [];
foreach ($filteredEvents as $event) {
    if (empty($event['event_date'])) {
        continue;
    }

    $eventsByDate[$event['event_date']][] = $event;
}

ksort($eventsByDate);
?>
<section>
    <h2>Community Calendar</h2>
    <p class="subtle">
        <?php if (function_exists('is_guest') && is_guest()): ?>
            Browse upcoming events.
        <?php else: ?>
            Browse upcoming events and see who has already added them to their calendar.
        <?php endif; ?>
    </p>

    <?php if (!empty($userTags)): ?>
        <div class="card">
            <h3>Your Tags</h3>
            <p class="subtle">Showing events filtered by tags you've used:</p>
            <div>
                <?php foreach ($userTags as $tag): ?>
                    <span class="badge">#<?= e($tag) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Popular Tags</h3>
            <p class="subtle">You haven't used any tags yet. Here are some sample public tags:</p>
            <div>
                <?php foreach ($sampleTags as $tag): ?>
                    <span class="badge">#<?= e(strtolower($tag)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($eventsByDate): ?>
        <div class="calendar-list">
            <?php foreach ($eventsByDate as $date => $items): ?>
                <div class="calendar-day">
                    <div class="calendar-date">
                        <span class="calendar-weekday"><?= date('l', strtotime($date)) ?></span>
                        <span class="calendar-fulldate"><?= format_date($date) ?></span>
                    </div>
                    <div class="calendar-events">
                        <?php foreach ($items as $event): ?>
                            <div class="calendar-event-card">
                                <h3><?= e($event['title']) ?></h3>

                                <?php if (!empty($event['image'])): ?>
                                    <div class="event-image">
                                        <img src="uploads/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>">
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($event['description'])): ?>
                                    <p><?= e($event['description']) ?></p>
                                <?php endif; ?>

                                <div class="calendar-event-meta">
                                    <?php if (!empty($event['start_time'])): ?>
                                        <span><strong>Starts:</strong> <?= format_time($event['start_time']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($event['venue_name'])): ?>
                                        <span><strong>Venue:</strong> <?= e($event['venue_name']) ?></span>
                                    <?php endif; ?>
                                    <span><strong>Owner:</strong> <?= e($event['owner']) ?></span>
                                </div>

                                <?php if (!empty($event['tags'])): ?>
                                    <div class="calendar-attendees">
                                        <strong>Tags:</strong>
                                        <?php foreach ($event['tags'] as $tag): ?>
                                            <span class="badge">#<?= e($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="calendar-event-actions">
                                    <a class="button-small" href="?page=events#<?= e($event['id']) ?>">View Event</a>
                                </div>

                                <?php if (!is_guest()): ?>
                                <div class="action-form">
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="action" value="add_tag">
                                        <input type="hidden" name="event_id" value="<?= e($event['id']) ?>">
                                        <input type="text" name="tag" placeholder="Add a tag (e.g., jazz)" required>
                                        <button type="submit" class="button-small">Add Tag</button>
                                    </form>
                                </div>
                                <?php endif; ?>

                                <?php if (!is_guest() && !empty($event['calendar_entries'])): ?>
                                    <div class="calendar-attendees">
                                        <strong>On calendars:</strong>
                                        <?php foreach ($event['calendar_entries'] as $entry): ?>
                                            <span class="badge"><?= e($entry['name']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (is_guest() && !empty($event['calendar_entries'])): ?>
                                    <div class="calendar-attendees">
                                        <strong>On calendars:</strong>
                                        <span class="badge"><?= count($event['calendar_entries']) ?> added</span>
                                    </div>
                                <?php else: ?>
                                    <div class="calendar-attendees empty">
                                        Be the first to add this event to your calendar!
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No events on the calendar yet. <a href="?page=events#create">Create an event</a> to get started!</p>
    <?php endif; ?>
</section>
