<?php
/** @var array $events */
/** @var array $venues */
?>
<section>
    <h2>Events</h2>

    <div class="card" id="create">
        <h3>Create New Event</h3>
        <form method="post" class="form">
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

            <button type="submit" class="button">Create Event</button>
        </form>
    </div>

    <div class="card">
        <h3>All Events</h3>
        <?php if ($events): ?>
            <?php foreach ($events as $event): ?>
                <div class="event-item" id="<?= e($event['id']) ?>">
                    <div class="event-header">
                        <h4><?= e($event['title']) ?></h4>
                        <span class="event-date"><?= format_date($event['event_date']) ?></span>
                    </div>

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

                    <?php if (!empty($event['calendar_entries'])): ?>
                        <div class="calendar-entries">
                            <strong>On Calendars:</strong>
                            <?php foreach ($event['calendar_entries'] as $entry): ?>
                                <span class="badge"><?= e($entry['name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['shared_with'])): ?>
                        <div class="shared-with">
                            <strong>Shared With:</strong>
                            <?php foreach ($event['shared_with'] as $person): ?>
                                <span class="badge"><?= e($person) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="event-actions">
                        <button class="button-small" onclick="showCalendarForm('<?= e($event['id']) ?>')">Add to Calendar</button>
                        <button class="button-small" onclick="showShareForm('<?= e($event['id']) ?>')">Share Event</button>
                    </div>

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
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No events yet. Create your first event above.</p>
        <?php endif; ?>
    </div>
</section>
