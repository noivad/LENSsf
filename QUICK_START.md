# Quick Start Guide - LENS Event Management

## Prerequisites

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server

## Installation

### 1. Clone/Download the Repository
```bash
git clone <repository-url>
cd project
```

### 2. Create Configuration
```bash
cp config.example.php config.php
```

### 3. Update Database Credentials
Edit `config.php` and set your MySQL credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lenssf');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Create Database
```bash
mysql -u root -p
```
```sql
CREATE DATABASE lenssf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run Setup Script
```bash
./setup.sh
```

This will:
- Create upload directories
- Set proper permissions
- Initialize configuration

### 6. Database Auto-Initialization

The database schema will automatically initialize on first connection. The system will:
- Create all required tables
- Apply the base schema from `database/schema_mysql.sql`

Alternatively, run migrations manually:
```bash
php database/run_migrations.php
```

### 7. Start Development Server
```bash
cd public
php -S localhost:8000
```

Visit http://localhost:8000 in your browser.

## Directory Structure

```
project/
├── config.php              # Configuration (create from example)
├── public/                 # Web root
│   ├── index.php          # Main entry point
│   ├── add-event.php      # Create events
│   ├── venues-list.php    # Browse venues
│   ├── create-venue.php   # Create venues
│   ├── venue-detail.php   # Single venue view
│   └── uploads/           # User uploads
├── includes/              # PHP includes
│   ├── db.php            # Database connection
│   ├── helpers.php       # Helper functions
│   └── managers/         # Business logic
└── database/
    ├── schema_mysql.sql  # Base schema
    └── migrations/       # Database migrations
```

## First Steps

### 1. Create Your First Venue
1. Navigate to http://localhost:8000/venues-list.php
2. Click "Create Venue"
3. Fill in venue details
4. Upload an image (optional)
5. Click "Create Venue"

### 2. Create Your First Event
1. Navigate to http://localhost:8000/add-event.php
2. Fill in event details
3. Select a venue from the dropdown (or create a new one)
4. Upload an event image (optional)
5. Configure recurrence if needed:
   - Check "Recurring Event"
   - Choose recurrence type
   - Set interval and end date
6. Click "Create Event"

### 3. Browse Venues
Visit http://localhost:8000/venues-list.php to see all venues in a grid layout.

### 4. Manage a Venue
1. Click on any venue card
2. If you're the owner, you'll see edit options
3. Update venue details
4. Change images
5. Toggle privacy (for private venues)

## Key Features

### Recurring Events
Events can repeat with various patterns:
- **Weekly**: Every N weeks
- **Monthly (by day)**: e.g., "2nd Tuesday of each month"
- **Monthly (by date)**: e.g., "15th of each month"
- **Custom**: Every N days/weeks/months

### Private Venues
Create custom venues (like home addresses) that are:
- Initially private (only you can see)
- Can be toggled to public later
- Useful for home parties or private gatherings

### Image Management
Upload images for:
- Events (displayed in event lists)
- Venues (shown in venue grid and detail pages)
- User profiles (avatars)

Supported formats: JPEG, PNG, GIF, WebP (max 10MB)

## Common Tasks

### Add Recurrence to an Event
```
1. Check "Recurring Event"
2. Select recurrence type
3. Configure interval
4. Set end date (optional)
```

### Make a Private Venue Public
```
1. Go to venue detail page
2. Find "Privacy Status" section
3. Click "Make Public"
```

### Change Event Image
```
1. Edit event
2. Upload new image
3. Old image will be replaced
```

## Troubleshooting

### Database Connection Fails
- Check `config.php` credentials
- Ensure MySQL is running
- Verify database exists

### Images Not Uploading
- Check `public/uploads/` permissions (755)
- Verify PHP upload settings in `php.ini`
- Check max upload size in `config.php`

### Pages Show Blank
- Check PHP error logs
- Enable error display in `config.php`:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

### Tables Not Created
- Run migrations manually:
  ```bash
  php database/run_migrations.php
  ```
- Check MySQL error logs
- Verify database permissions

## Development Tips

### Testing Recurrence Patterns
Create test events with different patterns to see how they're stored:
```sql
SELECT id, title, is_recurring, recurrence_pattern 
FROM events 
WHERE is_recurring = 1;
```

### View Venue Privacy Settings
```sql
SELECT id, name, is_private, is_public, owner_name 
FROM venues;
```

### Check Upload Directory
```bash
ls -lR public/uploads/
```

## Production Deployment

### Before Deploying
1. Set proper database credentials
2. Disable error display
3. Set appropriate permissions (755 for directories, 644 for files)
4. Configure web server (Apache/Nginx)
5. Set up SSL certificate
6. Configure backup system

### Security Checklist
- [ ] Database credentials secured
- [ ] File upload validation enabled
- [ ] Input sanitization active
- [ ] HTTPS configured
- [ ] Directory listing disabled
- [ ] Error display disabled

## Support & Documentation

- See `IMPLEMENTATION_SUMMARY.md` for detailed technical information
- Check `README.md` for project overview
- Review `API_DOCUMENTATION.md` for API details

## Version Information

Current implementation includes:
- Database schema v1.2 (with recurrence and privacy)
- Event recurrence patterns (weekly, monthly, custom)
- Venue privacy controls
- Image management system
- Grid layout for venues
- Full CRUD operations for events and venues
