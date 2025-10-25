#!/bin/bash

echo "=========================================="
echo "LENS Implementation Verification"
echo "=========================================="
echo ""

SUCCESS=0
FAIL=0

check_file() {
    if [ -f "$1" ]; then
        echo "✓ $1"
        ((SUCCESS++))
    else
        echo "✗ $1 - MISSING"
        ((FAIL++))
    fi
}

check_dir() {
    if [ -d "$1" ]; then
        echo "✓ $1"
        ((SUCCESS++))
    else
        echo "✗ $1 - MISSING"
        ((FAIL++))
    fi
}

echo "Checking Database Files..."
check_file "database/schema_mysql.sql"
check_file "database/migrations/001_add_venue_privacy_fields.sql"
check_file "database/migrations/002_add_event_recurrence_fields.sql"
check_file "database/migrations/003_add_user_profile_table.sql"
check_file "database/run_migrations.php"

echo ""
echo "Checking Manager Classes..."
check_file "includes/managers/EventManager.php"
check_file "includes/managers/VenueManager.php"
check_file "includes/managers/UserManager.php"

echo ""
echo "Checking Public Pages..."
check_file "public/add-event.php"
check_file "public/venues-list.php"
check_file "public/create-venue.php"
check_file "public/venue-detail.php"

echo ""
echo "Checking CSS..."
check_file "public/css/calendar-7x5.css"

echo ""
echo "Checking JavaScript..."
check_file "public/js/add-event.js"

echo ""
echo "Checking Upload Directories..."
check_dir "public/uploads"
check_dir "public/uploads/events"
check_dir "public/uploads/venues"
check_dir "public/uploads/users"

echo ""
echo "Checking Navigation..."
check_file "includes/navigation.php"

echo ""
echo "Checking Documentation..."
check_file "IMPLEMENTATION_SUMMARY.md"
check_file "QUICK_START.md"
check_file "CHANGES_MADE.md"

echo ""
echo "Checking Configuration..."
check_file "config.php"

echo ""
echo "=========================================="
echo "Verification Complete"
echo "=========================================="
echo "✓ Success: $SUCCESS"
echo "✗ Failed:  $FAIL"
echo ""

if [ $FAIL -eq 0 ]; then
    echo "✓ All components verified!"
    echo ""
    echo "Next steps:"
    echo "1. Update database credentials in config.php"
    echo "2. Create database: CREATE DATABASE lenssf;"
    echo "3. Run: php database/run_migrations.php"
    echo "4. Start server: cd public && php -S localhost:8000"
    exit 0
else
    echo "✗ Some components are missing!"
    exit 1
fi
