<?php
/** @var VenueManager $venueManager */

$venueId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$venue = $venueId > 0 ? $venueManager->findById($venueId) : null;

if (!$venue) {
    echo '<section class="card"><h2>Venue not found</h2><p>The venue you are looking for does not exist.</p></section>';
    return;
}

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$isEditor = strcasecmp($currentUser, (string) ($venue['owner'] ?? '')) === 0
    || in_array($currentUser, $venue['deputies'] ?? [], true);
?>
<section class="card venue-single" data-venue-id="<?= e((string) $venue['id']) ?>" data-current-user="<?= e($currentUser) ?>" data-is-editor="<?= $isEditor ? '1' : '0' ?>">
    <div class="event-single-header">
        <div class="event-single-title">
            <h2><?= e($venue['name']) ?></h2>
            <div class="event-single-sub">
                <div class="line">Owner: <?= e((string) ($venue['owner'] ?? '')) ?></div>
                <?php if (!empty($venue['deputies'])): ?>
                    <div class="line">Deputies: <?= e(implode(', ', $venue['deputies'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="event-single-actions">
            <div class="action-row"><a href="?page=venues" class="button-small">Back to Venues</a></div>
        </div>
    </div>

    <div class="event-single-media">
        <?php if (!empty($venue['image'])): ?>
            <div class="event-image"><img src="uploads/<?= e($venue['image']) ?>" alt="<?= e($venue['name']) ?>"></div>
            <?php if ($isEditor): ?>
                <form method="post" class="inline-form" onsubmit="return confirm('Remove this venue image?');" style="margin-top:0.5rem">
                    <input type="hidden" name="action" value="delete_venue_image">
                    <input type="hidden" name="venue_id" value="<?= e((string) $venue['id']) ?>">
                    <button type="submit" class="button-small">Remove image</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="image-placeholder readonly">
                <span class="placeholder-label">No image</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-subsection">
        <h3>Details</h3>
        <div class="venue-details">
            <?php if (!empty($venue['address'])): ?><div><?= e($venue['address']) ?></div><?php endif; ?>
            <?php if (!empty($venue['city']) || !empty($venue['state'])): ?><div><?= e(trim(($venue['city'] ?? '') . ', ' . ($venue['state'] ?? ''), ', ')) ?></div><?php endif; ?>
            <?php if (!empty($venue['zip_code'])): ?><div><?= e($venue['zip_code']) ?></div><?php endif; ?>
        </div>
    </div>

    <?php if (!empty($venue['description'])): ?>
        <div class="card-subsection">
            <h3>About</h3>
            <p><?= e($venue['description']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($venue['tags'])): ?>
        <div class="card-subsection">
            <h3>Tags</h3>
            <div>
                <?php foreach ($venue['tags'] as $tag): ?>
                    <span class="badge">#<?= e(strtolower((string) $tag)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
