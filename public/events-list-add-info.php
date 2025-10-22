<?php
declare(strict_types=1);

$today = new DateTimeImmutable('now');
$month = (int)$today->format('n');
$year = (int)$today->format('Y');

// Sample events with recurrence, privacy, photos, comments
$events = [
  [
    'id' => 'evt_101',
    'title' => 'Monday Night Club',
    'description' => 'Weekly dance night with live DJ sets. House, techno, and drum & bass until late.',
    'date' => (new DateTimeImmutable('monday this week'))->format('Y-m-d'),
    'startTime' => '21:30',
    'endTime' => '02:00',
    'owner' => 'You',
    'deputies' => ['Sam'],
    'tags' => ['nightlife','dance','dj'],
    'visibility' => 'Public',
    'image' => 'https://picsum.photos/seed/club/600/400',
    'sharedWith' => ['Alex','Jordan'],
    'recurrence' => [ 'freq' => 'Weekly', 'interval' => 1, 'byDay' => 1 ], // Monday
    'photos' => [
      ['url'=>'https://picsum.photos/seed/club1/400/300','visibility'=>'Public','tags'=>['crowd','lights']],
      ['url'=>'https://picsum.photos/seed/club2/400/300','visibility'=>'Invite Only','tags'=>['vip']]
    ],
    'comments' => [
      ['author'=>'Alex','text'=>'See you there!','visibility'=>'Public'],
      ['author'=>'Sam','text'=>'VIP table reserved.','visibility'=>'Invite Only']
    ],
  ],
  [
    'id' => 'evt_102',
    'title' => 'Third Sunday Brunch',
    'description' => 'Once a month on the third Sunday. Casual meetup and community announcements.',
    'date' => (new DateTimeImmutable('first day of this month'))->format('Y-m-01'),
    'startTime' => '10:00',
    'endTime' => '12:00',
    'owner' => 'Morgan',
    'deputies' => ['You'],
    'tags' => ['community','brunch'],
    'visibility' => 'Invite Only',
    'image' => 'https://picsum.photos/seed/brunch/600/400',
    'sharedWith' => ['You','Dana'],
    'recurrence' => [ 'freq' => 'Monthly', 'interval' => 1, 'byDay' => 0, 'nth' => 3 ], // 3rd Sunday
    'photos' => [
      ['url'=>'https://picsum.photos/seed/brunch1/400/300','visibility'=>'Event Specific','tags'=>['pancakes']],
    ],
    'comments' => [ ['author'=>'Morgan','text'=>'Bring a friend!','visibility'=>'Public'] ],
  ],
  [
    'id' => 'evt_103',
    'title' => 'Board Meeting',
    'description' => 'Planning meeting. Every other month, members only.',
    'date' => (new DateTimeImmutable('first day of January'))->format('Y-01-15'),
    'startTime' => '18:00',
    'endTime' => '19:30',
    'owner' => 'Taylor',
    'deputies' => [],
    'tags' => ['planning','internal'],
    'visibility' => 'Private',
    'image' => 'https://picsum.photos/seed/board/600/400',
    'sharedWith' => [],
    'recurrence' => [ 'freq' => 'Every other month', 'interval' => 2 ],
    'photos' => [],
    'comments' => [ ['author'=>'Taylor','text'=>'Agenda was emailed.','visibility'=>'Private'] ],
  ],
  [
    'id' => 'evt_104',
    'title' => 'Anniversary Party',
    'description' => 'Yearly celebration with live music and cake.',
    'date' => (new DateTimeImmutable('first day of January'))->format('Y-07-20'),
    'startTime' => '19:00',
    'endTime' => '23:00',
    'owner' => 'You',
    'deputies' => ['Alex'],
    'tags' => ['party','annual'],
    'visibility' => 'Public',
    'image' => 'https://picsum.photos/seed/anniv/600/400',
    'sharedWith' => ['Chris'],
    'recurrence' => [ 'freq' => 'Yearly' ],
    'photos' => [ ['url'=>'https://picsum.photos/seed/anniv1/400/300','visibility'=>'Public','tags'=>['cake']] ],
    'comments' => [],
  ],
  [
    'id' => 'evt_105',
    'title' => 'Private Planning Session',
    'description' => 'Confidential planning for upcoming festival. Restricted visibility.',
    'date' => (new DateTimeImmutable('friday this week'))->format('Y-m-d'),
    'startTime' => '14:00',
    'endTime' => '16:00',
    'owner' => 'You',
    'deputies' => ['Sam'],
    'tags' => ['festival','planning'],
    'visibility' => 'Private',
    'image' => 'https://picsum.photos/seed/plan/600/400',
    'sharedWith' => [],
    'photos' => [ ['url'=>'https://picsum.photos/seed/plan1/400/300','visibility'=>'Private','tags'=>['whiteboard']] ],
    'comments' => [ ['author'=>'You','text'=>'Notes will be uploaded later.','visibility'=>'Private'] ],
  ],
];

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events List/Add/Info – Mockup</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/events-mockup.css">
</head>
<body class="theme-dark">
  <header>
    <div class="container" style="align-items:center;display:flex;gap:1rem;justify-content:space-between">
      <h1><a href="#">Events List/Add/Info Mockup</a></h1>
      <div class="help-hint">Right-click (or control-click) items for options. Highlight text + Option-/ to search, Option-? for help.</div>
    </div>
  </header>

  <main class="container">
    <div class="mockup-wrap">
      <aside class="sticky-top">
        <div class="mini-cal" id="mini-cal"></div>
        <div class="top-side-media">
          <img id="top-image" src="https://picsum.photos/seed/default/600/400" alt="Selected event image">
          <div class="caption" id="top-image-cap">Selected event image</div>
        </div>
      </aside>

      <section>
        <div class="controls-bar">
          <input id="search-input" type="text" placeholder="Search/filter event name or #tag">
          <button class="button-thin" id="btn-filter">Filter</button>
          <button class="button-thin button-muted" id="btn-add-filter">Add Filter</button>
          <span class="viewer-role">
            <label for="viewer-role" class="help-hint">Viewer role</label>
            <select id="viewer-role">
              <option>Public</option>
              <option>Creator</option>
              <option>Deputy</option>
              <option>Shared</option>
              <option>Admin</option>
            </select>
          </span>
        </div>

        <div class="event-list" id="event-list"></div>

        <div class="addedit" id="addedit">
          <h3>Add/Edit Event (mock form)</h3>
          <form id="addedit-form">
            <div class="grid2">
              <div class="row">
                <label>Title <input type="text" name="title" required></label>
              </div>
              <div class="row">
                <label>Description <textarea name="description" rows="2" placeholder="Short description..."></textarea></label>
              </div>
              <div class="row">
                <label>Owner <input type="text" name="owner" id="owner"></label>
              </div>
              <div class="row">
                <label>Date <input type="date" name="date"></label>
              </div>
              <div class="row">
                <label>Start Time <input type="time" name="start_time"></label>
              </div>
              <div class="row">
                <label>End Time <input type="time" name="end_time"></label>
              </div>
              <div class="row">
                <label>Deputies <input type="text" name="deputies" placeholder="Alice, Bob"></label>
              </div>
              <div class="row">
                <label>Tags <input type="text" name="tags" placeholder="music, art"></label>
              </div>
              <div class="row">
                <label>Event Visibility
                  <select name="visibility" class="privacy-select">
                    <option>Public</option>
                    <option>Invite Only</option>
                    <option>Private</option>
                    <option>Event Specific</option>
                  </select>
                </label>
              </div>
            </div>

            <div class="section-title"><strong>Recurrence</strong></div>
            <div class="grid2">
              <div class="row">
                <label>Frequency
                  <select name="freq" class="recurrence-select">
                    <option>None</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                    <option>Every other month</option>
                    <option>Yearly</option>
                  </select>
                </label>
                <small>Examples: Every Monday 9:30–2am, Third Sunday monthly, Every other month, Yearly</small>
              </div>
              <div class="row">
                <label>Interval <input type="number" min="1" name="interval" placeholder="1"></label>
                <small>For Weekly/Monthly: 2 = every other</small>
              </div>
              <div class="row">
                <label>By Day (0-6, Sun=0) <input type="number" min="0" max="6" name="byday" placeholder="" ></label>
                <small>For Weekly/Monthly</small>
              </div>
              <div class="row">
                <label>Nth (1..4 or -1 last) <input type="number" name="nth" placeholder=""></label>
                <small>For Monthly patterns like "third sunday"</small>
              </div>
              <div class="row">
                <label>Until (end date) <input type="date" name="until"></label>
              </div>
            </div>

            <div class="section-title"><strong>Photos</strong></div>
            <div id="photo-grid-mini" class="photo-grid-mini"></div>

            <div class="section-title"><strong>Comments</strong></div>
            <div id="comment-list" class="item-list" style="margin-bottom:0.5rem"></div>
            <div class="grid2">
              <div class="row"><textarea id="comment-text" rows="2" placeholder="Add a comment..."></textarea></div>
              <div class="row">
                <label>Comment Visibility
                  <select id="comment-vis">
                    <option>Public</option>
                    <option>Invite Only</option>
                    <option>Private</option>
                    <option>Event Specific</option>
                  </select>
                </label>
                <button type="button" class="button-thin" id="add-comment-btn" style="margin-top:0.5rem">Add Comment</button>
              </div>
            </div>

            <div style="margin-top:0.75rem">
              <button class="button">Save (mock)</button>
            </div>
          </form>
        </div>
      </section>
    </div>
  </main>

  <div class="ctx-menu" id="ctx-menu"></div>

  <script>
  window.__EVENTS__ = <?php echo json_encode($events, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  </script>
  <script src="js/events-mockup.js"></script>
</body>
</html>
