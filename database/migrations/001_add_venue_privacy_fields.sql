-- Add privacy fields to venues table
ALTER TABLE venues 
ADD COLUMN is_private BOOLEAN DEFAULT FALSE COMMENT 'Private venues are custom addresses created by users',
ADD COLUMN is_public BOOLEAN DEFAULT TRUE COMMENT 'Can be toggled by venue owner to make venue public';
