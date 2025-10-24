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

if (!$isEditor && !is_admin($currentUser)) {
    echo '<section class="card"><h2>Access Denied</h2><p>You do not have permission to edit this venue.</p></section>';
    return;
}
?>
<section>
    <h2>Edit Venue</h2>
    
    <div class="card">
        <form method="post" action="?page=venue&id=<?= e((string) $venue['id']) ?>" enctype="multipart/form-data" class="form">
            <input type="hidden" name="action" value="update_venue">
            <input type="hidden" name="venue_id" value="<?= e((string) $venue['id']) ?>">
            
            <div class="form-row">
                <label for="name">Venue Name *</label>
                <input type="text" id="name" name="name" value="<?= e($venue['name']) ?>" required>
            </div>
            
            <div class="form-row">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= e($venue['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?= e($venue['address'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="<?= e($venue['city'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <label for="state">State</label>
                <input type="text" id="state" name="state" value="<?= e($venue['state'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <label for="zip_code">ZIP Code</label>
                <input type="text" id="zip_code" name="zip_code" value="<?= e($venue['zip_code'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <label for="owner">Venue Owner *</label>
                <input type="text" id="owner" name="owner" value="<?= e($venue['owner'] ?? '') ?>" required>
            </div>
            
            <div class="form-row">
                <label for="deputies">Deputies</label>
                <input type="text" id="deputies" name="deputies" value="<?= e(implode(', ', $venue['deputies'] ?? [])) ?>" placeholder="Comma-separated list of deputy names">
                <small>Deputies can help manage the venue. Separate names with commas.</small>
            </div>
            
            <div class="form-row">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" value="<?= e(implode(', ', $venue['tags'] ?? [])) ?>" placeholder="Comma-separated tags (e.g., outdoor, downtown, parking)">
                <small>Add tags to help people discover your venue. Separate with commas.</small>
            </div>
            
            <?php if (!empty($venue['image'])): ?>
                <div class="form-row">
                    <label>Current Image</label>
                    <div>
                        <img src="uploads/<?= e($venue['image']) ?>" alt="Venue image" style="max-width: 300px; border-radius: 8px;">
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="form-row">
                <label for="image">Update Venue Image</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                <small>Upload a new image to replace the current one. Leave empty to keep current image.</small>
            </div>
            
            <div class="actions">
                <button type="submit" class="button">Update Venue</button>
                <a href="?page=venue&id=<?= e((string) $venue['id']) ?>" class="button">Cancel</a>
            </div>
        </form>
    </div>
    
    <div class="card">
        <h3>Danger Zone</h3>
        <form method="post" action="?page=venues" onsubmit="return confirm('Are you sure you want to delete this venue? This action cannot be undone.');">
            <input type="hidden" name="action" value="delete_venue">
            <input type="hidden" name="venue_id" value="<?= e((string) $venue['id']) ?>">
            <p class="subtle">Deleting a venue is permanent and cannot be undone. Any events linked to this venue will still exist but will show "Venue: TBA".</p>
            <button type="submit" class="button" style="background: var(--error-color);">Delete Venue</button>
        </form>
    </div>
</section>
