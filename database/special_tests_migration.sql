-- Migration: add "special tests" feature tables
-- Import via phpMyAdmin: select database "online_exam" then Import this file

USE online_exam;

CREATE TABLE IF NOT EXISTS special_tests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code CHAR(10) NOT NULL UNIQUE COMMENT 'Unique join code',
    title VARCHAR(191) NOT NULL,
    duration_seconds INT UNSIGNED NOT NULL COMMENT 'Overall test duration in seconds',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_active (is_active, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS special_test_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    special_test_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(512) NOT NULL,
    option_b VARCHAR(512) NOT NULL,
    option_c VARCHAR(512) NOT NULL,
    option_d VARCHAR(512) NOT NULL,
    correct_option CHAR(1) NOT NULL COMMENT 'A, B, C, or D',
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (special_test_id) REFERENCES special_tests(id) ON DELETE CASCADE,
    INDEX idx_test_order (special_test_id, sort_order, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS special_test_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    special_test_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    total_questions SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    correct_answers SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    wrong_answers SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    score_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (special_test_id) REFERENCES special_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_special_user (special_test_id, user_id),
    INDEX idx_special_finished (special_test_id, finished_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS special_test_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    chosen_option CHAR(1) NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    answered_at DATETIME NOT NULL,
    FOREIGN KEY (attempt_id) REFERENCES special_test_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES special_test_questions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_special_attempt_question (attempt_id, question_id)
) ENGINE=InnoDB;

