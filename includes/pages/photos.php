<?php
/** @var array $photos */
/** @var array $events */

$eventLookup = [];
foreach ($events as $event) {
    $eventLookup[$event['id']] = $event;
}
?>
<section>
    <h2>Photos</h2>

    <div class="card" id="upload">
        <h3>Upload Photo</h3>
        <form method="post" enctype="multipart/form-data" class="form">
            <input type="hidden" name="action" value="upload_photo">

            <div class="form-row">
                <label>
                    Photo *
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif" required>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Caption
                    <textarea name="caption" rows="2"></textarea>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Your Name *
                    <input type="text" name="uploaded_by" required>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Event (optional)
                    <select name="event_id">
                        <option value="">— Not linked to an event —</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?= e($event['id']) ?>">
                                <?= e($event['title']) ?> (<?= format_date($event['event_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <button type="submit" class="button">Upload</button>
        </form>
    </div>

    <div class="card">
        <h3>Photo Gallery</h3>
        <?php if ($photos): ?>
            <div class="photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-card">
                        <img src="uploads/<?= e($photo['filename']) ?>" alt="<?= e($photo['original_name']) ?>">
                        <?php if (!empty($photo['caption'])): ?>
                            <p class="photo-caption"><?= e($photo['caption']) ?></p>
                        <?php endif; ?>
                        <div class="photo-meta">
                            <span>By <?= e($photo['uploaded_by']) ?></span>
                            <span><?= format_datetime($photo['uploaded_at']) ?></span>
                        </div>

                        <?php if (!empty($photo['event_id']) && isset($eventLookup[$photo['event_id']])): ?>
                            <div class="photo-event">
                                Linked to event: <?= e($eventLookup[$photo['event_id']]['title']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($photo['comments'])): ?>
                            <div class="comments">
                                <strong>Comments:</strong>
                                <?php foreach ($photo['comments'] as $comment): ?>
                                    <div class="comment">
                                        <span class="comment-author"><?= e($comment['name']) ?>:</span>
                                        <span class="comment-text"><?= e($comment['comment']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <button class="button-small" onclick="showCommentForm('<?= e($photo['id']) ?>')">Add Comment</button>

                        <div id="comment-form-<?= e($photo['id']) ?>" class="hidden action-form">
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="add_comment">
                                <input type="hidden" name="photo_id" value="<?= e($photo['id']) ?>">
                                <input type="text" name="name" placeholder="Your name" required>
                                <textarea name="comment" placeholder="Your comment" rows="2" required></textarea>
                                <button type="submit" class="button-small">Post Comment</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No photos yet. Upload your first photo above.</p>
        <?php endif; ?>
    </div>
</section>
