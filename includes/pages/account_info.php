<?php
$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$currentUserId = $_SESSION['user_id'] ?? 1;
?>
<section>
    <h2>Account Information</h2>

    <div class="card">
        <h3>Profile Details</h3>
        <div class="form-row">
            <label>Display Name</label>
            <input type="text" value="<?= e($currentUser) ?>" disabled style="background: var(--border-color);">
        </div>
        
        <div class="form-row">
            <label>User ID</label>
            <input type="text" value="<?= e((string) $currentUserId) ?>" disabled style="background: var(--border-color);">
        </div>
        
        <p class="subtle" style="margin-top: 1rem;">
            You are signed in as <strong><?= e($currentUser) ?></strong>. 
            To change your display name, use the URL parameter <code>?as=YourName</code>.
        </p>
    </div>

    <div class="card">
        <h3>Account Summary</h3>
        <?php
        $myEvents = array_values(array_filter($events, static fn(array $e): bool => ($e['owner'] ?? '') === $currentUser));
        $myPhotos = array_values(array_filter($photos, static fn(array $p): bool => ($p['uploaded_by'] ?? '') === $currentUser));
        
        $myVenues = array_values(array_filter($venues, static fn(array $v): bool => ($v['owner'] ?? '') === $currentUser));
        
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
                <span class="label">Venues Created</span>
                <span class="value"><?= count($myVenues) ?></span>
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
    </div>

    <div class="card">
        <h3>Quick Links</h3>
        <div class="actions">
            <a href="?page=account" class="button-small">Full Account Overview</a>
            <a href="?page=account_events" class="button-small">My Events</a>
            <a href="?page=account&tab=photos" class="button-small">My Photos</a>
            <a href="?page=account&tab=comments" class="button-small">My Comments</a>
            <a href="?page=account_settings" class="button-small">Settings</a>
            <a href="?page=shared" class="button-small">Shared Items</a>
        </div>
    </div>

    <div class="actions">
        <a class="button" href="?page=home">Back to Home</a>
    </div>
</section>
