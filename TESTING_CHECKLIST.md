# Testing Checklist - LENS Event & Venue Management

## Pre-Testing Setup

- [ ] Database credentials configured in `config.php`
- [ ] Database created: `CREATE DATABASE lenssf;`
- [ ] Migrations run: `php database/run_migrations.php`
- [ ] Server started: `cd public && php -S localhost:8000`
- [ ] Browser opened: `http://localhost:8000`

---

## 1. Venue Management

### Create Public Venue
- [ ] Navigate to venues page via sidebar
- [ ] Click "Create Venue" button
- [ ] Fill in required fields:
  - [ ] Venue name (required)
  - [ ] Owner name (required)
- [ ] Fill in optional fields:
  - [ ] Description
  - [ ] Address, City, State, ZIP
  - [ ] Opening hours
  - [ ] Tags (comma-separated)
  - [ ] Deputies
- [ ] Upload venue image
- [ ] Leave "private venue" unchecked
- [ ] Click "Create Venue"
- [ ] Verify redirect to venues list
- [ ] Verify venue appears in grid
- [ ] Verify flash message: "Venue created successfully!"

### Create Private/Custom Venue
- [ ] Navigate to "Create Venue"
- [ ] Fill in venue details
- [ ] Check "This is a private/custom venue"
- [ ] Click "Create Venue"
- [ ] Verify venue is created
- [ ] Navigate to venue detail page
- [ ] Verify "Privacy Status" shows "Private"

### View Venues Grid
- [ ] Navigate to venues list
- [ ] Verify venues display in grid layout
- [ ] Verify each venue card shows:
  - [ ] Image (if uploaded)
  - [ ] Name
  - [ ] Description (truncated)
  - [ ] Location (city, state)
  - [ ] Tags (up to 3 + count)
- [ ] Verify solid borders around venue cards
- [ ] Verify spacing between cards
- [ ] Hover over venue card
- [ ] Verify hover effect (lift + glow)

### Single Venue Detail Page
- [ ] Click on any venue card
- [ ] Verify redirect to venue detail page
- [ ] Verify all venue information displays
- [ ] If owner: Verify edit form appears
- [ ] If not owner: Verify read-only view

### Edit Venue (as owner)
- [ ] Navigate to owned venue detail page
- [ ] Modify venue name
- [ ] Update description
- [ ] Change address
- [ ] Update tags
- [ ] Click "Update Venue"
- [ ] Verify changes saved
- [ ] Verify flash message

### Change Venue Image
- [ ] Navigate to owned venue detail page
- [ ] Click "Choose File" under "Change Venue Image"
- [ ] Select new image (JPEG, PNG, GIF, or WebP)
- [ ] Click "Upload Image"
- [ ] Verify image updates
- [ ] Verify old image replaced

### Toggle Venue Privacy
- [ ] Create or navigate to private venue
- [ ] Verify "Privacy Status" section visible
- [ ] Verify status shows "Private"
- [ ] Click "Make Public"
- [ ] Verify status changes to "Public"
- [ ] Click "Make Private"
- [ ] Verify status changes back to "Private"

---

## 2. Event Management

### Create Basic Event
- [ ] Navigate to "Add Event"
- [ ] Fill in required fields:
  - [ ] Event title
  - [ ] Event date
  - [ ] Owner name
- [ ] Select venue from dropdown
- [ ] Click "Create Event"
- [ ] Verify event created
- [ ] Verify flash message

### Create Event with Image
- [ ] Navigate to "Add Event"
- [ ] Fill in event details
- [ ] Upload event image
- [ ] Click "Create Event"
- [ ] Verify event created with image

### Create Event from Custom Venue
- [ ] Navigate to "Add Event"
- [ ] Click "Create Custom Venue" link
- [ ] Create new private venue
- [ ] Return to "Add Event"
- [ ] Select newly created venue
- [ ] Complete event creation

### Create Recurring Event - Weekly
- [ ] Navigate to "Add Event"
- [ ] Fill in basic details
- [ ] Check "Recurring Event"
- [ ] Verify recurrence options appear
- [ ] Select "Weekly" from dropdown
- [ ] Verify "Repeat every X week(s)" appears
- [ ] Set interval to 2
- [ ] Set end date (optional)
- [ ] Click "Create Event"
- [ ] Verify event created
- [ ] Check database: `SELECT * FROM events WHERE is_recurring=1`
- [ ] Verify recurrence_pattern JSON contains:
  ```json
  {"type": "weekly", "interval": 2, "end_date": "..."}
  ```

