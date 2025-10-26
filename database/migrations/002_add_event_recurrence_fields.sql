-- Add recurrence fields to events table
ALTER TABLE events 
ADD COLUMN is_recurring BOOLEAN DEFAULT FALSE,
ADD COLUMN recurrence_pattern TEXT COMMENT 'JSON field storing recurrence details';
