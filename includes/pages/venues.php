<?php
/** @var array $venues */
?>
<section>
    <h2>Venues</h2>

    <div class="card" id="create" style="max-width: 800px; margin-left: auto; margin-right: auto; margin-bottom: 1.5rem;">
        <h3>Add New Venue</h3>
        <form method="post" enctype="multipart/form-data" class="form">
            <input type="hidden" name="action" value="create_venue">

            <div class="form-row">
                <label>
                    Venue Name *
                    <input type="text" name="name" required>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Description
                    <textarea name="description" rows="3"></textarea>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Venue Image
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                </label>
            </div>

            <div class="form-row">
                <label>
                    Address
                    <input type="text" name="address">
                </label>
            </div>

            <div class="form-row">
                <div class="form-split">
                    <label>
                        City
                        <input type="text" name="city">
                    </label>
                    <label>
                        State
                        <input type="text" name="state">
                    </label>
                    <label>
                        ZIP
                        <input type="text" name="zip_code">
                    </label>
                </div>
            </div>

            <div class="form-row">
                <label>
                    Venue Owner Name *
                    <input type="text" name="owner" required>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Deputies (comma-separated names)
                    <input type="text" name="deputies" placeholder="Pat, Lee, Morgan">
                </label>
            </div>

            <button type="submit" class="button">Add Venue</button>
        </form>
    </div>

    <div class="card">
        <h3>All Venues</h3>
        <?php if ($venues): ?>
            <div class="venue-list">
                <?php foreach ($venues as $venue): ?>
                    <div class="venue-item">
                        <h4><a href="?page=venue&id=<?= e((string) $venue['id']) ?>"><?= e($venue['name']) ?></a></h4>

                        <?php if (!empty($venue['image'])): ?>
                            <div class="venue-image">
                                <img src="uploads/<?= e($venue['image']) ?>" alt="<?= e($venue['name']) ?>">
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($venue['description'])): ?>
                            <p><?= e($venue['description']) ?></p>
                        <?php endif; ?>

                        <div class="venue-details">
                            <?php if (!empty($venue['address'])): ?>
                                <span><?= e($venue['address']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($venue['city']) || !empty($venue['state'])): ?>
                                <span><?= e(trim(($venue['city'] ?? '') . ', ' . ($venue['state'] ?? ''), ', ')) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($venue['zip_code'])): ?>
                                <span><?= e($venue['zip_code']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="venue-meta">
                            <span><strong>Owner:</strong> <?= e($venue['owner']) ?></span>
                            <?php if (!empty($venue['deputies'])): ?>
                                <span><strong>Deputies:</strong> <?= e(implode(', ', $venue['deputies'])) ?></span>
                            <?php endif; ?>
                            <span><strong>Created:</strong> <?= format_datetime($venue['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No venues yet. Add one using the form above.</p>
        <?php endif; ?>
    </div>
</section>