### Create Recurring Event - Monthly by Day
- [ ] Navigate to "Add Event"
- [ ] Check "Recurring Event"
- [ ] Select "Monthly (specific day of week)"
- [ ] Verify options appear:
  - [ ] Week of month dropdown
  - [ ] Day of week dropdown
  - [ ] Interval input
- [ ] Select "Second" week
- [ ] Select "Tuesday"
- [ ] Set interval to 1
- [ ] Set end date
- [ ] Click "Create Event"
- [ ] Verify event created
- [ ] Check recurrence_pattern:
  ```json
  {
    "type": "monthly_day",
    "week": "second",
    "day_of_week": "tuesday",
    "interval": 1,
    "end_date": "..."
  }
  ```

### Create Recurring Event - Monthly by Date
- [ ] Navigate to "Add Event"
- [ ] Check "Recurring Event"
- [ ] Select "Monthly (specific date)"
- [ ] Verify "Repeat every X month(s)" appears
- [ ] Set interval to 3
- [ ] Click "Create Event"
- [ ] Verify event created

### Create Recurring Event - Custom Interval
- [ ] Navigate to "Add Event"
- [ ] Check "Recurring Event"
- [ ] Select "Custom interval"
- [ ] Verify custom options appear
- [ ] Set interval to 5
- [ ] Select "Day(s)"
- [ ] Click "Create Event"
- [ ] Verify event created
- [ ] Try with "Week(s)"
- [ ] Try with "Month(s)"

---

## 3. Image Management

### Event Images
- [ ] Create event with image
- [ ] Verify image displays in event list
- [ ] Verify image is in `/public/uploads/events/`
- [ ] Verify filename format: `event_[id]_[filename].jpg`

### Venue Images
- [ ] Create venue with image
- [ ] Verify image displays in venue grid
- [ ] Verify image displays in venue detail
- [ ] Verify image is in `/public/uploads/venues/`
- [ ] Verify filename format: `venue_[id]_[filename].jpg`

### Image Format Support
- [ ] Upload JPEG image - verify success
- [ ] Upload PNG image - verify success
- [ ] Upload GIF image - verify success
- [ ] Upload WebP image - verify success
- [ ] Try uploading PDF - verify rejection
- [ ] Try uploading > 10MB file - verify rejection

### Image Change/Update
- [ ] Create venue with image
- [ ] Navigate to venue detail
- [ ] Upload new image
- [ ] Verify old image replaced
- [ ] Verify old file deleted from filesystem
- [ ] Repeat for event images

---

## 4. Navigation & UI

### Sidebar Navigation
- [ ] Click "Home" - verify navigation
- [ ] Click "Events" - verify navigation
- [ ] Click "Venues" - verify navigation to venues-list.php
- [ ] Click "Tags" - verify navigation
- [ ] Click "Add Event" - verify navigation
- [ ] Verify active page highlighted

### Theme Toggle
- [ ] Click theme toggle button
- [ ] Verify theme switches (dark/light)
- [ ] Verify all elements properly themed
- [ ] Toggle back

### Responsive Design
- [ ] Resize browser to mobile width
- [ ] Verify venue grid adjusts
- [ ] Verify cards stack properly
- [ ] Verify navigation remains usable

---

## 5. Form Validation

### Required Fields - Event
- [ ] Navigate to "Add Event"
- [ ] Leave title blank
- [ ] Try to submit
- [ ] Verify browser validation message
- [ ] Fill title, leave date blank
- [ ] Try to submit
- [ ] Verify validation

### Required Fields - Venue
- [ ] Navigate to "Create Venue"
- [ ] Leave name blank
- [ ] Try to submit
- [ ] Verify validation
- [ ] Fill name, leave owner blank
- [ ] Try to submit
- [ ] Verify validation

### Flash Messages
- [ ] Create venue successfully
- [ ] Verify green success message
- [ ] Try to create with missing field
- [ ] Verify red error message

---

## 6. Database Persistence

### Verify Event Data
```sql
SELECT * FROM events ORDER BY created_at DESC LIMIT 5;
```
- [ ] Verify title, description saved
- [ ] Verify event_date, event_time saved
- [ ] Verify venue_id references correct venue
- [ ] Verify owner_name saved
- [ ] Verify image path saved
- [ ] Verify is_recurring set correctly
- [ ] Verify recurrence_pattern is valid JSON

