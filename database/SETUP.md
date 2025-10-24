# Database Setup Instructions

## Prerequisites

You need to have MySQL/MariaDB installed and running on your system.

## Setup Steps

### 1. Create the Database

Run the following command to create the database:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or if you're using MariaDB:

```bash
mariadb -u root -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If you have a password for your MySQL root user, add `-p` flag:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Update Configuration

Make sure your `config.php` file has the correct database credentials:

```php
define('DB_NAME', 'lensf7');
define('DB_USER', 'root');
define('DB_PASS', ''); // Update if you have a password
```

### 3. Initialize Schema

The schema will be automatically initialized when you first connect to the database through the application. The schema file is located at `database/schema_mysql.sql`.

Alternatively, you can manually run the schema:

```bash
mysql -u root lensf7 < database/schema_mysql.sql
```

### 4. Seed Sample Data (Optional)

To populate the database with sample data including Death Guild events:

```bash
php database/seed.php
```

This will create:
- DNA Lounge venue
- Death Guild events for the next 4 Mondays (9:30 PM - 2:00 AM)
- Additional sample venues (The Fillmore, The Chapel)
- Tags: #goth #industrial #DeathGuild

## Troubleshooting

### Error: "Unknown database 'lenssf'"

This error occurs when the config.php file doesn't exist or has the wrong database name. Make sure:

1. `config.php` exists in the project root (copy from `config.example.php` if needed)
2. The `DB_NAME` constant is set to `'lensf7'`
3. The database has been created as described in step 1

### Error: "Access denied for user 'root'@'localhost'"

Update the `DB_USER` and `DB_PASS` constants in `config.php` with your MySQL credentials.

### Database Connection Test

You can test your database connection by creating a simple test file:

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = Database::connect();
    echo "✅ Database connection successful!\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
```

Save this as `test-db.php` and run with `php test-db.php`.
