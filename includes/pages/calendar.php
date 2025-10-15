<?php
/** @var array $events */

$eventsByDate = [];
foreach ($events as $event) {
    if (empty($event['event_date'])) {
        continue;
    }

    $eventsByDate[$event['event_date']][] = $event;
}

ksort($eventsByDate);
?>
<section>
    <h2>Community Calendar</h2>
    <p class="subtle">Browse upcoming events and see who has already added them to their calendar.</p>

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

                                <div class="calendar-event-actions">
                                    <a class="button-small" href="?page=events#<?= e($event['id']) ?>">View Event</a>
                                </div>

                                <?php if (!empty($event['calendar_entries'])): ?>
                                    <div class="calendar-attendees">
                                        <strong>On calendars:</strong>
                                        <?php foreach ($event['calendar_entries'] as $entry): ?>
                                            <span class="badge"><?= e($entry['name']) ?></span>
                                        <?php endforeach; ?>
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
