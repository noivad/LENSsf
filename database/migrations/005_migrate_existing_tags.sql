-- Migration script to move existing tags from JSON columns to universal tags tables
-- This is a data migration that preserves existing tags

-- Step 1: Insert all unique tags from venues into tags table
INSERT IGNORE INTO tags (name)
SELECT DISTINCT LOWER(TRIM(tag_value)) as tag_name
FROM (
    SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', idx, ']'))) as tag_value
    FROM venues
    CROSS JOIN (
        SELECT 0 as idx UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
    ) numbers
    WHERE tags IS NOT NULL 
    AND tags != ''
    AND tags LIKE '[%'
    AND JSON_VALID(tags)
    AND JSON_EXTRACT(tags, CONCAT('$[', idx, ']')) IS NOT NULL
) venue_tags_data
WHERE tag_value IS NOT NULL AND TRIM(tag_value) != '';

-- Step 2: Insert all unique tags from events into tags table
INSERT IGNORE INTO tags (name)
SELECT DISTINCT LOWER(TRIM(tag_value)) as tag_name
FROM (
    SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', idx, ']'))) as tag_value
    FROM events
    CROSS JOIN (
        SELECT 0 as idx UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
    ) numbers
    WHERE tags IS NOT NULL 
    AND tags != ''
    AND tags LIKE '[%'
    AND JSON_VALID(tags)
    AND JSON_EXTRACT(tags, CONCAT('$[', idx, ']')) IS NOT NULL
) event_tags_data
WHERE tag_value IS NOT NULL AND TRIM(tag_value) != '';

-- Step 3: Create junction table entries for venues
INSERT IGNORE INTO venue_tags (venue_id, tag_id)
SELECT v.id, t.id
FROM venues v
CROSS JOIN (
    SELECT 0 as idx UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
) numbers
INNER JOIN tags t ON t.name = LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(v.tags, CONCAT('$[', numbers.idx, ']')))))
WHERE v.tags IS NOT NULL 
AND v.tags != ''
AND v.tags LIKE '[%'
AND JSON_VALID(v.tags)
AND JSON_EXTRACT(v.tags, CONCAT('$[', numbers.idx, ']')) IS NOT NULL;

-- Step 4: Create junction table entries for events
INSERT IGNORE INTO event_tags (event_id, tag_id)
SELECT e.id, t.id
FROM events e
CROSS JOIN (
    SELECT 0 as idx UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
) numbers
INNER JOIN tags t ON t.name = LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(e.tags, CONCAT('$[', numbers.idx, ']')))))
WHERE e.tags IS NOT NULL 
AND e.tags != ''
AND e.tags LIKE '[%'
AND JSON_VALID(e.tags)
AND JSON_EXTRACT(e.tags, CONCAT('$[', numbers.idx, ']')) IS NOT NULL;
