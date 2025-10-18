<?php
/** @var EventManager $eventManager */
/** @var VenueManager $venueManager */
/** @var PhotoManager $photoManager */

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = $eventId > 0 ? $eventManager->findById($eventId) : null;

if (!$event) {
    echo '<section class="card"><h2>Event not found</h2><p>The event you are looking for does not exist.</p></section>';
    return;
}

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$isEditor = $currentUser === ($event['owner'] ?? '') || in_array($currentUser, $event['deputies'] ?? [], true);

$allPhotos = $photoManager->all();
$eventPhotos = array_values(array_filter($allPhotos, static function (array $p) use ($eventId): bool {
    return (int) ($p['event_id'] ?? 0) === $eventId;
}));

?>
<section class="card event-single" data-event-id="<?= e((string) $event['id']) ?>" data-current-user="<?= e($currentUser) ?>" data-is-editor="<?= $isEditor ? '1' : '0' ?>">
    <div class="event-single-header">
        <div class="event-single-title">
            <h2><?= e($event['title']) ?></h2>
            <div class="event-single-sub">
                <div class="line"><?= !empty($event['start_time']) ? e(format_time($event['start_time'])) : 'Time: TBA' ?></div>
                <div class="line"><?= !empty($event['venue_name']) ? e($event['venue_name']) : 'Venue: TBA' ?></div>
            </div>
        </div>
        <div class="event-single-actions">
            <?php if ($isEditor): ?>
                <div class="action-row"><a href="?page=events#<?= e((string) $event['id']) ?>" class="button-small">Edit Event</a></div>
            <?php endif; ?>
            <div class="action-row"><button id="share-btn" class="button-small" type="button">Share</button></div>
            <?php if ($isEditor): ?>
                <div class="action-row"><button id="add-deputy-btn" class="button-small" type="button">Add Deputy</button></div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Compute popular tags across all events (top 3)
    $allEvents = $eventManager->all();
    $tagCounts = [];
    foreach ($allEvents as $ev) {
        foreach (($ev['tags'] ?? []) as $t) {
            $tLower = strtolower((string) $t);
            if ($tLower === '') continue;
            $tagCounts[$tLower] = ($tagCounts[$tLower] ?? 0) + 1;
        }
    }
    arsort($tagCounts);
    $popularTags = array_slice(array_keys($tagCounts), 0, 3);
    ?>

    <?php if (!empty($event['tags'])): ?>
        <div class="card-subsection">
            <h3>Tags</h3>
            <div>
                <?php foreach ($event['tags'] as $tag): ?>
                    <span class="badge">#<?= e(strtolower((string) $tag)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($popularTags)): ?>
        <div class="card-subsection">
            <h3>Popular tags</h3>
            <div>
                <?php foreach ($popularTags as $tag): ?>
                    <span class="badge">#<?= e($tag) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="event-single-media">
        <?php if (!empty($event['image'])): ?>
            <div class="event-image"><img src="uploads/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>"></div>
        <?php else: ?>
            <div class="image-placeholder <?php if(!$isEditor) echo 'readonly'; ?>">
                <?php if ($isEditor): ?>
                    <button class="placeholder-cta" id="add-event-image-btn" type="button">Add image</button>
                    <input type="file" id="event-image-input" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                <?php else: ?>
                    <span class="placeholder-label">No image</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="popover" id="share-popover">
        <div class="popover-header">
            <strong>Share event</strong>
            <button class="popover-close" type="button" aria-label="Close">×</button>
        </div>
        <div class="popover-body">
            <input type="text" id="share-input" class="popover-input" placeholder="Type a display name...">
            <div id="share-suggestions" class="suggestions"></div>
            <small class="subtle">Press Enter to add. Field clears for another entry.</small>
        </div>
    </div>

    <?php if ($isEditor): ?>
    <div class="popover" id="deputy-popover">
        <div class="popover-header">
            <strong>Add deputy</strong>
            <button class="popover-close" type="button" aria-label="Close">×</button>
        </div>
        <div class="popover-body">
            <input type="text" id="deputy-input" class="popover-input" placeholder="Type a display name...">
            <div id="deputy-suggestions" class="suggestions"></div>
            <small class="subtle">Press Enter to add. Field clears for another entry.</small>
        </div>
    </div>
    <?php endif; ?>

    <div class="shares card-subsection">
        <h3>Shared With</h3>
        <div id="shared-list" class="shared-list empty-state">
            <!-- Filled dynamically -->
        </div>
    </div>

    <div class="photos card-subsection">
        <div class="section-header">
            <h3>Event Photos</h3>
            <?php if ($isEditor): ?>
                <div class="image-placeholder compact">
                    <button class="placeholder-cta" id="add-photo-btn" type="button">Add photo</button>
                    <input type="file" id="photo-input" accept="image/jpeg,image/png,image/gif" hidden>
                </div>
            <?php endif; ?>
        </div>
        <div id="photo-grid" class="photo-grid">
            <?php foreach ($eventPhotos as $photo): ?>
                <div class="photo-card" data-photo-id="<?= e((string) $photo['id']) ?>">
                    <img src="uploads/<?= e($photo['filename']) ?>" alt="<?= e($photo['original_name']) ?>">
                    <div class="photo-event">Event: <?= e($event['title']) ?><?php if (!empty($event['venue_name'])): ?> @ <?= e($event['venue_name']) ?><?php endif; ?></div>
                    <?php if (!empty($photo['comments'])): ?>
                        <div class="comments">
                            <strong>Comments:</strong>
                            <?php foreach ($photo['comments'] as $comment): ?>
                                <div class="comment">
                                    <span class="comment-author"><?= e($comment['name']) ?>:</span>
                                    <span class="comment-text"> <?= e($comment['comment']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="photo-card-actions">
                        <button class="button-small add-photo-comment" data-photo-id="<?= e((string) $photo['id']) ?>">Add Comment</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="comments card-subsection" id="event-comments">
        <h3>Event Comments</h3>
        <div id="event-comments-list" class="event-comments-list empty-state"></div>
        <div class="comment-form">
            <input type="text" id="event-comment-name" placeholder="Your name">
            <textarea id="event-comment-text" rows="2" placeholder="Write a comment..."></textarea>
            <button id="post-event-comment" class="button-small" type="button">Post Comment</button>
        </div>
    </div>
</section>

<script src="js/event.js"></script>
