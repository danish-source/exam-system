-- Remove all categories and questions from an existing database.
-- Also removes exam_responses and exam_attempts (they reference questions/categories).
-- Users (including admin) are kept.
-- Run: mysql -u root -p online_exam < database/clear_categories_and_questions.sql

USE online_exam;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE exam_responses;
TRUNCATE TABLE exam_attempts;
TRUNCATE TABLE questions;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;
