<?php
// Configuration file - Copy this to config.php and update values

define('DB_TYPE', 'sqlite'); // or 'mysql'
define('DB_PATH', __DIR__ . '/data/lensf.db'); // for SQLite

// For MySQL, uncomment and configure these:
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'lensf');
// define('DB_USER', 'root');
// define('DB_PASS', '');

define('SITE_NAME', 'Local Event Network Service');
define('SITE_URL', 'http://localhost');

define('UPLOAD_DIR', __DIR__ . '/public/uploads/');
define('MAX_UPLOAD_SIZE', 5_242_880); // 5MB in bytes

session_start();
