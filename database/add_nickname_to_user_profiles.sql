-- Add nickname field to user_profiles table
ALTER TABLE user_profiles ADD COLUMN nickname VARCHAR(50) AFTER last_name;
