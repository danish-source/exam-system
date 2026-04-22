<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$rows = $pdo->query(
    'SELECT a.id, a.started_at, a.finished_at, a.total_questions, a.correct_answers, a.wrong_answers,
            a.score_percent, a.passed, u.username, c.name AS category_name
     FROM exam_attempts a
     JOIN users u ON u.id = a.user_id
     JOIN categories c ON c.id = a.category_id
     ORDER BY a.started_at DESC
     LIMIT 200'
)->fetchAll();

$pageTitle = 'Exam attempts';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Exam attempts (recent)</h1>
    <p style="color:var(--muted)">Shows up to 200 recent attempts. Incomplete attempts have no finish time.</p>
    <?php if ($rows === []): ?>
        <p style="color:var(--muted)">No attempts yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Category</th>
                    <th>Started</th>
                    <th>Finished</th>
                    <th>Score</th>
                    <th>Pass</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int) $r['id'] ?></td>
                        <td><?= h($r['username']) ?></td>
                        <td><?= h($r['category_name']) ?></td>
                        <td><?= h($r['started_at']) ?></td>
                        <td><?= $r['finished_at'] ? h($r['finished_at']) : '—' ?></td>
                        <td><?= $r['finished_at'] ? h((string) $r['score_percent']) . '%' : '—' ?></td>
                        <td><?= $r['finished_at'] ? ((int) $r['passed'] === 1 ? 'Yes' : 'No') : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
