# Changes Made

## 1. Database Configuration Fixed

### Files Modified:
- `includes/db.php` - Fixed duplicate lines and set correct defaults:
  - Database name: `lenssf` (was incorrectly `lensf7` in error messages)
  - Database user: `lenssfadmin`
  - Database password: blank (empty string)
  - Host: `127.0.0.1`

### Files Created:
- `config.php` - Main configuration file with database credentials
- `database/sample_data.sql` - Sample data including DeathGuild events
- `populate_db.sh` - Script to populate database with sample data
- `DATABASE_SETUP.md` - Instructions for database setup

## 2. Venue Form Centered

### Files Modified:
- `public/css/style.css` - Updated `.popover` class to center the form:
  - Changed from `position: sticky` to `position: fixed`
  - Added `top: 50%; left: 50%; transform: translate(-50%, -50%)`
  - Increased width from `420px` to `800px`
  - Added `max-height: 90vh` and `overflow-y: auto`

## 3. Inline Styles Moved to CSS Files

### HTML Files Updated:
- `public/venue-info.html` - Removed inline styles, added venue-info.css
- `public/account.html` - Removed inline styles, fixed stray hyphens in links
- `public/add-event.html` - Removed inline styles, added add-event.css
- `public/shared.html` - Removed inline styles, added shared.css
- `public/help.html` - Removed inline styles, added help.css
- `public/account-events.html` - Removed inline styles, fixed broken links
- `public/account-settings.html` - Removed inline styles, fixed broken links
- `public/auth/login.html` - Removed inline styles, added auth.css
- `public/auth/register.html` - Removed inline styles, added auth.css

### PHP Files Updated:
- `public/venue-info.php` - Removed all inline styles

### CSS Files Created:
- `public/css/venue-info.css` - Styles for venue pages
- `public/css/account.css` - Styles for account pages
- `public/css/add-event.css` - Styles for add event page
- `public/css/shared.css` - Styles for shared items page
- `public/css/help.css` - Styles for help page
- `public/css/auth.css` - Styles for authentication pages

## 4. Inline Scripts Moved to JS Files

### JS Files Created:
- `public/js/venue-info-data.js` - Venue data and year display
- `public/js/add-event.js` - Image preview functionality
- `public/js/shared.js` - Tab switching and data loading
- `public/js/help.js` - Help search functionality
- `public/js/auth.js` - Slider CAPTCHA initialization

## 5. Fixed Stray Hyphens in Links

### Files Fixed:
- `public/account.html` - Fixed links:
  - `account-events-.html` → `account-events.html`
  - `account-settings-.html` → `account-settings.html`
- `public/account-events.html` - Fixed link: `account-.html` → `account.html`
- `public/account-settings.html` - Fixed link: `account-.html` → `account.html`

## 6. Sample Data Added

### Database Content:
- **DNA Lounge** venue (375 11th Street, San Francisco, CA 94103)
- **5 DeathGuild events** - Recurring every Monday from 9:30PM-2AM
  - Tags: `#goth`, `#industrial`, `#DeathGuild`, `#darkwave`, `#ebm`
  - Owner: DJ Decay
  - Deputies: DJ Trauma, DJ Bleak
- **2 additional venues**: The Chapel, Cat Club

## Summary

All issues have been resolved:
- ✅ Database configuration corrected (lenssf, not lensf7)
- ✅ Venue creation form centered on page
- ✅ All inline `<style>` tags moved to external CSS files
- ✅ All inline `<script>` tags moved to external JS files
- ✅ Stray hyphens in HTML filenames/links fixed
- ✅ Sample data with DeathGuild events created and ready to populate