### Verify Venue Data
```sql
SELECT * FROM venues ORDER BY created_at DESC LIMIT 5;
```
- [ ] Verify name, description saved
- [ ] Verify address fields saved
- [ ] Verify owner_name saved
- [ ] Verify is_private set correctly
- [ ] Verify is_public set correctly
- [ ] Verify image path saved
- [ ] Verify tags JSON valid

### Verify Foreign Keys
```sql
SELECT e.title, v.name 
FROM events e 
LEFT JOIN venues v ON e.venue_id = v.id;
```
- [ ] Verify events linked to venues
- [ ] Verify venue names display correctly

---

## 7. Edge Cases & Error Handling

### Large Files
- [ ] Try uploading 15MB image
- [ ] Verify rejection with appropriate message
- [ ] Verify no partial file created

### Invalid File Types
- [ ] Try uploading .txt file
- [ ] Try uploading .exe file
- [ ] Verify rejection

### Special Characters in Names
- [ ] Create venue with name: `O'Reilly's Pub & Grill`
- [ ] Verify saves correctly
- [ ] Verify displays without escaping issues

### Empty Optional Fields
- [ ] Create event with only required fields
- [ ] Verify saves successfully
- [ ] Create venue with only required fields
- [ ] Verify saves successfully

### Long Text
- [ ] Create venue with 5000 character description
- [ ] Verify saves
- [ ] Verify truncation in grid view
- [ ] Verify full text in detail view

---

## 8. Security

### SQL Injection Prevention
- [ ] Try venue name: `'; DROP TABLE venues; --`
- [ ] Verify saves as literal text
- [ ] Verify no SQL error

### XSS Prevention
- [ ] Try venue name: `<script>alert('XSS')</script>`
- [ ] Verify saves as literal text
- [ ] Verify script doesn't execute on display

### Path Traversal
- [ ] Monitor upload attempts with `../` in filename
- [ ] Verify sanitization
- [ ] Verify files saved securely

---

## 9. Performance

### Grid Loading
- [ ] Create 20+ venues
- [ ] Navigate to venues list
- [ ] Verify page loads quickly (< 1 second)
- [ ] Verify all images load

### Form Submission
- [ ] Create event with all fields filled
- [ ] Verify submission completes quickly
- [ ] Verify redirect is immediate

---

## 10. Backward Compatibility

### With Old Schema
- [ ] Remove is_private column: `ALTER TABLE venues DROP COLUMN is_private;`
- [ ] Try to view venues list
- [ ] Verify no errors
- [ ] Verify is_private treated as FALSE
- [ ] Restore column

### With Missing Columns
- [ ] Check manager classes handle missing columns gracefully
- [ ] Verify hasColumn() checks work
- [ ] Verify default values used

---

## Summary

### Critical Tests (Must Pass)
- [ ] Create venue with image
- [ ] Create event with venue selection
- [ ] View venues in grid
- [ ] Edit venue details
- [ ] Create recurring event (any type)
- [ ] Toggle venue privacy
- [ ] All images upload and display
- [ ] Database persistence verified

### Optional Tests (Nice to Have)
- [ ] All edge cases handled
- [ ] Performance acceptable
- [ ] Responsive design works
- [ ] Theme toggle works
- [ ] All validation works

### Test Results

**Date Tested:** _______________

**Environment:**
- PHP Version: _______________
- MySQL Version: _______________
- Browser: _______________

**Pass Rate:** _____ / _____ tests passed

**Issues Found:**
1. _________________________________
2. _________________________________
3. _________________________________

**Notes:**
_________________________________________
_________________________________________
_________________________________________

---

## Automated Testing Commands

### Check Database Tables
```bash
mysql -u root -p lenssf -e "SHOW TABLES;"
```

### Check Venue Columns
```bash
mysql -u root -p lenssf -e "DESCRIBE venues;"
```

### Check Event Columns
```bash
mysql -u root -p lenssf -e "DESCRIBE events;"
```

### Check Upload Directories
```bash
ls -lR public/uploads/
```

### Check Recent Events
```bash
mysql -u root -p lenssf -e "SELECT id, title, is_recurring FROM events ORDER BY created_at DESC LIMIT 5;"
```

### Check Recent Venues
```bash
mysql -u root -p lenssf -e "SELECT id, name, is_private, is_public FROM venues ORDER BY created_at DESC LIMIT 5;"
```

---

## Sign-Off

- [ ] All critical tests passed
- [ ] No major issues found
- [ ] Ready for production

**Tested By:** _______________
**Date:** _______________
**Signature:** _______________
