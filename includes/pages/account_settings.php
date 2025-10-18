<?php
/** @var array $events */
/** @var array $photos */

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$theme = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light') ? 'light' : 'dark';
?>
<section>
    <h2>Account Settings</h2>
    <div class="card">
        <p class="card-subtext">Signed in as <?= e($currentUser) ?></p>
        <form method="post" class="form">
            <input type="hidden" name="action" value="set_theme">
            <div class="form-row">
                <label>Theme</label>
                <div class="form-split">
                    <label>
                        <input type="radio" name="theme" value="dark" <?= $theme === 'dark' ? 'checked' : '' ?>>
                        Dark (default)
                    </label>
                    <label>
                        <input type="radio" name="theme" value="light" <?= $theme === 'light' ? 'checked' : '' ?>>
                        Light
                    </label>
                </div>
                <small>Switch between dark and light themes. Dark is the default.</small>
            </div>
            <input type="hidden" name="redirect" value="?page=account_settings">
            <div class="actions">
                <button type="submit" class="button">Save Settings</button>
                <a class="button" href="?page=account">Back to Account</a>
            </div>
        </form>
    </div>
</section>
