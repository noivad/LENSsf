# Database Setup

This directory contains the MySQL database schema for the Local Event Network Service.

## Automatic Setup (Recommended)

The database schema is automatically created when you first access the application. Just make sure:

1. MySQL is running
2. The database exists: `CREATE DATABASE lenssf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Your `config.php` has the correct credentials

The application will create all tables automatically on first connection.

## Manual Setup (Optional)

If you prefer to manually create the database schema:

```bash
mysql -u your_username -p lenssf < schema_mysql.sql
```

Or from within MySQL:

```sql
USE lenssf;
SOURCE schema_mysql.sql;
```

## Schema Overview

**Tables:**

- **venues** - Venue details with owner names and deputies (stored as JSON)
- **events** - Events with dates, times, venue links, and deputies (JSON)
- **event_calendar_entries** - User calendar additions for events (unique per event+name)
- **event_shares** - Event sharing between users (unique per event+shared_with)
- **photos** - Photo uploads with captions and event associations
- **photo_comments** - Comments on photos

All tables use InnoDB engine with utf8mb4 character set for full Unicode support.

## Migrations

Currently, schema updates are manual. Future versions may include migration scripts.

If you need to reset the database:

```sql
DROP DATABASE lenssf;
CREATE DATABASE lenssf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then restart the application to recreate tables automatically.
