<?php
/** @var array $upcomingEvents */
/** @var array $venues */
/** @var array $events */
?>
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
