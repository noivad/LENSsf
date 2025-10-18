<?php
/** @var array $events */

$currentUser = $_SESSION['current_user'] ?? 'Demo User';

$myEvents = array_values(array_filter($events, static fn(array $e): bool => ($e['owner'] ?? '') === $currentUser));

$today = new DateTimeImmutable('today');
$pastEvents = array_values(array_filter($myEvents, static function (array $e) use ($today): bool {
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $e['event_date'] ?? '') ?: null;
    return $d instanceof DateTimeImmutable && $d < $today;
}));
?>
<section>
    <h2>My Events</h2>

    <div class="grid">
        <div class="card stat">
            <span class="label">Events Created</span>
            <span class="value"><?= count($myEvents) ?></span>
        </div>
        <div class="card stat">
            <span class="label">Past Events</span>
            <span class="value"><?= count($pastEvents) ?></span>
        </div>
    </div>

    <div class="card">
        <h3>Past Events</h3>
        <?php if ($pastEvents): ?>
            <ul class="item-list">
                <?php foreach ($pastEvents as $e): ?>
                    <li>
                        <a href="?page=event&id=<?= e((string) $e['id']) ?>"><?= e($e['title']) ?></a>
                        <span class="subtle">— <?= format_date($e['event_date'] ?? null) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No past events.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>All Events You've Hosted</h3>
        <?php if ($myEvents): ?>
            <ul class="item-list">
                <?php foreach ($myEvents as $e): ?>
                    <li>
                        <a href="?page=event&id=<?= e((string) $e['id']) ?>"><?= e($e['title']) ?></a>
                        <span class="subtle">— <?= format_date($e['event_date'] ?? null) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You haven't created any events yet.</p>
        <?php endif; ?>
    </div>

    <div class="actions">
        <a class="button" href="?page=account">Back to Account</a>
    </div>
</section>
