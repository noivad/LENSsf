<?php
/** @var array $events */
/** @var array $photos */
/** @var EventManager $eventManager */

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$tab = $_GET['tab'] ?? 'overview';

$eventLookup = [];
foreach ($events as $ev) {
    $eventLookup[$ev['id']] = $ev;
}

function account_event_has_passed(?string $date): bool {
    if (!$date) return false;
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $d instanceof DateTimeImmutable && $d < new DateTimeImmutable('today');
}
?>
<section>
    <h2>My Account</h2>

    <div class="card">
        <div class="actions" style="margin-bottom: 1rem;">
            <a href="?page=account&tab=overview" class="<?= $tab === 'overview' ? 'active' : '' ?>">Overview</a>
            <a href="?page=account&tab=photos" class="<?= $tab === 'photos' ? 'active' : '' ?>">Photos</a>
            <a href="?page=account&tab=comments" class="<?= $tab === 'comments' ? 'active' : '' ?>">Comments</a>
            <a href="?page=account_events" class="<?= ($tab === 'events') ? 'active' : '' ?>">My Events</a>
            <a href="?page=account_settings">Settings</a>
        </div>

        <?php if ($tab === 'overview'): ?>
            <?php
            $myEvents = array_values(array_filter($events, static fn(array $e): bool => ($e['owner'] ?? '') === $currentUser));
            $myPhotos = array_values(array_filter($photos, static fn(array $p): bool => ($p['uploaded_by'] ?? '') === $currentUser));

            $myPhotoCommentsCount = 0;
            foreach ($photos as $p) {
                foreach ($p['comments'] as $c) {
                    if (($c['name'] ?? '') === $currentUser) {
                        $myPhotoCommentsCount++;
                    }
                }
            }

            $myEventCommentsCount = 0;
            foreach ($events as $e) {
                foreach ($eventManager->getEventComments((int) $e['id']) as $c) {
                    if (($c['name'] ?? '') === $currentUser) {
                        $myEventCommentsCount++;
                    }
                }
            }
            ?>
            <div class="grid">
                <div class="card stat">
                    <span class="label">Events Created</span>
                    <span class="value"><?= count($myEvents) ?></span>
                </div>
                <div class="card stat">
                    <span class="label">Photos Uploaded</span>
                    <span class="value"><?= count($myPhotos) ?></span>
                </div>
                <div class="card stat">
                    <span class="label">Comments Posted</span>
                    <span class="value"><?= $myPhotoCommentsCount + $myEventCommentsCount ?></span>
                </div>
            </div>

            <p class="subtle">Signed in as <?= e($currentUser) ?>. Use the tabs above to manage your photos and comments, or visit <a href="?page=account_settings">Settings</a> to change your theme.</p>
        <?php elseif ($tab === 'photos'): ?>
            <h3>Your Uploaded Photos</h3>
            <?php
            $myPhotos = array_values(array_filter($photos, static fn(array $p): bool => ($p['uploaded_by'] ?? '') === $currentUser));
            ?>
            <?php if ($myPhotos): ?>
                <div class="photo-grid">
                    <?php foreach ($myPhotos as $photo): ?>
                        <div class="photo-card">
                            <img src="uploads/<?= e($photo['filename']) ?>" alt="<?= e($photo['original_name']) ?>">
                            <?php if (!empty($photo['caption'])): ?>
                                <p class="photo-caption"><?= e($photo['caption']) ?></p>
                            <?php endif; ?>
                            <div class="photo-meta">
                                <span>Uploaded <?= format_datetime($photo['uploaded_at']) ?></span>
                                <span><?= e($photo['uploaded_by']) ?></span>
                            </div>
                            <?php if (!empty($photo['event_id']) && isset($eventLookup[$photo['event_id']])): ?>
                                <div class="photo-event">
                                    Linked to event: <?= e($eventLookup[$photo['event_id']]['title']) ?> (<?= format_date($eventLookup[$photo['event_id']]['event_date']) ?>)
                                </div>
                            <?php endif; ?>
                            <form method="post" class="action-form inline-form" onsubmit="return confirm('Delete this photo? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_photo">
                                <input type="hidden" name="photo_id" value="<?= e((string) $photo['id']) ?>">
                                <input type="hidden" name="redirect" value="?page=account&tab=photos">
                                <button type="submit" class="button-small">Delete Photo</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>You haven't uploaded any photos yet.</p>
            <?php endif; ?>
        <?php elseif ($tab === 'comments'): ?>
            <h3>Your Comments</h3>
            <?php
            $myPhotoComments = [];
            foreach ($photos as $p) {
                foreach ($p['comments'] as $c) {
                    if (($c['name'] ?? '') === $currentUser) {
                        $eventId = $p['event_id'] ?? null;
                        $eventDate = $eventId && isset($eventLookup[$eventId]) ? ($eventLookup[$eventId]['event_date'] ?? null) : null;
                        $myPhotoComments[] = [
                            'id' => $c['id'],
                            'photo_id' => $p['id'],
                            'comment' => $c['comment'],
                            'created_at' => $c['created_at'] ?? null,
                            'event_id' => $eventId,
                            'event_date' => $eventDate,
                            'event_title' => $eventId && isset($eventLookup[$eventId]) ? $eventLookup[$eventId]['title'] : null,
                        ];
                    }
                }
            }
            $myEventComments = [];
            foreach ($events as $e) {
                $evComments = $eventManager->getEventComments((int) $e['id']);
                foreach ($evComments as $c) {
                    if (($c['name'] ?? '') === $currentUser) {
                        $myEventComments[] = [
                            'id' => $c['id'],
                            'event_id' => $e['id'],
                            'event_title' => $e['title'],
                            'event_date' => $e['event_date'] ?? null,
                            'comment' => $c['comment'],
                            'created_at' => $c['created_at'] ?? null,
                        ];
                    }
                }
            }
            ?>

            <div class="card">
                <h4>Photo Comments</h4>
                <?php if ($myPhotoComments): ?>
                    <ul class="item-list">
                        <?php foreach ($myPhotoComments as $c): $passed = account_event_has_passed($c['event_date'] ?? null); ?>
                            <li>
                                <div><strong>On photo #<?= e((string) $c['photo_id']) ?></strong><?php if (!empty($c['event_title'])): ?> (Event: <?= e($c['event_title']) ?>)<?php endif; ?></div>
                                <div class="subtle">Posted <?= e(format_datetime($c['created_at'] ?? null)) ?></div>
                                <form method="post" class="inline-form" style="margin-top: 0.5rem;">
                                    <input type="hidden" name="action" value="update_photo_comment">
                                    <input type="hidden" name="comment_id" value="<?= e((string) $c['id']) ?>">
                                    <input type="hidden" name="redirect" value="?page=account&tab=comments">
                                    <textarea name="comment_text" rows="2" <?= $passed ? 'disabled' : '' ?>><?= e($c['comment']) ?></textarea>
                                    <div class="actions">
                                        <button type="submit" class="button-small" <?= $passed ? 'disabled' : '' ?>>Update</button>
                                    </div>
                                </form>
                                <form method="post" class="inline-form" style="margin-top: 0.5rem;" onsubmit="return confirm('Delete this comment?');">
                                    <input type="hidden" name="action" value="delete_photo_comment">
                                    <input type="hidden" name="comment_id" value="<?= e((string) $c['id']) ?>">
                                    <input type="hidden" name="redirect" value="?page=account&tab=comments">
                                    <button type="submit" class="button-small" <?= $passed ? 'disabled' : '' ?>>Delete</button>
                                </form>
                                <?php if ($passed): ?>
                                    <small class="subtle">This event has passed; editing is disabled.</small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No photo comments yet.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h4>Event Comments</h4>
                <?php if ($myEventComments): ?>
                    <ul class="item-list">
                        <?php foreach ($myEventComments as $c): $passed = account_event_has_passed($c['event_date'] ?? null); ?>
                            <li>
                                <div><strong>On event:</strong> <a href="?page=event&id=<?= e((string) $c['event_id']) ?>"><?= e($c['event_title']) ?></a> (<?= format_date($c['event_date'] ?? null) ?>)</div>
                                <div class="subtle">Posted <?= e(format_datetime($c['created_at'] ?? null)) ?></div>
                                <form method="post" class="inline-form" style="margin-top: 0.5rem;">
                                    <input type="hidden" name="action" value="update_event_comment">
                                    <input type="hidden" name="comment_id" value="<?= e((string) $c['id']) ?>">
                                    <input type="hidden" name="redirect" value="?page=account&tab=comments">
                                    <textarea name="comment_text" rows="2" <?= $passed ? 'disabled' : '' ?>><?= e($c['comment']) ?></textarea>
                                    <div class="actions">
                                        <button type="submit" class="button-small" <?= $passed ? 'disabled' : '' ?>>Update</button>
                                    </div>
                                </form>
                                <form method="post" class="inline-form" style="margin-top: 0.5rem;" onsubmit="return confirm('Delete this comment?');">
                                    <input type="hidden" name="action" value="delete_event_comment">
                                    <input type="hidden" name="comment_id" value="<?= e((string) $c['id']) ?>">
                                    <input type="hidden" name="redirect" value="?page=account&tab=comments">
                                    <button type="submit" class="button-small" <?= $passed ? 'disabled' : '' ?>>Delete</button>
                                </form>
                                <?php if ($passed): ?>
                                    <small class="subtle">This event has passed; editing is disabled.</small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No event comments yet.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Unknown tab.</p>
        <?php endif; ?>
    </div>
</section>
