# LENSsf
Local Event Network Service

This website allows users to create events, add events to their calendar, define the location/venue and act as a venue owner, have deputies for both venues and events where they can take care of most administration. The site allows users to upload photos, comment on photos, and share events with other users.

To keep things straightforward the application is built with plain PHP, HTML, CSS, and a sprinkle of vanilla JavaScript—no heavy frameworks or tooling required.

## Features

- **Event Management** – Create events with titles, descriptions, dates, times, and assigned venues
- **Venue Management** – Add venues with full address details, owners, and deputies
- **Deputy System** – Attach deputies to both events and venues so tasks can be delegated easily
- **Community Calendar** – View an aggregated calendar that highlights upcoming events and who has added them to their own calendars
- **Calendar Entries** – Friends can add events to their calendar with one click so organizers see who is interested
- **Photo Gallery** – Upload event photos (JPEG/PNG/GIF), add captions, and see who uploaded them
- **Photo Comments** – Comment threads keep conversations alive under each photo
- **Event Sharing** – Share events with others by name to keep everyone in the loop
- **MySQL Database** – Uses MySQL for reliable, relational data storage

## Getting Started

### Prerequisites

- **PHP 8.0 or higher** with PDO MySQL extension enabled
- **MySQL 5.7+ or MariaDB 10.3+**
- A web server (Apache, Nginx, or PHP's built-in server for development)

### Quick Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd LENSsf
   ```

2. **Create a MySQL database:**
   ```sql
   CREATE DATABASE lenssf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Run the setup script:**
   ```bash
   ./setup.sh
   ```
   > This creates the upload directory and copies `config.example.php` to `config.php`

4. **Configure the database:**
   Edit `config.php` and update your MySQL credentials:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'lenssf');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

5. **Start the development server:**
   ```bash
   cd public
   php -S localhost:8000
   ```

6. **Visit the app:**
   Open [http://localhost:8000](http://localhost:8000) in your browser
   
   The database tables will be created automatically on first load.

## Project Structure

```
.
├── public/               # Document root
│   ├── index.php        # Main application entry point
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript helpers
│   └── uploads/         # Photo uploads (gitignored)
├── includes/
│   ├── helpers.php      # Common helper functions
│   ├── db.php           # PDO database connection
│   ├── managers/        # Domain services (EventManager, VenueManager, PhotoManager)
│   └── pages/           # Page templates (home, events, calendar, venues, photos)
├── database/
│   └── schema_mysql.sql # MySQL database schema
├── setup.sh             # Setup script
├── config.example.php   # Sample configuration
└── README.md
```

## Database Schema

The application uses MySQL to store:

- **venues** – Venue details with owner names and deputies (stored as JSON)
- **events** – Events with dates, times, venue links, and deputies
- **event_calendar_entries** – User calendar additions for events
- **event_shares** – Event sharing between users
- **photos** – Photo uploads with captions and event associations
- **photo_comments** – Comments on photos

The schema is automatically applied on first run. You can also manually run:

```bash
mysql -u your_user -p lenssf < database/schema_mysql.sql
```

## Configuration

Edit `config.php` to customize:

```php
// Database
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'lenssf');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site
define('SITE_NAME', 'Local Event Network Service');
define('SITE_URL', 'http://localhost:8000');

// Uploads
define('UPLOAD_DIR', __DIR__ . '/public/uploads/');
define('MAX_UPLOAD_SIZE', 5_242_880); // 5MB
```

## Development Notes

- **Database Initialization** – The schema is automatically created on first database connection
- **Sessions** – Started automatically in `config.php`
- **Flash Messages** – Use the Post/Redirect/Get pattern to avoid duplicate submissions
- **File Uploads** – Limited to 5 MB, JPEG/PNG/GIF only, saved in `public/uploads/`
- **No Authentication** – Names are collected via form fields for now (authentication is a future enhancement)
- **Deputies** – Stored as JSON arrays in the database for flexibility

## Future Enhancements

- User authentication and authorization system
- Search and filtering for events and venues
- Email reminders or notifications for shared events
- iCal or Google Calendar export support
- Advanced media gallery with lightbox
- RSVP tracking and attendee limits
- Social sharing integrations
- Admin dashboard for site management

## Deployment

For production deployment:

1. Use a proper web server (Apache or Nginx)
2. Enable HTTPS with SSL certificates
3. Harden MySQL user permissions
4. Set restrictive file permissions on config.php
5. Configure proper error logging
6. Consider adding Redis/Memcached for session storage
7. Set up automated backups for the database

Enjoy building out your Local Event Network!
