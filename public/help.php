<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';

autoloadSession();
ensureSiteName();

$siteName = $_SESSION['site_name'] ?? 'Local Event Network Service';
$theme = $_SESSION['theme'] ?? 'dark';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help - <?= e($siteName) ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/help.css">
</head>
<body class="theme-<?= e($theme) ?>">
  <main class="container wrap">
    <h1>Help</h1>
    <p class="muted">This is a lightweight help page. Type a topic or use Option-? from any page.</p>
    <form>
      <input type="text" id="q" placeholder="Search help topics..." class="search-input">
    </form>
    <div id="results" class="results-container"></div>
  </main>
  <script src="js/help.js"></script>
</body>
</html>
