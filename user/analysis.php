<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$uid = current_user()['id'];
$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS total_tests, AVG(score_percent) AS avg_score, MAX(score_percent) AS best_score
     FROM exam_attempts
     WHERE user_id = ? AND finished_at IS NOT NULL'
);
$stmt->execute([$uid]);
$stats = $stmt->fetch();

$total = (int) ($stats['total_tests'] ?? 0);
$avg = $stats['avg_score'] !== null ? round((float) $stats['avg_score'], 2) : null;
$best = $stats['best_score'] !== null ? (float) $stats['best_score'] : null;

$pageTitle = 'Test analysis';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Your test analysis</h1>
    <p style="color:var(--muted)">Based on all completed exams stored in the database.</p>

    <div class="stats">
        <div class="stat">
            <div class="num"><?= $total ?></div>
            <div class="lbl">Total tests</div>
        </div>
        <div class="stat">
            <div class="num"><?= $avg !== null ? h((string) $avg) . '%' : '—' ?></div>
            <div class="lbl">Average score</div>
        </div>
        <div class="stat">
            <div class="num"><?= $best !== null ? h((string) $best) . '%' : '—' ?></div>
            <div class="lbl">Best score</div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
