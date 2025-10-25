# Database Setup Instructions

## Configuration

The application is configured to use the following database settings:

- **Database Name**: `lenssf`
- **Database User**: `lenssfadmin`
- **Database Password**: (empty/blank - set your own password)
- **Database Host**: `127.0.0.1`
- **Database Port**: `3306`

## Setup Steps

### 1. Create the Database

```bash
mysql -u root -p
```

Then in the MySQL prompt:

```sql
CREATE DATABASE lenssf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lenssfadmin'@'localhost' IDENTIFIED BY 'your_password_here';
GRANT ALL PRIVILEGES ON lenssf.* TO 'lenssfadmin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Update Configuration

Edit `config.php` and set your password:

```php
define('DB_PASS', 'your_password_here');
```

### 3. Initialize Schema

The schema will be automatically initialized when you first access the application, or you can manually run:

```bash
mysql -u lenssfadmin -p lenssf < database/schema_mysql.sql
```

### 4. Populate Sample Data

To add sample data including DeathGuild events:

```bash
./populate_db.sh
```

Or manually:

```bash
mysql -u lenssfadmin -p lenssf < database/sample_data.sql
```

## Sample Data

The sample data includes:

- **DNA Lounge** venue (375 11th Street, San Francisco)
- **5 DeathGuild events** - every Monday from 9:30PM-2AM
- Tags: `#goth`, `#industrial`, `#DeathGuild`, `#darkwave`, `#ebm`
- 2 additional sample venues (The Chapel, Cat Club)

## Troubleshooting

If you encounter "Unknown database" errors:

1. Make sure the database `lenssf` exists
2. Verify the user `lenssfadmin` has proper permissions
3. Check that `config.php` has the correct database credentials
4. Ensure the password in `config.php` matches your MySQL user password
