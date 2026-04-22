<?php
declare(strict_types=1);

/**
 * Mark attempt complete and store totals (call when last answer saved).
 */
function finalize_exam_attempt(PDO $pdo, int $attemptId, int $categoryId, array $questionIds): void
{
    $total = count($questionIds);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM exam_responses WHERE attempt_id = ? AND is_correct = 1');
    $stmt->execute([$attemptId]);
    $correct = (int) $stmt->fetchColumn();

    $wrong = $total - $correct;
    $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;

    $stmt = $pdo->prepare('SELECT pass_percentage FROM categories WHERE id = ?');
    $stmt->execute([$categoryId]);
    $passPct = (int) $stmt->fetchColumn();
    $passed = $score >= $passPct ? 1 : 0;

    $upd = $pdo->prepare(
        'UPDATE exam_attempts SET finished_at = NOW(), total_questions = ?, correct_answers = ?, wrong_answers = ?, score_percent = ?, passed = ? WHERE id = ?'
    );
    $upd->execute([$total, $correct, $wrong, $score, $passed, $attemptId]);
}
