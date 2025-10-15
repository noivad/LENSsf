# LENS API Documentation - Tagging & Sharing

This document describes the tagging and sharing APIs for the LENS (Local Event Network Service) platform.

## Authentication

All API endpoints require an authenticated session. The user must be logged in and have a valid `$_SESSION['user_id']` value.

Unauthorized requests will receive:
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

## Base URLs

- **Tagging API**: `/api/tags.php`
- **Sharing API**: `/api/sharing.php`

---

## Tagging API

### Add Tag to Event

**Endpoint**: `POST /api/tags.php?action=add_event_tag`

Adds a tag to an event. If the tag doesn't exist, it will be created automatically.

**Request Body**:
```json
{
  "event_id": 123,
  "tag_name": "live-music",
  "category": "genre"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Tag added successfully",
  "tag_id": 45
}
```

### Add Tag to Venue

**Endpoint**: `POST /api/tags.php?action=add_venue_tag`

Adds a tag to a venue. If the tag doesn't exist, it will be created automatically.

**Request Body**:
```json
{
  "venue_id": 456,
  "tag_name": "outdoor",
  "category": "amenity"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Tag added successfully",
  "tag_id": 67
}
```

### Remove Tag from Event

**Endpoint**: `DELETE /api/tags.php?action=remove_event_tag`

Removes a tag from an event (only the tag association created by the current user).

**Request Body**:
```json
{
  "event_id": 123,
  "tag_id": 45
}
```

**Response**:
```json
{
  "success": true,
  "message": "Tag removed successfully"
}
```

### Remove Tag from Venue

**Endpoint**: `DELETE /api/tags.php?action=remove_venue_tag`

Removes a tag from a venue (only the tag association created by the current user).

**Request Body**:
```json
{
  "venue_id": 456,
  "tag_id": 67
}
```

**Response**:
```json
{
  "success": true,
  "message": "Tag removed successfully"
}
```

### Get Event Tags

**Endpoint**: `GET /api/tags.php?action=get_event_tags&event_id=123`

Retrieves all tags associated with an event.

**Response**:
```json
{
  "success": true,
  "tags": [
    {
      "id": 45,
      "name": "live-music",
      "category": "genre",
      "usage_count": 127,
      "user_id": 10,
      "created_at": "2024-01-15 14:30:00"
    }
  ]
}
```

### Get Venue Tags

**Endpoint**: `GET /api/tags.php?action=get_venue_tags&venue_id=456`

Retrieves all tags associated with a venue.

**Response**:
```json
{
  "success": true,
  "tags": [
    {
      "id": 67,
      "name": "outdoor",
      "category": "amenity",
      "usage_count": 52,
      "user_id": 12,
      "created_at": "2024-01-16 10:15:00"
    }
  ]
}
```

### Search Tags

**Endpoint**: `GET /api/tags.php?action=search_tags&query=music&limit=10`

Searches for tags matching the query string. Results are sorted by usage count (descending) and name (ascending).

**Query Parameters**:
- `query` (required): Search term
- `limit` (optional): Maximum results to return (default: 10)

**Response**:
```json
{
  "success": true,
  "tags": [
    {
      "id": 45,
      "name": "live-music",
      "category": "genre",
      "usage_count": 127
    },
    {
      "id": 46,
      "name": "music-festival",
      "category": "genre",
      "usage_count": 89
    }
  ]
}
```

### Get Popular Tags

**Endpoint**: `GET /api/tags.php?action=get_popular_tags&limit=20`

Retrieves the most popular tags based on usage count.

**Query Parameters**:
- `limit` (optional): Maximum results to return (default: 20)

**Response**:
```json
{
  "success": true,
  "tags": [
    {
      "id": 1,
      "name": "concert",
      "category": "type",
      "usage_count": 543
    },
    {
      "id": 45,
      "name": "live-music",
      "category": "genre",
      "usage_count": 127
    }
  ]
}
```

### Get Events by Tag

**Endpoint**: `GET /api/tags.php?action=get_events_by_tag&tag_id=45`

Retrieves all events that have been tagged with the specified tag.

**Response**:
```json
{
  "success": true,
  "events": [
    {
      "id": 123,
      "title": "Summer Music Fest",
      "description": "Annual outdoor music festival",
      "venue_id": 10,
      "start_datetime": "2024-07-15 18:00:00",
      "end_datetime": "2024-07-15 23:00:00",
      "status": "published"
    }
  ]
}
```

### Get Venues by Tag

