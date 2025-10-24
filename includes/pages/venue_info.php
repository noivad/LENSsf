<?php
/** @var VenueManager $venueManager */
/** @var EventManager $eventManager */

$venueId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$venue = $venueId > 0 ? $venueManager->findById($venueId) : null;

if (!$venue) {
    echo '<section class="card"><h2>Venue not found</h2><p>The venue you are looking for does not exist.</p></section>';
    return;
}

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$isEditor = strcasecmp($currentUser, (string) ($venue['owner'] ?? '')) === 0
    || in_array($currentUser, $venue['deputies'] ?? [], true);

$allEvents = $eventManager->all();
$venueEvents = array_values(array_filter($allEvents, static function (array $e) use ($venueId): bool {
    return (int) ($e['venue_id'] ?? 0) === $venueId;
}));

usort($venueEvents, static function (array $a, array $b): int {
    return strcmp($a['event_date'] ?? '', $b['event_date'] ?? '');
});
?>
<section class="card venue-info">
    <div class="venue-info-header">
        <div>
            <h2><?= e($venue['name']) ?></h2>
            <p class="subtle">Owner: <?= e((string) ($venue['owner'] ?? '')) ?></p>
            <?php if (!empty($venue['deputies'])): ?>
                <p class="subtle">Deputies: <?= e(implode(', ', $venue['deputies'])) ?></p>
            <?php endif; ?>
        </div>
        <div class="venue-info-actions">
            <?php if ($isEditor): ?>
                <a href="?page=venue_edit&id=<?= e((string) $venue['id']) ?>" class="button-small">Edit Venue</a>
            <?php endif; ?>
            <a href="?page=venues" class="button-small">Back to Venues</a>
        </div>
    </div>

    <?php if (!empty($venue['image'])): ?>
        <div class="venue-image">
            <img src="uploads/<?= e($venue['image']) ?>" alt="<?= e($venue['name']) ?>" style="max-width: 100%; border-radius: 8px;">
        </div>
    <?php endif; ?>

    <div class="card-subsection">
        <h3>Location Details</h3>
        <div class="venue-details">
            <?php if (!empty($venue['address'])): ?>
                <div><strong>Address:</strong> <?= e($venue['address']) ?></div>
            <?php endif; ?>
            <?php if (!empty($venue['city']) || !empty($venue['state'])): ?>
                <div><strong>City/State:</strong> <?= e(trim(($venue['city'] ?? '') . ', ' . ($venue['state'] ?? ''), ', ')) ?></div>
            <?php endif; ?>
            <?php if (!empty($venue['zip_code'])): ?>
                <div><strong>ZIP:</strong> <?= e($venue['zip_code']) ?></div>
            <?php endif; ?>
            <?php if (empty($venue['address']) && empty($venue['city']) && empty($venue['state']) && empty($venue['zip_code'])): ?>
                <p class="subtle">No address information available.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($venue['description'])): ?>
        <div class="card-subsection">
            <h3>About This Venue</h3>
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

    <div class="card-subsection">
        <h3>Events at This Venue</h3>
        <?php if ($venueEvents): ?>
            <ul class="item-list">
                <?php foreach ($venueEvents as $e): ?>
                    <li>
                        <a href="?page=event&id=<?= e((string) $e['id']) ?>"><?= e($e['title']) ?></a>
                        <span class="subtle">— <?= format_date($e['event_date'] ?? null) ?></span>
                        <?php if (!empty($e['start_time'])): ?>
                            <span class="subtle">at <?= e(format_time($e['start_time'])) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="subtle">No events scheduled at this venue yet.</p>
        <?php endif; ?>
    </div>

    <div class="card-subsection">
        <h3>Venue Statistics</h3>
        <div class="grid">
            <div class="card stat">
                <span class="label">Total Events</span>
                <span class="value"><?= count($venueEvents) ?></span>
            </div>
            <div class="card stat">
                <span class="label">Upcoming Events</span>
                <span class="value"><?php
                    $today = new DateTimeImmutable('today');
                    $upcomingCount = count(array_filter($venueEvents, static function (array $e) use ($today): bool {
                        $d = DateTimeImmutable::createFromFormat('Y-m-d', $e['event_date'] ?? '') ?: null;
                        return $d instanceof DateTimeImmutable && $d >= $today;
                    }));
                    echo $upcomingCount;
                ?></span>
            </div>
            <div class="card stat">
                <span class="label">Tags</span>
                <span class="value"><?= count($venue['tags'] ?? []) ?></span>
            </div>
        </div>
    </div>
</section>

<style>
.venue-info-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1.5rem;
}

.venue-info-actions {
    display: flex;
    gap: 0.5rem;
}

.venue-image {
    margin-bottom: 1.5rem;
}

.venue-details > div {
    margin-bottom: 0.5rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    margin: 0.25rem;
    background: var(--primary-color);
    color: white;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}
</style>
