# LENSsf
Local Event Network Service

This website—when completed—will allow users to create events, add events to their calendar, define the location/venue and act as a venue owner, have deputies for both venues and events where they can take care of most administration. The site will all users to upload photos, coment on photos, and share events with other users. It will also allow custom tags for events and venue. All tags are public, and events with tags from other users will be suggested by the tag entry pane.

I'm trying to make this really staightforward and simple. So, I’m using php since it's the most straight forward language, without a lot of the JS gotchas that take days to debug. 

This is being done via vibe coding, with manual changes where the AI gets it wrong.

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
