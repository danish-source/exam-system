<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$testId = (int) ($_GET['id'] ?? 0);
if ($testId <= 0) {
    redirect(url('admin/special_tests.php'));
}

$setupError = '';
$stmt = $pdo->prepare('SELECT id, code, title, duration_seconds FROM special_tests WHERE id = ?');
$stmt->execute([$testId]);
$test = $stmt->fetch();
if (!$test) {
    redirect(url('admin/special_tests.php'));
}

$rows = [];
try {
    $stmt = $pdo->prepare(
        'SELECT a.id, a.started_at, a.finished_at, a.total_questions, a.correct_answers, a.wrong_answers, a.score_percent,
                u.username, u.email
         FROM special_test_attempts a
         JOIN users u ON u.id = a.user_id
         WHERE a.special_test_id = ?
         ORDER BY a.started_at DESC
         LIMIT 500'
    );
    $stmt->execute([$testId]);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    if (($e->getCode() ?? '') === '42S02') {
        $setupError = 'Special tests tables are not installed yet. Import database/special_tests_migration.sql (or re-import database/schema.sql).';
    } else {
        $setupError = 'Could not load results.';
    }
}

$pageTitle = 'Special test results';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Results — <?= h($test['title']) ?></h1>
    <p style="color:var(--muted)">
        Code: <strong><?= h($test['code']) ?></strong> · Duration: <?= (int) floor(((int) $test['duration_seconds']) / 60) ?> min
    </p>
    <p class="inline-actions">
        <a class="btn secondary" href="<?= h(url('admin/special_test_questions.php?id=' . $testId)) ?>">Back</a>
        <a class="btn secondary" href="<?= h(url('admin/special_tests.php')) ?>">All special tests</a>
    </p>
    <?php if ($setupError): ?><div class="msg error"><?= h($setupError) ?></div><?php endif; ?>
</div>

<div class="card">
    <h2>Attempts</h2>
    <p style="color:var(--muted)">Shows up to 500 attempts (incomplete attempts have no finish time).</p>
    <?php if ($rows === []): ?>
        <p style="color:var(--muted)">No attempts yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Started</th>
                    <th>Finished</th>
                    <th>Correct</th>
                    <th>Wrong</th>
                    <th>Score</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int) $r['id'] ?></td>
                        <td><?= h($r['username']) ?></td>
                        <td><?= h($r['email']) ?></td>
                        <td><?= h($r['started_at']) ?></td>
                        <td><?= $r['finished_at'] ? h($r['finished_at']) : '—' ?></td>
                        <td><?= $r['finished_at'] ? (int) $r['correct_answers'] : '—' ?></td>
                        <td><?= $r['finished_at'] ? (int) $r['wrong_answers'] : '—' ?></td>
                        <td><?= $r['finished_at'] ? h((string) $r['score_percent']) . '%' : '—' ?></td>
                        <td>
                            <?php if ($r['finished_at']): ?>
                                <a class="btn small secondary" href="<?= h(url('admin/special_test_attempt.php?attempt=' . (int) $r['id'])) ?>">View</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

