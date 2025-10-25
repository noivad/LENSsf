<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/navigation.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Local Event Network Service');
}

$siteName = SITE_NAME;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LENSsf::Help</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/help.css">
  <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="light">
  <?php renderNavigation('help', 'LENSsf - Help'); ?>

  <main class="main-content">
    <div class="container">
      <section class="card">
        <h2>Help</h2>
        <p class="subtle">This is a lightweight help page. Type a topic or use Option-? from any page.</p>
        <form>
          <input type="text" id="q" placeholder="Search help topics..." class="search-input" style="width: 100%; padding: 0.625rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
        </form>
        <div id="results" class="results-container"></div>
      </section>
    </div>
  </main>

  <?php renderFooter(); ?>

  <script src="js/help.js"></script>
</body>
</html>
