<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$aid = (int) ($_GET['attempt'] ?? 0);
$uid = current_user()['id'];

$stmt = $pdo->prepare(
    'SELECT a.*, c.name AS category_name, c.pass_percentage
     FROM exam_attempts a
     JOIN categories c ON c.id = a.category_id
     WHERE a.id = ? AND a.user_id = ? LIMIT 1'
);
$stmt->execute([$aid, $uid]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['finished_at'] === null) {
    redirect(url('user/dashboard.php'));
}

$stmt = $pdo->prepare(
    'SELECT q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option,
            r.chosen_option, r.is_correct
     FROM exam_responses r
     JOIN questions q ON q.id = r.question_id
     WHERE r.attempt_id = ?
     ORDER BY q.id'
);
$stmt->execute([$aid]);
$rows = $stmt->fetchAll();

$totalQuestions = (int) $attempt['total_questions'];
$correctAnswers = (int) $attempt['correct_answers'];
$attemptedQuestions = count($rows);
$wrongAnswers = max(0, $attemptedQuestions - $correctAnswers);
$notAttemptedQuestions = max(0, $totalQuestions - $attemptedQuestions);

$pageTitle = 'Exam result';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Result — <?= h($attempt['category_name']) ?></h1>
    <p style="color:var(--muted)">Finished: <?= h($attempt['finished_at']) ?></p>

    <div class="stats">
        <div class="stat"><div class="num"><?= $totalQuestions ?></div><div class="lbl">Total</div></div>
        <div class="stat"><div class="num"><?= $attemptedQuestions ?></div><div class="lbl">Attempted</div></div>
        <div class="stat"><div class="num"><?= $correctAnswers ?></div><div class="lbl">Correct</div></div>
        <div class="stat"><div class="num"><?= $wrongAnswers ?></div><div class="lbl">Wrong</div></div>
        <div class="stat"><div class="num"><?= $notAttemptedQuestions ?></div><div class="lbl">Not attempted</div></div>
        <div class="stat"><div class="num"><?= h((string) $attempt['score_percent']) ?>%</div><div class="lbl">Score</div></div>
    </div>

    <p>Pass mark for this category: <strong><?= (int) $attempt['pass_percentage'] ?>%</strong></p>
    <p>Status:
        <?php if ((int) $attempt['passed'] === 1): ?>
            <span class="pass">Pass</span>
        <?php else: ?>
            <span class="fail">Fail</span>
        <?php endif; ?>
    </p>

    <p><a class="btn secondary" href="<?= h(url('user/dashboard.php')) ?>">Back to dashboard</a></p>
</div>

<div class="card">
    <h2>Review (correct answers)</h2>
    <?php foreach ($rows as $i => $r): ?>
        <div class="review-item">
            <p><strong>Q<?= $i + 1 ?>.</strong> <?= nl2br(h($r['question_text'])) ?></p>
            <p style="font-size:0.9rem;color:var(--muted)">Your answer:
                <?= $r['chosen_option'] ? h($r['chosen_option']) : '<em>none / time out</em>' ?>
                — Correct: <strong><?= h($r['correct_option']) ?></strong>
            </p>
            <?php foreach (['A' => $r['option_a'], 'B' => $r['option_b'], 'C' => $r['option_c'], 'D' => $r['option_d']] as $L => $txt): ?>
                <?php
                $cls = '';
                if ($L === $r['correct_option']) {
                    $cls = 'correct';
                } elseif ($r['chosen_option'] === $L && $L !== $r['correct_option']) {
                    $cls = 'wrong';
                }
                ?>
                <div class="option-row <?= h($cls) ?>"><strong><?= h($L) ?>.</strong> <?= h($txt) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
