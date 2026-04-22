<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$aid = (int) ($_GET['attempt'] ?? 0);
if ($aid <= 0) {
    redirect(url('admin/special_tests.php'));
}

$stmt = $pdo->prepare(
    'SELECT a.*, u.username, u.email, t.title AS test_title, t.code AS test_code
     FROM special_test_attempts a
     JOIN users u ON u.id = a.user_id
     JOIN special_tests t ON t.id = a.special_test_id
     WHERE a.id = ? LIMIT 1'
);
$stmt->execute([$aid]);
$attempt = $stmt->fetch();
if (!$attempt || $attempt['finished_at'] === null) {
    redirect(url('admin/special_tests.php'));
}

$stmt = $pdo->prepare(
    'SELECT q.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option,
            r.chosen_option, r.is_correct
     FROM special_test_responses r
     JOIN special_test_questions q ON q.id = r.question_id
     WHERE r.attempt_id = ?
     ORDER BY q.sort_order, q.id'
);
$stmt->execute([$aid]);
$rows = $stmt->fetchAll();

$pageTitle = 'Special attempt';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Attempt #<?= (int) $attempt['id'] ?> — <?= h($attempt['test_title']) ?></h1>
    <p style="color:var(--muted)">
        Test code: <strong><?= h($attempt['test_code']) ?></strong> ·
        User: <strong><?= h($attempt['username']) ?></strong> (<?= h($attempt['email']) ?>) ·
        Started: <?= h($attempt['started_at']) ?> · Finished: <?= h($attempt['finished_at']) ?>
    </p>

    <div class="stats">
        <div class="stat"><div class="num"><?= (int) $attempt['total_questions'] ?></div><div class="lbl">Total</div></div>
        <div class="stat"><div class="num"><?= (int) $attempt['correct_answers'] ?></div><div class="lbl">Correct</div></div>
        <div class="stat"><div class="num"><?= (int) $attempt['wrong_answers'] ?></div><div class="lbl">Wrong</div></div>
        <div class="stat"><div class="num"><?= h((string) $attempt['score_percent']) ?>%</div><div class="lbl">Score</div></div>
    </div>

    <p><a class="btn secondary" href="<?= h(url('admin/special_test_results.php?id=' . (int) $attempt['special_test_id'])) ?>">Back to results</a></p>
</div>

<div class="card">
    <h2>Answer review (admin only)</h2>
    <?php if ($rows === []): ?>
        <p style="color:var(--muted)">No responses recorded.</p>
    <?php else: ?>
        <?php foreach ($rows as $i => $r): ?>
            <div class="review-item">
                <p><strong>Q<?= $i + 1 ?>.</strong> <?= nl2br(h((string) $r['question_text'])) ?></p>
                <p style="font-size:0.9rem;color:var(--muted)">User answer:
                    <?= $r['chosen_option'] ? h((string) $r['chosen_option']) : '<em>none / time out</em>' ?>
                    — Correct: <strong><?= h((string) $r['correct_option']) ?></strong>
                </p>
                <?php foreach (['A' => $r['option_a'], 'B' => $r['option_b'], 'C' => $r['option_c'], 'D' => $r['option_d']] as $L => $txt): ?>
                    <?php
                    $cls = '';
                    if ($L === $r['correct_option']) {
                        $cls = 'correct';
                    } elseif (($r['chosen_option'] ?? null) === $L && $L !== $r['correct_option']) {
                        $cls = 'wrong';
                    }
                    ?>
                    <div class="option-row <?= h($cls) ?>"><strong><?= h((string) $L) ?>.</strong> <?= h((string) $txt) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

