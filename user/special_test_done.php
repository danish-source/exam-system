<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$aid = (int) ($_GET['attempt'] ?? 0);
$uid = (int) current_user()['id'];

$stmt = $pdo->prepare(
    'SELECT a.finished_at, t.title
     FROM special_test_attempts a
     JOIN special_tests t ON t.id = a.special_test_id
     WHERE a.id = ? AND a.user_id = ? LIMIT 1'
);
$stmt->execute([$aid, $uid]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['finished_at'] === null) {
    redirect(url('user/dashboard.php'));
}

$pageTitle = 'Special test submitted';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Submitted — <?= h($attempt['title']) ?></h1>
    <p style="color:var(--muted)">Finished: <?= h($attempt['finished_at']) ?></p>
    <p style="color:var(--muted)">Your result is available to the admin only.</p>
    <p><a class="btn secondary" href="<?= h(url('user/dashboard.php')) ?>">Back to dashboard</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

