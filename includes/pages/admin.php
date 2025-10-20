<?php
$me = $_SESSION['current_user'] ?? '';
if (!is_admin($me)) {
    echo '<section class="card"><h2>Admin</h2><p>You do not have access to this page.</p></section>';
    return;
}

try {
    $pdoAdmin = Database::connect();
} catch (Throwable $e) {
    $pdoAdmin = null;
}

$bans = [];
if ($pdoAdmin) {
    $stmt = $pdoAdmin->query('SELECT id, user_identifier, ends_at, created_at FROM banned_users ORDER BY created_at DESC');
    $bans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
?>
<section>
    <h2>Admin Dashboard</h2>

    <div class="card">
        <h3>Kick (Temporarily Suspend) a User</h3>
        <form method="post" class="form" autocomplete="off">
            <input type="hidden" name="action" value="kick_user">
            <div class="form-row">
                <label>User identifier (username or display name)
                    <input type="text" name="user_identifier" required>
                </label>
            </div>
            <div class="form-row">
                <div class="form-split">
                    <label>Duration
                        <input type="number" min="1" step="1" name="duration" value="24" required>
                    </label>
                    <label>Unit
                        <select name="unit">
                            <option value="hours">Hours</option>
                            <option value="days">Days</option>
                            <option value="months">Months</option>
                        </select>
                    </label>
                </div>
            </div>
            <button type="submit" class="button">Kick User</button>
        </form>
    </div>

    <div class="card">
        <h3>Active Kicks</h3>
        <?php if ($bans): ?>
            <ul class="item-list">
                <?php foreach ($bans as $ban): ?>
                    <li>
                        <div><strong><?= e($ban['user_identifier']) ?></strong></div>
                        <div class="subtle">Until <?= e($ban['ends_at']) ?> (created <?= e(format_datetime($ban['created_at'] ?? null)) ?>)</div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="subtle">No active kicks.</p>
        <?php endif; ?>
    </div>
</section>
