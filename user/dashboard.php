<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$err = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$stmt = $pdo->query(
    'SELECT c.id, c.name, COUNT(q.id) AS q_count
     FROM categories c
     LEFT JOIN questions q ON q.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name'
);
$categories = $stmt->fetchAll();

$pageTitle = 'Your dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Exam categories</h1>
    <p style="color:var(--muted)">Choose a category to start an exam. Each question has its own time limit.</p>
    <?php if ($err): ?><div class="msg error"><?= h($err) ?></div><?php endif; ?>
</div>

<?php foreach ($categories as $c): ?>
    <div class="card">
        <h2><?= h($c['name']) ?></h2>
        <p>Questions available: <strong><?= (int) $c['q_count'] ?></strong></p>
        <?php if ((int) $c['q_count'] > 0): ?>
            <form method="post" action="<?= h(url('user/exam_start.php')) ?>" style="display:inline">
                <input type="hidden" name="category_id" value="<?= (int) $c['id'] ?>">
                <button type="submit" class="btn">Attempt</button>
            </form>
        <?php else: ?>
            <p style="color:var(--muted)">No questions yet.</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
