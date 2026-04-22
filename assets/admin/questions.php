<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$filter = (int) ($_GET['category'] ?? 0);
$sql = 'SELECT q.id, q.question_text, q.timer_seconds, c.name AS category_name, c.id AS category_id
        FROM questions q
        JOIN categories c ON c.id = q.category_id';
$args = [];
if ($filter > 0) {
    $sql .= ' WHERE q.category_id = ?';
    $args[] = $filter;
}
$sql .= ' ORDER BY c.name, q.id';

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$questions = $stmt->fetchAll();

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Questions';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Questions</h1>
    <p class="inline-actions">
        <a class="btn" href="<?= h(url('admin/question_edit.php')) ?>">Add question</a>
    </p>
    <form method="get" action="" style="margin:1rem 0">
        <label for="category">Filter by category</label>
        <select name="category" id="category" onchange="this.form.submit()">
            <option value="0">All</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filter === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn small">Filter</button></noscript>
    </form>

    <?php if ($questions === []): ?>
        <p style="color:var(--muted)">No questions found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Preview</th>
                    <th>Timer (s)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?= (int) $q['id'] ?></td>
                        <td><?= h($q['category_name']) ?></td>
                        <td><?php
                            $t = $q['question_text'];
                            echo h(strlen($t) > 80 ? substr($t, 0, 80) . '…' : $t);
                            ?></td>
                        <td><?= (int) $q['timer_seconds'] ?></td>
                        <td class="inline-actions">
                            <a class="btn small secondary" href="<?= h(url('admin/question_edit.php?id=' . (int) $q['id'])) ?>">Edit</a>
                            <a class="btn small danger" href="<?= h(url('admin/question_delete.php?id=' . (int) $q['id'])) ?>"
                               onclick="return confirm('Delete this question?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
