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
$stmt = $pdo->prepare('SELECT id, code, title, duration_seconds, is_active FROM special_tests WHERE id = ?');
$stmt->execute([$testId]);
$test = $stmt->fetch();
if (!$test) {
    redirect(url('admin/special_tests.php'));
}

if (isset($_GET['toggle'])) {
    try {
        $pdo->prepare('UPDATE special_tests SET is_active = (1 - is_active) WHERE id = ?')->execute([$testId]);
        redirect(url('admin/special_test_questions.php?id=' . $testId));
    } catch (PDOException $e) {
        if (($e->getCode() ?? '') === '42S02') {
            $setupError = 'Special tests tables are not installed yet. Import database/special_tests_migration.sql (or re-import database/schema.sql).';
        } else {
            $setupError = 'Could not toggle status.';
        }
    }
}

if (isset($_GET['delete_test'])) {
    try {
        $pdo->prepare('DELETE FROM special_tests WHERE id = ?')->execute([$testId]);
        redirect(url('admin/special_tests.php?deleted=1'));
    } catch (PDOException $e) {
        if (($e->getCode() ?? '') === '42S02') {
            $setupError = 'Special tests tables are not installed yet. Import database/special_tests_migration.sql (or re-import database/schema.sql).';
        } else {
            $setupError = 'Could not delete special test.';
        }
    }
}

$qs = $pdo->prepare(
    'SELECT id, question_text, sort_order
     FROM special_test_questions
     WHERE special_test_id = ?
     ORDER BY sort_order, id'
);
$qs->execute([$testId]);
$questions = $qs->fetchAll();

$pageTitle = 'Special test questions';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Special test: <?= h($test['title']) ?></h1>
    <p style="color:var(--muted)">
        Code: <strong><?= h($test['code']) ?></strong> · Duration: <?= (int) floor(((int) $test['duration_seconds']) / 60) ?> min ·
        Status: <?= (int) $test['is_active'] === 1 ? 'Active' : 'Disabled' ?>
    </p>
    <?php if ($setupError): ?><div class="msg error"><?= h($setupError) ?></div><?php endif; ?>
    <p class="inline-actions">
        <a class="btn secondary" href="<?= h(url('admin/special_tests.php')) ?>">Back</a>
        <a class="btn secondary" href="<?= h(url('admin/special_test_results.php?id=' . $testId)) ?>">View results</a>
        <a class="btn" href="<?= h(url('admin/special_test_question_edit.php?test_id=' . $testId)) ?>">Add question</a>
        <a class="btn small danger" href="<?= h(url('admin/special_test_questions.php?id=' . $testId . '&toggle=1')) ?>"
           onclick="return confirm('Toggle active status?');">
            <?= (int) $test['is_active'] === 1 ? 'Disable' : 'Enable' ?>
        </a>
        <a class="btn small danger" href="<?= h(url('admin/special_test_questions.php?id=' . $testId . '&delete_test=1')) ?>"
           onclick="return confirm('Delete this special test? This will delete its questions and attempts too.');">
            Delete test
        </a>
    </p>
</div>

<div class="card">
    <h2>Questions</h2>
    <?php if ($questions === []): ?>
        <p style="color:var(--muted)">No questions yet. Add at least one question before sharing the code.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>ID</th>
                    <th>Preview</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?= (int) $q['sort_order'] ?></td>
                        <td><?= (int) $q['id'] ?></td>
                        <td><?php
                            $t = (string) $q['question_text'];
                            echo h(strlen($t) > 90 ? substr($t, 0, 90) . '…' : $t);
                        ?></td>
                        <td class="inline-actions">
                            <a class="btn small secondary" href="<?= h(url('admin/special_test_question_edit.php?test_id=' . $testId . '&id=' . (int) $q['id'])) ?>">Edit</a>
                            <a class="btn small danger" href="<?= h(url('admin/special_test_question_edit.php?test_id=' . $testId . '&delete=' . (int) $q['id'])) ?>"
                               onclick="return confirm('Delete this question?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
