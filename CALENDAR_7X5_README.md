# 7×5 Calendar with Event Popovers

This implementation provides a responsive calendar system with dynamic event management, designed with a futuristic aesthetic.

## Features

### Calendar Display
- **7×5 Grid Layout**: Seven columns (days of week) × five rows (weeks)
- **Square Container**: Calendar grid fits into a square aspect ratio on desktop
- **Event Flags**: Visual indicators (colored dots) show events on each day
- **Color-Coded Status**:
  - **Cyan/Blue** (#2af6ff): Upcoming events
  - **Green** (#39ffb5): Events happening now (with pulsing animation)
  - **Gray** (#6e7899): Past events (with reduced opacity)

### Event Popovers
On hover over a day with events, a popover appears showing:
- Event title with emoji icon
- Venue location
- Time range
- Event creator
- Event description
- Current status (Upcoming/Happening Now/Past)

### User Actions
- **All Users**: Can share any event
- **Event Creators**: Get additional controls:
  - Edit event
  - Delete event (with confirmation dialog)

### Theme System
- **Dark Mode** (default): Advanced civilization theme with deep blue/purple gradients
- **Light Mode**: Clean white/blue professional theme
- **Toggle Button**: Switches between themes with localStorage persistence
- **Smooth Transitions**: All color changes are animated

### Responsive Design

#### Desktop (>768px)
- Sidebar navigation on the left
- User controls in top-right header
- Full calendar grid with hover popovers

#### Mobile (≤768px)
- Navigation collapses to top of page
- Compact calendar cells
- Touch-friendly controls
- Popovers adjust positioning for smaller screens

### Event Images Gallery
- Displays random images from past events at bottom of page
- Grid layout with hover effects
- Image overlays with captions
- Responsive grid adapts to screen size

## Files

### HTML Mockup
**Location**: `/public/calendar-7x5-mockup.html`
- Static HTML version with hardcoded sample events
- Perfect for design review and frontend testing
- No server-side dependencies

### PHP Dynamic Version
**Location**: `/public/calendar-7x5.php`
- Fully functional PHP implementation
- Supports month/year navigation via URL parameters
- Dynamic event rendering from PHP array (ready for database integration)
- Example: `calendar-7x5.php?month=11&year=2025`

### CSS Stylesheet
**Location**: `/public/css/calendar-7x5.css`
- Complete responsive styles
- CSS custom properties for theming
- Animations and transitions
- Mobile-first media queries

### JavaScript
**Location**: `/public/js/calendar-7x5.js`
- Theme toggle with localStorage
- User dropdown menu
- Delete confirmation modal
- Event action handlers
- Ready for AJAX integration

## Usage

### Running the HTML Mockup
Simply open `public/calendar-7x5-mockup.html` in any web browser.

### Running the PHP Version
1. Start the PHP development server:
   ```bash
   cd /home/engine/project/public
   php -S localhost:8000
   ```
2. Navigate to: `http://localhost:8000/calendar-7x5.php`

### Navigating Months
Use the "← Previous" and "Next →" buttons or URL parameters:
- Previous month: `?month=9&year=2025`
- Next month: `?month=11&year=2025`

## Customization

### Adding Real Events
In `calendar-7x5.php`, replace the `$events` array with a database query:

```php
// Example integration with existing EventRepository
require_once '../includes/managers/EventManager.php';
$eventManager = new EventManager();
$events = $eventManager->getEventsByMonthYear($month, $year);
```

### Adjusting Colors
Edit CSS custom properties in `:root` and `[data-theme="light"]`:
- `--accent-cyan`: Primary accent color
- `--accent-purple`: Secondary accent
- `--accent-pink`: Warning/delete actions
- `--event-upcoming`, `--event-happening`, `--event-past`: Event status colors

### Configuring User Authentication
The current implementation checks `$event['is_creator']` to determine if edit/delete buttons should appear. Integrate with your session:

```php
$event['is_creator'] = isset($_SESSION['user_id']) 
    && $_SESSION['user_id'] === $event['creator_id'];
```

## Design Philosophy

The calendar embodies an "advanced civilization" aesthetic:
- **Neon Gradients**: Cyan to purple gradients evoke futuristic interfaces
- **Glowing Effects**: Subtle shadows and hover glows
- **Smooth Animations**: Everything transitions smoothly
- **High Contrast**: Readable in both themes
- **Geometric Precision**: Clean borders and consistent spacing

## Browser Support
- Modern evergreen browsers (Chrome, Firefox, Safari, Edge)
- CSS Grid and Custom Properties required
- Flexbox for layout components
- LocalStorage for theme persistence

## Future Enhancements
- Drag-and-drop event creation
- Filter events by category/tag
- Export to iCal format
- Real-time updates via WebSocket
- Multi-user calendar overlays
- Event reminders and notifications
