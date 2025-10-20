# LENSsf
Local Event Network Service

This website—when completed—will allow users to create events, add events to their calendar, define the location/venue and act as a location/venue owner. There will also be deputies for both locations/venues and events where they can take care of most administration. The site will allow users to upload photos, coment on photos, and share events with other users. It will also allow custom tags for events and venue. All tags are public, and events with tags from other users will be suggested by the tag entry pane. Tags will be searchable for other events sharing the same tag. Tags can be suggested from other users' events/venues as well as be custom input. 

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
├── public/                       # Web root
│   ├── index.php                 # Router + layout + main nav
│   ├── add-event.php             # Alternate add-event workspace
│   ├── calendar-7x5.php          # 7×5 demo calendar
│   ├── venue-info.php            # Advanced venue details (standalone)
│   ├── event_api.php             # Legacy event API/demo
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── oauth_start.php
│   │   └── oauth_callback.php
│   ├── css/
│   │   ├── style.css
│   │   └── calendar-7x5.css
│   ├── js/
│   │   ├── main.js
│   │   ├── event.js
│   │   ├── venue-info.js
│   │   └── calendar-7x5.js
│   ├── uploads/                  # Photo uploads (gitignored)
│   └── [mockups]
│       ├── account-mockup.html
│       ├── account-events-mockup.html
│       ├── account-settings-mockup.html
│       ├── add-event-mockup.html
│       ├── calendar-7x5-mockup.html
│       └── venue-info-mockup.html
├── includes/
│   ├── helpers.php               # Common helper functions
│   ├── db.php                    # MySQL connection + auto schema init
│   ├── managers/                 # Domain services (events, venues, photos)
│   │   ├── EventManager.php
│   │   ├── VenueManager.php
│   │   └── PhotoManager.php
│   └── pages/                    # Router pages (ALL available pages)
│       ├── home.php
│       ├── events.php
│       ├── event.php
│       ├── venues.php
│       ├── venue.php
│       ├── calendar.php
│       ├── tags.php
│       ├── photos.php
│       ├── account.php
│       ├── account_events.php
│       ├── account_settings.php
│       └── admin.php
├── api/
│   ├── tags.php                  # Tags API (routes to backend/controllers/TagController.php)
│   └── sharing.php               # Sharing API (routes to backend/controllers/SharingController.php)
├── backend/
│   ├── controllers/
│   │   ├── TagController.php
│   │   └── SharingController.php
│   ├── repositories/             # DB access layer
│   └── services/                 # Business logic
├── database/
│   ├── schema_mysql.sql          # Auto-applied MySQL schema
│   └── README.md
├── [prototypes at repo root]
│   ├── calendar-view.php
│   ├── calendar-crud.php
│   ├── calendar-settings.php
│   ├── LENSsf6.html
│   └── tag-and-share-demo.html
├── setup.sh
├── config.example.php
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

## Missing Pages Checklist

The site is router-driven via `public/index.php`. All current pages are enumerated in the Project Structure above. Here’s the status and what remains to build to match the feature plan.

Already implemented
- [x] Home — `includes/pages/home.php`
- [x] Events list + create — `includes/pages/events.php`
- [x] Event details — `includes/pages/event.php`
- [x] Community calendar — `includes/pages/calendar.php`
- [x] Venues list + create — `includes/pages/venues.php`
- [x] Venue details — `includes/pages/venue.php`
- [x] Tags explorer — `includes/pages/tags.php`
- [x] Photo gallery + upload — `includes/pages/photos.php`
- [x] Account (Overview/Photos/Comments) — `includes/pages/account.php`
- [x] Account → My Events — `includes/pages/account_events.php`
- [x] Account → Settings (theme) — `includes/pages/account_settings.php`
- [x] Admin (basic moderation) — `includes/pages/admin.php`
- [x] Alternate add-event workspace — `public/add-event.php`
- [x] Advanced venue workspace — `public/venue-info.php`
- [x] 7×5 calendar demo — `public/calendar-7x5.php`
- [x] Auth forms (not wired into header) — `public/auth/login.php`, `public/auth/register.php`

Pages to build next
- [ ] Account → Shared with me — `includes/pages/account_shared.php`
      Lists events and venues shared with the current user using `/api/sharing.php?action=get_events_shared_with_me` and `get_venues_shared_with_me`. Add a new tab in Account navigation.
- [ ] Account → My shares — `includes/pages/account_shares.php`
      Shows items you’ve shared and allows revoking via `/api/sharing.php` (`get_event_shares`, `revoke_event_share`, `get_venue_shares`, `revoke_venue_share`).
- [ ] Event edit — `includes/pages/event_edit.php` (or inline editing on `event.php`)
      Full edit form for event fields and image.
- [ ] Venue edit — `includes/pages/venue_edit.php` (or inline editing on `venue.php`)
      Update venue fields and image.

Optional/advanced (defer until later)
- [ ] Integrate Calendar settings into the UI (wire `calendar-settings.php` under Settings or Calendar)
- [ ] Add Login/Register links in the header (auth exists under `public/auth/`)
- [ ] Inline tag UI on the Venues list if not relying on `venue-info.php`
- [ ] Admin subpages (Users/Roles/Site settings) for future role-based features
- [ ] Notifications (settings and history) if/when reminders are introduced
- [ ] Tag dashboards and richer discovery (trending, categories, etc.)
- [ ] Media lightbox and gallery enhancements

Notes
- Where a standalone page already exists (e.g. `public/venue-info.php`), you can either link to it from the router-based UI or port it into `includes/pages/*` for a unified navigation experience.
- Keep page structure and styling consistent with existing pages under `includes/pages/` and `public/css/style.css`.
