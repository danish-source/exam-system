-- Online Exam System — MySQL schema
-- Import via: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS online_exam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_exam;

-- Registered users (role: user | admin)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Exam categories (Physics, Chemistry, etc.)
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL UNIQUE,
    pass_percentage TINYINT UNSIGNED NOT NULL DEFAULT 50,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 1200 COMMENT 'Overall exam duration in seconds (applies to whole category exam)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Questions with per-question timer (seconds)
CREATE TABLE questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(512) NOT NULL,
    option_b VARCHAR(512) NOT NULL,
    option_c VARCHAR(512) NOT NULL,
    option_d VARCHAR(512) NOT NULL,
    correct_option CHAR(1) NOT NULL COMMENT 'A, B, C, or D',
    timer_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- One row per completed (or abandoned) exam session
CREATE TABLE exam_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    total_questions SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    correct_answers SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    wrong_answers SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    score_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_user_finished (user_id, finished_at)
) ENGINE=InnoDB;

-- Per-question answers for an attempt
CREATE TABLE exam_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    chosen_option CHAR(1) NULL COMMENT 'NULL if time ran out with no selection',
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    answered_at DATETIME NOT NULL,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_attempt_question (attempt_id, question_id)
) ENGINE=InnoDB;

-- Special tests (shareable unique code, single overall timer)
CREATE TABLE special_tests (
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

CREATE TABLE special_test_questions (
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

CREATE TABLE special_test_attempts (
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

CREATE TABLE special_test_responses (
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

-- Seed default admin — username: admin | password: password (change after setup; use password_hash() in PHP)
-- Default admin — username: admin | password: password (change after setup)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@exam.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categories and questions: add via Admin panel or SQL (none seeded here)
