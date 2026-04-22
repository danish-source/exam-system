<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$code = strtoupper((string) ($_SESSION['special_join_code'] ?? ''));
if ($code === '') {
    redirect(url('user/special_test_join.php'));
}

unset($_SESSION['special_join_code']);

$error = '';
$setupError = '';

try {
    $stmt = $pdo->prepare('SELECT id, title, duration_seconds, is_active FROM special_tests WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $test = $stmt->fetch();
    if (!$test || (int) $test['is_active'] !== 1) {
        redirect(url('user/special_test_join.php'));
    }

    $q = $pdo->prepare('SELECT id FROM special_test_questions WHERE special_test_id = ? ORDER BY sort_order, id');
    $q->execute([(int) $test['id']]);
    $ids = $q->fetchAll(PDO::FETCH_COLUMN);
    if ($ids === []) {
        $error = 'This special test has no questions yet.';
    } else {
        $uid = (int) current_user()['id'];
        $attemptStmt = $pdo->prepare(
            'SELECT id, started_at, finished_at
             FROM special_test_attempts
             WHERE special_test_id = ? AND user_id = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        $attemptStmt->execute([(int) $test['id'], $uid]);
        $existingAttempt = $attemptStmt->fetch();

        if ($existingAttempt && $existingAttempt['finished_at'] !== null) {
            $error = 'You have already attempted this special test.';
        } else {
            if ($existingAttempt) {
                $attemptId = (int) $existingAttempt['id'];
                $startedEpoch = strtotime((string) $existingAttempt['started_at']);
                if ($startedEpoch === false) {
                    $startedEpoch = time();
                }
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO special_test_attempts (special_test_id, user_id, started_at, total_questions, correct_answers, wrong_answers, score_percent)
                     VALUES (?, ?, NOW(), ?, 0, 0, 0)'
                );
                $ins->execute([(int) $test['id'], $uid, count($ids)]);
                $attemptId = (int) $pdo->lastInsertId();
                $startedEpoch = time();
            }

            $_SESSION['special_exam'] = [
                'attempt_id' => $attemptId,
                'special_test_id' => (int) $test['id'],
                'question_ids' => array_map('intval', $ids),
                'current' => 0,
                'duration_seconds' => (int) $test['duration_seconds'],
                'started_epoch' => (int) $startedEpoch,
            ];

            redirect(url('user/special_test_exam.php'));
        }
    }
} catch (PDOException $e) {
    if (($e->getCode() ?? '') === '42S02') {
        $setupError = 'Special tests feature is not installed in the database yet.';
    } else {
        $error = 'Could not start special test.';
    }
}

$pageTitle = 'Starting special test';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Start special test</h1>
    <?php if ($setupError): ?><div class="msg error"><?= h($setupError) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>
    <p><a class="btn secondary" href="<?= h(url('user/special_test_join.php')) ?>">Back</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
