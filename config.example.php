<?php
// Configuration file - Copy this to config.php and update values

// Database Configuration (MySQL only)
define('DB_TYPE', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'lensf7');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_NAME', 'Local Event Network Service');
define('SITE_URL', 'http://localhost:8000');

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/public/uploads/');
define('MAX_UPLOAD_SIZE', 5_242_880); // 5MB in bytes

// Session
session_start();
