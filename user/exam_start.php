<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('user/dashboard.php'));
}

$cid = (int) ($_POST['category_id'] ?? 0);
$durationSeconds = 1200;
try {
    $catStmt = $pdo->prepare('SELECT duration_seconds FROM categories WHERE id = ?');
    $catStmt->execute([$cid]);
    $durationSeconds = (int) ($catStmt->fetchColumn() ?: 1200);
} catch (PDOException $e) {
    // If migration not imported yet, keep default duration.
    if (($e->getCode() ?? '') !== '42S22') {
        throw $e;
    }
}
$durationSeconds = max(60, $durationSeconds);

$stmt = $pdo->prepare('SELECT id FROM questions WHERE category_id = ? ORDER BY id');
$stmt->execute([$cid]);
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($ids === []) {
    $_SESSION['flash_error'] = 'This category has no questions.';
    redirect(url('user/dashboard.php'));
}

$uid = current_user()['id'];
$ins = $pdo->prepare(
    'INSERT INTO exam_attempts (user_id, category_id, started_at, total_questions, correct_answers, wrong_answers, score_percent, passed)
     VALUES (?, ?, NOW(), ?, 0, 0, 0, 0)'
);
$ins->execute([$uid, $cid, count($ids)]);
$attemptId = (int) $pdo->lastInsertId();

$_SESSION['exam'] = [
    'attempt_id'   => $attemptId,
    'category_id'  => $cid,
    'question_ids' => array_map('intval', $ids),
    'current'      => 0,
    'duration_seconds' => $durationSeconds,
    'started_epoch' => time(),
];

redirect(url('user/exam.php'));
