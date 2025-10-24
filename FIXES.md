# Fixes Applied

This document describes the fixes that have been applied to resolve the reported issues.

## 1. ✅ Centered Create Venue Form

**Issue**: The create venue form needed to be centered on the page.

**Fix**: Added inline styles to the venue form card in `/includes/pages/venues.php`:
- Set `max-width: 800px` to constrain the form width
- Added `margin-left: auto` and `margin-right: auto` to center it horizontally

**File Modified**: `/includes/pages/venues.php` (line 7)

## 2. ✅ Fixed Database Connection Errors

**Issue**: Fatal error `SQLSTATE[HY000] [1049] Unknown database 'lenssf'` when accessing:
- event-list.php
- tags.php
- account.php

**Root Cause**: The database name was incorrectly set to `'lenssf'` but should be `'lensf7'` based on your installation path `/opt/homebrew/var/www/lensf7/`.

**Fix**: Created `/config.php` file (from `config.example.php`) with the correct database name:
```php
define('DB_NAME', 'lensf7');
```

**Files Created**:
- `/config.php` - Main configuration file with correct database name

**Setup Required**: 
You need to create the database. Run this command in your terminal:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or if using MariaDB:
```bash
mariadb -u root -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

See `/database/SETUP.md` for detailed database setup instructions.

## 3. ✅ Fixed Stray Hyphens in Filenames

**Issue**: `account.html` contained references to files with stray hyphens:
- `account-events-.html` (should be `account-events.html`)
- `account-settings-.html` (should be `account-settings.html`)

**Fix**: Updated the links in `/public/account.html` to remove the trailing hyphens.

**File Modified**: `/public/account.html` (lines 40-41)

## 4. ✅ Created Database Seed Script

**Issue**: Need to populate the database with sample data including Death Guild events.

**Fix**: Created a comprehensive seed script that populates the database with:

**Sample Data Included**:
- **DNA Lounge** venue (375 Eleventh Street, San Francisco, CA 94103)
  - Owner: Jamie Zawinski
  - Deputies: Alex Rosenberg, Barry Threw
  
- **Death Guild Events** - Next 4 Mondays
  - Title: Death Guild
  - Description: Longest-running goth/industrial club in North America
  - Time: Every Monday from 9:30 PM to 2:00 AM
  - Tags: `#goth #industrial #DeathGuild`
  - Venue: DNA Lounge
  
- **Additional Sample Venues**:
  - The Fillmore (1805 Geary Boulevard)
  - The Chapel (777 Valencia Street)

**Files Created**:
- `/database/seed.php` - Seed script to populate sample data
- `/database/SETUP.md` - Detailed database setup instructions

**To Run the Seed Script**:
```bash
php database/seed.php
```

Make sure you've created the database and the schema has been initialized first (see `/database/SETUP.md`).

## Summary

All reported issues have been addressed:

1. ✅ Create venue form is now centered
2. ✅ Database connection error fixed (need to create database)
3. ✅ Stray hyphens in filenames removed
4. ✅ Seed script created with Death Guild event data

## Next Steps

To complete the setup:

1. Create the MySQL database:
   ```bash
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS lensf7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

2. Run the seed script to populate sample data:
   ```bash
   php database/seed.php
   ```

3. Access your application and the database errors should be resolved!

For detailed troubleshooting and setup instructions, see `/database/SETUP.md`.
