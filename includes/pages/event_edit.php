<?php
/** @var EventManager $eventManager */
/** @var VenueManager $venueManager */

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = $eventId > 0 ? $eventManager->findById($eventId) : null;

if (!$event) {
    echo '<section class="card"><h2>Event not found</h2><p>The event you are looking for does not exist.</p></section>';
    return;
}

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$isEditor = $currentUser === ($event['owner'] ?? '') || in_array($currentUser, $event['deputies'] ?? [], true);

if (!$isEditor && !is_admin($currentUser)) {
    echo '<section class="card"><h2>Access Denied</h2><p>You do not have permission to edit this event.</p></section>';
    return;
}

$venues = $venueManager->all();
?>
<section>
    <h2>Edit Event</h2>
    
    <div class="card">
        <form method="post" action="?page=event&id=<?= e((string) $event['id']) ?>" enctype="multipart/form-data" class="form">
            <input type="hidden" name="action" value="update_event">
            <input type="hidden" name="event_id" value="<?= e((string) $event['id']) ?>">
            
            <div class="form-row">
                <label for="title">Event Title *</label>
                <input type="text" id="title" name="title" value="<?= e($event['title']) ?>" required>
            </div>
            
            <div class="form-row">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= e($event['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <label for="event_date">Event Date *</label>
                <input type="date" id="event_date" name="event_date" value="<?= e($event['event_date'] ?? '') ?>" required>
            </div>
            
            <div class="form-row">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" value="<?= e($event['start_time'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <label for="venue_id">Venue</label>
                <select id="venue_id" name="venue_id">
                    <option value="">-- Select a venue --</option>
                    <?php foreach ($venues as $v): ?>
                        <option value="<?= e((string) $v['id']) ?>" <?= (int) ($event['venue_id'] ?? 0) === (int) $v['id'] ? 'selected' : '' ?>>
                            <?= e($v['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <label for="owner">Event Owner *</label>
                <input type="text" id="owner" name="owner" value="<?= e($event['owner'] ?? '') ?>" required>
            </div>
            
            <div class="form-row">
                <label for="deputies">Deputies</label>
                <input type="text" id="deputies" name="deputies" value="<?= e(implode(', ', $event['deputies'] ?? [])) ?>" placeholder="Comma-separated list of deputy names">
                <small>Deputies can help manage the event. Separate names with commas.</small>
            </div>
            
            <div class="form-row">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" value="<?= e(implode(', ', $event['tags'] ?? [])) ?>" placeholder="Comma-separated tags (e.g., music, outdoor, family)">
                <small>Add tags to help people discover your event. Separate with commas.</small>
            </div>
            
            <?php if (!empty($event['image'])): ?>
                <div class="form-row">
                    <label>Current Image</label>
                    <div>
                        <img src="uploads/<?= e($event['image']) ?>" alt="Event image" style="max-width: 300px; border-radius: 8px;">
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="form-row">
                <label for="image">Update Event Image</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                <small>Upload a new image to replace the current one. Leave empty to keep current image.</small>
            </div>
            
            <div class="actions">
                <button type="submit" class="button">Update Event</button>
                <a href="?page=event&id=<?= e((string) $event['id']) ?>" class="button">Cancel</a>
            </div>
        </form>
    </div>
    
    <div class="card">
        <h3>Danger Zone</h3>
        <form method="post" action="?page=events" onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
            <input type="hidden" name="action" value="delete_event">
            <input type="hidden" name="event_id" value="<?= e((string) $event['id']) ?>">
            <p class="subtle">Deleting an event is permanent and cannot be undone. All associated photos, comments, and calendar entries will remain but will no longer be linked to this event.</p>
            <button type="submit" class="button" style="background: var(--error-color);">Delete Event</button>
        </form>
    </div>
</section>
