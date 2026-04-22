-- Migration: add overall timer to category exams
-- Import via phpMyAdmin: select database "online_exam" then Import this file

USE online_exam;

ALTER TABLE categories
  ADD COLUMN duration_seconds INT UNSIGNED NOT NULL DEFAULT 1200
  COMMENT 'Overall exam duration in seconds (applies to whole category exam)';

