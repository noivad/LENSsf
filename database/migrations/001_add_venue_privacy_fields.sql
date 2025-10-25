-- Add privacy fields to venues table
ALTER TABLE venues 
ADD COLUMN IF NOT EXISTS is_private BOOLEAN DEFAULT FALSE COMMENT 'Private venues are custom addresses created by users',
ADD COLUMN IF NOT EXISTS is_public BOOLEAN DEFAULT TRUE COMMENT 'Can be toggled by venue owner to make venue public';
