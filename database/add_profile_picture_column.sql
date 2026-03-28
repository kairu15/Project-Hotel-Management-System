-- Add profile_picture column to users table
-- Run this SQL in your MySQL database

ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER country;
