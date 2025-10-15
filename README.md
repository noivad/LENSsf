# LENSsf
Local Event Network Service

This website allows users to create events, add events to their calendar, define the location/venue and act as a venue owner, have deputies for both venues and events where they can take care of most administration. The site allows users to upload photos, comment on photos, and share events with other users.

To keep things straightforward the application is built with plain PHP, HTML, CSS, and a sprinkle of vanilla JavaScript—no heavy frameworks or tooling required.

## Features

- **Event Management** – Create events with titles, descriptions, dates, times, and assigned venues.
- **Venue Management** – Add venues with full address details, owners, and deputies.
- **Deputy System** – Attach deputies to both events and venues so tasks can be delegated easily.
- **Community Calendar** – View an aggregated calendar that highlights upcoming events and who has added them to their own calendars.
- **Calendar Entries** – Friends can add events to their calendar with one click so organizers see who is interested.
- **Photo Gallery** – Upload event photos (JPEG/PNG/GIF), add captions, and see who uploaded them.
- **Photo Comments** – Comment threads keep conversations alive under each photo.
- **Event Sharing** – Share events with others by name to keep everyone in the loop.
- **JSON Data Store** – Lightweight JSON persistence keeps setup simple while staying easy to inspect and edit.

## Getting Started

### Prerequisites

- PHP 8.0 or higher (the built-in web server is perfect for development)
- SQLite extension (bundled with most PHP installations)
- Optional: MySQL if you prefer a relational database — a schema is provided

### Quick Setup

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd LENSsf
   ```

2. Run the setup script (creates config, data, and upload directories):
   ```bash
   ./setup.sh
   ```
   > On Windows, run the listed commands manually or use Git Bash.

3. Start the development server:
   ```bash
   cd public
   php -S localhost:8000
   ```

4. Visit [http://localhost:8000](http://localhost:8000) in your browser.

### Configuration

The setup script copies `config.example.php` to `config.php`. Update the values inside `config.php` if you need to change the site name, URLs, or database preferences.

### Database (Optional)

The application uses JSON files by default. If you want a relational database instead, a complete schema is available in `database/schema.sql`. Update `config.php` with your database credentials and import the schema.

```sql
CREATE DATABASE lensf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then import:

```bash
mysql -u your_user -p lensf < database/schema.sql
```

## Project Structure

```
.
├── public/               # Document root
│   ├── index.php        # Router + layout
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript helpers
│   └── uploads/         # Photo uploads (gitignored)
├── includes/
│   ├── helpers.php      # Common helper functions
│   ├── DataStore.php    # JSON persistence layer
│   ├── db.php           # Optional PDO database helper
│   ├── managers/        # Domain services (events, venues, photos)
│   └── pages/           # Page templates (home, events, calendar, venues, photos)
├── data/                # JSON data files (tracked)
├── database/            # SQL schema for relational storage
├── setup.sh             # Convenience setup script
├── config.example.php   # Sample configuration
└── README.md
```

## Development Notes

- Sessions are started automatically in `public/index.php` (or via `config.php`).
- Flash messages use the Post/Redirect/Get pattern to avoid duplicate submissions.
- Uploaded images are limited to 5 MB and saved under `public/uploads/` so they can be served directly.
- IDs include human-friendly prefixes (`evt_`, `ven_`, `ph_`, `com_`, `cal_`) for easier debugging.
- No authentication yet—names are collected via form fields for now.

## Future Enhancements

- User authentication and roles
- Search and filtering for events and venues
- Email reminders or notifications for shared events
- iCal or Google Calendar export support
- Advanced media gallery with lightbox
- RSVP tracking and attendee limits
- Social sharing integrations

Enjoy building out your Local Event Network!
