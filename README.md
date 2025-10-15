# LENSsf
Local Event Network Service

This website—when completed—will allow users to create events, add events to their calendar, define the location/venue and act as a venue owner, have deputies for both venues and events where they can take care of most administration. The site will all users to upload photos, coment on photos, and share events with other users. It will also allow custom tags for events and venue. All tags are public, and events with tags from other users will be suggested by the tag entry pane.

To keep things straightforward the application is built with plain PHP, HTML, CSS, and a sprinkle of vanilla JavaScript—no heavy frameworks or tooling required.

This is being done via vibe coding, with manual changes where the AI gets it wrong.

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

## Tagging & Sharing

Two lightweight JSON APIs expose the new functionality.

### Tagging (api/tags.php)

| Action | Method | Required payload/query |
| --- | --- | --- |
| `add_event_tag` | POST | `{ "event_id": <int>, "tag_name": "<string>", "category": "<optional>" }` |
| `add_venue_tag` | POST | `{ "venue_id": <int>, "tag_name": "<string>", "category": "<optional>" }` |
| `remove_event_tag` | DELETE | `{ "event_id": <int>, "tag_id": <int> }` |
| `remove_venue_tag` | DELETE | `{ "venue_id": <int>, "tag_id": <int> }` |
| `get_event_tags` | GET | `?action=get_event_tags&event_id=<int>` |
| `get_venue_tags` | GET | `?action=get_venue_tags&venue_id=<int>` |
| `search_tags` | GET | `?action=search_tags&query=<string>&limit=<optional int>` |
| `get_popular_tags` | GET | `?action=get_popular_tags&limit=<optional int>` |
| `get_events_by_tag` | GET | `?action=get_events_by_tag&tag_id=<int>` |
| `get_venues_by_tag` | GET | `?action=get_venues_by_tag&tag_id=<int>` |

All tagging actions require an authenticated session (`$_SESSION['user_id']`). Tag associations are public; the API records the user who attached each tag for auditing and personal suggestions.

### Sharing (api/sharing.php)

| Action | Method | Required payload/query |
| --- | --- | --- |
| `share_event` | POST | `{ "event_id": <int>, "shared_with": <int>, "message": "<optional>" }` |
| `revoke_event_share` | DELETE | `{ "event_id": <int>, "shared_with": <int> }` |
| `get_event_shares` | GET | `?action=get_event_shares&event_id=<int>` |
| `get_events_shared_with_me` | GET | `?action=get_events_shared_with_me` |
| `share_venue` | POST | `{ "venue_id": <int>, "shared_with": <int>, "message": "<optional>" }` |
| `revoke_venue_share` | DELETE | `{ "venue_id": <int>, "shared_with": <int> }` |
| `get_venue_shares` | GET | `?action=get_venue_shares&venue_id=<int>` |
| `get_venues_shared_with_me` | GET | `?action=get_venues_shared_with_me` |

Again, session authentication is required. A single share per sender/recipient is maintained for each entity; subsequent shares update the message and timestamp.

### Database

`newesSchema.sql` now includes:
- `event_tags` and `venue_tags` tables with `user_id` tracking and timestamps.
- `event_shares` and `venue_shares` tables for user-to-user sharing.
- `usage_count` on `tags` for surfacing popular tags.

Ensure these migrations are applied before exercising the APIs.
