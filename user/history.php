<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$uid = current_user()['id'];
$stmt = $pdo->prepare(
    'SELECT a.id, a.finished_at, a.score_percent, a.passed, c.name AS category_name
     FROM exam_attempts a
     JOIN categories c ON c.id = a.category_id
     WHERE a.user_id = ? AND a.finished_at IS NOT NULL
     ORDER BY a.finished_at DESC'
);
$stmt->execute([$uid]);
$list = $stmt->fetchAll();

$pageTitle = 'Exam history';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Past attempts</h1>
    <?php if ($list === []): ?>
        <p style="color:var(--muted)">No completed exams yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Score</th>
                    <th>Result</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $r): ?>
                    <tr>
                        <td><?= h($r['finished_at']) ?></td>
                        <td><?= h($r['category_name']) ?></td>
                        <td><?= h((string) $r['score_percent']) ?>%</td>
                        <td><?= (int) $r['passed'] === 1 ? '<span class="pass">Pass</span>' : '<span class="fail">Fail</span>' ?></td>
                        <td><a class="btn small secondary" href="<?= h(url('user/result.php?attempt=' . (int) $r['id'])) ?>">Review</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
