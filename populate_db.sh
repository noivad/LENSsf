#!/bin/bash

# Script to populate the database with sample data
# Usage: ./populate_db.sh

DB_NAME="lenssf"
DB_USER="lenssfadmin"
DB_PASS=""
DB_HOST="127.0.0.1"

echo "Populating database: $DB_NAME"
echo "This will add sample data including DeathGuild events..."

# Check if mysql command is available
if ! command -v mysql &> /dev/null; then
    echo "Error: mysql command not found. Please install MySQL client."
    exit 1
fi

# Populate sample data
if [ -z "$DB_PASS" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < database/sample_data.sql
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/sample_data.sql
fi

if [ $? -eq 0 ]; then
    echo "Sample data populated successfully!"
    echo ""
    echo "Added:"
    echo "  - DNA Lounge venue"
    echo "  - 5 DeathGuild events (every Monday 9:30PM-2AM)"
    echo "  - Tags: #goth #industrial #DeathGuild #darkwave #ebm"
    echo "  - 2 additional sample venues"
else
    echo "Error populating sample data."
    exit 1
fi
