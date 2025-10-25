# Universal Tags System Setup

This document explains the universal tags system that has been implemented and how to set it up.

## What Changed

### 1. Venue Single View with Edit/Delete
- **venue-info.php** now shows a single venue based on the `id` URL parameter
- Clicking on venues from the venue list now takes you to this single view page
- If you're the venue owner, you'll see "Edit Venue" and "Delete Venue" buttons
- Edit mode allows you to modify all venue fields inline without leaving the page
- Delete functionality removes the venue from the database

### 2. Venue List Layout Fixes
- Fixed CSS issues where venue card content was getting cut off
- Venue cards now auto-expand to fit all content
- Updated flexbox layout for better content display

### 3. Universal Tags System
Previously, tags were stored as JSON in separate `tags` columns for both events and venues. Now:

- **Universal tags table**: All tags are stored in a single `tags` table
- **Junction tables**: `event_tags` and `venue_tags` link entities to tags
- Tags are now reusable across both events and venues
- Better data integrity and easier tag management

### 4. Fixed SQL Error
- Fixed column name error in `account-unified.php` line 74
- Changed `e.owner` to `e.owner_name` to match the actual database schema

## Database Migrations

The following migrations have been created:

1. **004_create_universal_tags.sql** - Creates the universal tags tables:
   - `tags` - Universal tags table
   - `event_tags` - Junction table for event tags
   - `venue_tags` - Junction table for venue tags

2. **005_migrate_existing_tags.sql** - Migrates existing tags from JSON columns to the new tables

## Setup Instructions

### Option 1: Automatic Setup (Recommended)

Run the setup script from your project root:

```bash
php setup_universal_tags.php
```

This will:
1. Create the universal tags tables
2. Migrate all existing tags from JSON columns to the new system
3. Verify the setup

### Option 2: Manual Setup

If you prefer to run the migrations manually:

1. Connect to your MySQL database
2. Run the migration files in order:
   ```bash
   mysql -u your_user -p your_database < database/migrations/004_create_universal_tags.sql
   mysql -u your_user -p your_database < database/migrations/005_migrate_existing_tags.sql
   ```

## Backward Compatibility

The system is designed to be backward compatible:

- **TagManager** class handles all tag operations for the new system
- **VenueManager** automatically detects if universal tags are available
- If universal tags tables don't exist, it falls back to the JSON-based system
- Existing code continues to work during the transition

## Code Changes

### New Classes
- `TagManager` - Manages universal tags and their associations

### Updated Classes
- `VenueManager` - Now uses TagManager when universal tags are available
- Falls back to JSON tags if universal tags aren't set up

### Updated Files
- `public/venue-info.php` - Completely rewritten for single venue view with edit/delete
- `public/venues-list.php` - Updated to link to venue-info.php with ID parameter
- `public/css/calendar-7x5.css` - Fixed venue card layout issues, added button-danger style
- `public/account-unified.php` - Fixed SQL column name error

## Testing

After setup, verify:

1. ✅ Venue list displays correctly with no content cutoff
2. ✅ Clicking a venue takes you to the single venue view
3. ✅ Venue owners can see and use Edit/Delete buttons
4. ✅ Tags display correctly on venues
5. ✅ Adding/editing tags works properly
6. ✅ No SQL errors in account-unified.php

## Notes

- The JSON `tags` columns in `events` and `venues` tables are not removed for safety
- You can remove them after verifying the universal tags system works correctly
- The system seamlessly switches between JSON and universal tags based on availability
