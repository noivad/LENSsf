# Quick Start Guide

## Issues Fixed in This Update

### 1. ✅ Create Venue Form - Centered
The venue creation form is now centered on the page with a max width of 800px.

### 2. ✅ Database Connection Errors - Fixed
All database connection errors have been resolved:
- `event-list.php` ✓
- `tags.php` ✓
- `account.php` ✓

### 3. ✅ Stray Hyphens - Removed
Fixed incorrect filenames in `account.html`:
- `account-events-.html` → `account-events.html`
- `account-settings-.html` → `account-settings.html`

### 4. ✅ Sample Data Seed Script - Created
A comprehensive seed script has been created to populate Death Guild events and sample venues.

---

## How to Fix the Database Issues on Your System

The database errors occur because the database doesn't exist yet. Follow these steps:

### Step 1: Create the Database

Open your terminal and run:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**OR** if using MariaDB:

```bash
mariadb -u root -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**If you have a password**, add the `-p` flag:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step 2: Verify Config File

Make sure the file `/opt/homebrew/var/www/lensf7/config.php` exists with these settings:

```php
define('DB_NAME', 'lensf7');
define('DB_USER', 'root');
define('DB_PASS', '');  // Update if you have a password
```

The config.php file has been created in this repository. Copy it to your web root:

```bash
cp config.php /opt/homebrew/var/www/lensf7/
```

### Step 3: Populate with Sample Data (Optional)

To add Death Guild events and sample venues:

```bash
cd /opt/homebrew/var/www/lensf7
php database/seed.php
```

This will create:
- **DNA Lounge** venue (Home of Death Guild)
- **Death Guild events** for the next 4 Mondays
  - Time: 9:30 PM - 2:00 AM
  - Tags: #goth #industrial #DeathGuild
- **The Fillmore** venue
- **The Chapel** venue

---

## Testing Your Fix

After completing the steps above, try accessing these pages:

1. **Event List**: http://localhost:8000/event-list.php
2. **Tags**: http://localhost:8000/tags.php
3. **Account**: http://localhost:8000/account.php
4. **Venues** (to see centered form): http://localhost:8000/index.php?page=venues

All should work without database errors!

---

## Files Changed/Created

### Modified Files:
- `/includes/pages/venues.php` - Centered the create venue form
- `/public/account.html` - Fixed stray hyphens in links

### Created Files:
- `/config.php` - Main configuration with correct database name
- `/database/seed.php` - Sample data seed script
- `/database/SETUP.md` - Detailed database setup instructions
- `/FIXES.md` - Detailed explanation of all fixes
- `/QUICK_START.md` - This file

---

## Need More Help?

- **Detailed Setup Instructions**: See `/database/SETUP.md`
- **Detailed Fix Explanations**: See `/FIXES.md`
- **Database Issues**: The most common issue is that the database hasn't been created yet. Make sure you've run the CREATE DATABASE command above.

## Common Issues

### "mysql: command not found"
- Make sure MySQL or MariaDB is installed on your system
- On macOS with Homebrew: `brew install mysql` or `brew install mariadb`

### "Access denied for user 'root'@'localhost'"
- Update the `DB_PASS` in `config.php` with your MySQL password
- Or create a new MySQL user with appropriate permissions

### Schema not initialized
- The schema will be automatically created when you first connect to the database through the application
- Or manually run: `mysql -u root lensf7 < database/schema_mysql.sql`
