<?php
/**
 * Database connection (PDO). Adjust credentials for your MySQL server.
 */
declare(strict_types=1);

$DB_HOST = getenv('EXAM_DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('EXAM_DB_NAME') ?: 'online_exam';
$DB_USER = getenv('EXAM_DB_USER') ?: 'root';
$DB_PASS = getenv('EXAM_DB_PASS') ?: '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    exit('Database connection failed. Check config/database.php and import database/schema.sql.');
}