**Endpoint**: `GET /api/tags.php?action=get_venues_by_tag&tag_id=67`

Retrieves all venues that have been tagged with the specified tag.

**Response**:
```json
{
  "success": true,
  "venues": [
    {
      "id": 456,
      "name": "Central Park Amphitheater",
      "description": "Large outdoor venue",
      "address": "123 Park Ave",
      "city": "Springfield",
      "state": "IL",
      "capacity": 5000,
      "status": "active"
    }
  ]
}
```

---

## Sharing API

### Share Event

**Endpoint**: `POST /api/sharing.php?action=share_event`

Shares an event with another user.

**Request Body**:
```json
{
  "event_id": 123,
  "shared_with": 25,
  "message": "Check out this awesome event!"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Event shared successfully"
}
```

### Revoke Event Share

**Endpoint**: `DELETE /api/sharing.php?action=revoke_event_share`

Removes a previously shared event.

**Request Body**:
```json
{
  "event_id": 123,
  "shared_with": 25
}
```

**Response**:
```json
{
  "success": true,
  "message": "Event share revoked"
}
```

### Get Event Shares

**Endpoint**: `GET /api/sharing.php?action=get_event_shares&event_id=123`

Retrieves a list of users with whom the event has been shared.

**Response**:
```json
{
  "success": true,
  "shares": [
    {
      "id": 1,
      "event_id": 123,
      "shared_by": 10,
      "shared_with": 25,
      "recipient_username": "john_doe",
      "message": "Check out this awesome event!",
      "created_at": "2024-01-15 14:30:00"
    }
  ]
}
```

### Get Events Shared With Me

**Endpoint**: `GET /api/sharing.php?action=get_events_shared_with_me`

Retrieves all events that have been shared with the current user.

**Response**:
```json
{
  "success": true,
  "events": [
    {
      "id": 123,
      "title": "Summer Music Fest",
      "description": "Annual outdoor music festival",
      "venue_id": 10,
      "start_datetime": "2024-07-15 18:00:00",
      "end_datetime": "2024-07-15 23:00:00",
      "shared_by": 10,
      "message": "Check out this awesome event!",
      "created_at": "2024-01-15 14:30:00"
    }
  ]
}
```

### Share Venue

**Endpoint**: `POST /api/sharing.php?action=share_venue`

Shares a venue with another user.

**Request Body**:
```json
{
  "venue_id": 456,
  "shared_with": 25,
  "message": "Great venue for your next event!"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Venue shared successfully"
}
```

### Revoke Venue Share

**Endpoint**: `DELETE /api/sharing.php?action=revoke_venue_share`

Removes a previously shared venue.

**Request Body**:
```json
{
  "venue_id": 456,
  "shared_with": 25
}
```

**Response**:
```json
{
  "success": true,
  "message": "Venue share revoked"
}
```

### Get Venue Shares

**Endpoint**: `GET /api/sharing.php?action=get_venue_shares&venue_id=456`

Retrieves a list of users with whom the venue has been shared.

**Response**:
```json
{
  "success": true,
  "shares": [
    {
      "id": 2,
      "venue_id": 456,
      "shared_by": 10,
      "shared_with": 25,
      "recipient_username": "john_doe",
      "message": "Great venue for your next event!",
      "created_at": "2024-01-16 10:15:00"
    }
  ]
}
```

### Get Venues Shared With Me

**Endpoint**: `GET /api/sharing.php?action=get_venues_shared_with_me`

Retrieves all venues that have been shared with the current user.

**Response**:
```json
{
  "success": true,
  "venues": [
    {
      "id": 456,
      "name": "Central Park Amphitheater",
      "description": "Large outdoor venue",
      "address": "123 Park Ave",
      "city": "Springfield",
      "state": "IL",
      "shared_by": 10,
      "message": "Great venue for your next event!",
      "created_at": "2024-01-16 10:15:00"
    }
  ]
}
```

---

## Error Responses

All endpoints may return the following error responses:

### 400 Bad Request
```json
{
  "success": false,
  "message": "Event ID is required"
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Action not found"
}
```

### 405 Method Not Allowed
```json
{
  "success": false,
  "message": "Method not allowed"
}
```

---

## Notes

- All tags are public and visible to all users
- The system tracks which user added each tag for auditing purposes
- Users can only remove tags they personally added
- Tag names are automatically converted to lowercase
- Tag names have a maximum length of 50 characters
- A single share between two users per entity is maintained; subsequent shares update the message and timestamp
- Users cannot share events or venues with themselves
