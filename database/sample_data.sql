-- Sample data for DeathGuild event
-- Tags: #goth #industrial #DeathGuild
-- Day: every Monday from 9:30PM–2AM

-- Insert the venue (DNA Lounge - famous venue in SF for DeathGuild)
INSERT INTO venues (name, description, address, city, state, zip_code, owner_name, deputies, open_times, tags)
VALUES (
    'DNA Lounge',
    'Iconic nightclub in San Francisco featuring live music, DJs, and legendary goth/industrial nights',
    '375 11th Street',
    'San Francisco',
    'CA',
    '94103',
    'DNA Lounge Management',
    'Jamie, Alex, Morgan',
    'Mon-Sat 9:00PM-2:00AM',
    'goth,industrial,club,nightlife,deathguild'
);

-- Get the venue ID (for events)
SET @venue_id = LAST_INSERT_ID();

-- Insert DeathGuild recurring event for several Mondays
-- First Monday
INSERT INTO events (title, description, event_date, event_time, venue_id, owner_name, deputies, tags)
VALUES (
    'DeathGuild',
    'The longest-running goth/industrial club night in the US! Join us every Monday for the best in dark alternative music. DJ Decay spins classic and new goth, industrial, EBM, and dark wave tracks. $5 before 10PM, $7 after.',
    DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) % 7 DAY),
    '21:30:00',
    @venue_id,
    'DJ Decay',
    'DJ Trauma, DJ Bleak',
    'goth,industrial,deathguild,darkwave,ebm'
);

-- Second Monday
INSERT INTO events (title, description, event_date, event_time, venue_id, owner_name, deputies, tags)
VALUES (
    'DeathGuild',
    'The longest-running goth/industrial club night in the US! Join us every Monday for the best in dark alternative music. DJ Decay spins classic and new goth, industrial, EBM, and dark wave tracks. $5 before 10PM, $7 after.',
    DATE_ADD(DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) % 7 DAY), INTERVAL 7 DAY),
    '21:30:00',
    @venue_id,
    'DJ Decay',
    'DJ Trauma, DJ Bleak',
    'goth,industrial,deathguild,darkwave,ebm'
);

-- Third Monday
INSERT INTO events (title, description, event_date, event_time, venue_id, owner_name, deputies, tags)
VALUES (
    'DeathGuild',
    'The longest-running goth/industrial club night in the US! Join us every Monday for the best in dark alternative music. DJ Decay spins classic and new goth, industrial, EBM, and dark wave tracks. $5 before 10PM, $7 after.',
    DATE_ADD(DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) % 7 DAY), INTERVAL 14 DAY),
    '21:30:00',
    @venue_id,
    'DJ Decay',
    'DJ Trauma, DJ Bleak',
    'goth,industrial,deathguild,darkwave,ebm'
);

-- Fourth Monday
INSERT INTO events (title, description, event_date, event_time, venue_id, owner_name, deputies, tags)
VALUES (
    'DeathGuild',
    'The longest-running goth/industrial club night in the US! Join us every Monday for the best in dark alternative music. DJ Decay spins classic and new goth, industrial, EBM, and dark wave tracks. $5 before 10PM, $7 after.',
    DATE_ADD(DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) % 7 DAY), INTERVAL 21 DAY),
    '21:30:00',
    @venue_id,
    'DJ Decay',
    'DJ Trauma, DJ Bleak',
    'goth,industrial,deathguild,darkwave,ebm'
);

-- Fifth Monday
INSERT INTO events (title, description, event_date, event_time, venue_id, owner_name, deputies, tags)
VALUES (
    'DeathGuild',
    'The longest-running goth/industrial club night in the US! Join us every Monday for the best in dark alternative music. DJ Decay spins classic and new goth, industrial, EBM, and dark wave tracks. $5 before 10PM, $7 after.',
    DATE_ADD(DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) % 7 DAY), INTERVAL 28 DAY),
    '21:30:00',
    @venue_id,
    'DJ Decay',
    'DJ Trauma, DJ Bleak',
    'goth,industrial,deathguild,darkwave,ebm'
);

-- Add some other sample venues
INSERT INTO venues (name, description, address, city, state, zip_code, owner_name, deputies, open_times, tags)
VALUES 
    ('The Chapel', 'Historic music venue and bar', '777 Valencia Street', 'San Francisco', 'CA', '94110', 'Chapel Management', 'Sam, Jesse', 'Mon-Sun 6:00PM-2:00AM', 'music,live,indie,rock'),
    ('Cat Club', 'Alternative dance club and live music venue', '1190 Folsom Street', 'San Francisco', 'CA', '94103', 'Cat Club Team', 'Riley, Casey', 'Fri-Sat 9:00PM-3:00AM', 'alternative,dance,club,indie');
