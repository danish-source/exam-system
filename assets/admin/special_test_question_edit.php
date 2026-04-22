<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$testId = (int) ($_GET['test_id'] ?? $_POST['test_id'] ?? 0);
if ($testId <= 0) {
    redirect(url('admin/special_tests.php'));
}

$stmt = $pdo->prepare('SELECT id, title FROM special_tests WHERE id = ?');
$stmt->execute([$testId]);
$test = $stmt->fetch();
if (!$test) {
    redirect(url('admin/special_tests.php'));
}

if (isset($_GET['delete'])) {
    $qid = (int) $_GET['delete'];
    if ($qid > 0) {
        $pdo->prepare('DELETE FROM special_test_questions WHERE id = ? AND special_test_id = ?')->execute([$qid, $testId]);
    }
    redirect(url('admin/special_test_questions.php?id=' . $testId));
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$row = null;
if ($id > 0) {
    $s = $pdo->prepare('SELECT * FROM special_test_questions WHERE id = ? AND special_test_id = ?');
    $s->execute([$id, $testId]);
    $row = $s->fetch();
    if (!$row) {
        redirect(url('admin/special_test_questions.php?id=' . $testId));
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $text = trim((string) ($_POST['question_text'] ?? ''));
    $a = trim((string) ($_POST['option_a'] ?? ''));
    $b = trim((string) ($_POST['option_b'] ?? ''));
    $c = trim((string) ($_POST['option_c'] ?? ''));
    $d = trim((string) ($_POST['option_d'] ?? ''));
    $correct = strtoupper(trim((string) ($_POST['correct_option'] ?? '')));
    $order = (int) ($_POST['sort_order'] ?? 1);

    if ($text === '' || $a === '' || $b === '' || $c === '' || $d === '') {
        $error = 'Fill all required fields.';
    } elseif (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        $error = 'Select a valid correct option.';
    } elseif ($order < 1 || $order > 1000000) {
        $error = 'Order must be a positive number.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE special_test_questions
                 SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?, sort_order=?
                 WHERE id=? AND special_test_id=?'
            );
            $stmt->execute([$text, $a, $b, $c, $d, $correct, $order, $id, $testId]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO special_test_questions (special_test_id, question_text, option_a, option_b, option_c, option_d, correct_option, sort_order)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$testId, $text, $a, $b, $c, $d, $correct, $order]);
        }
        redirect(url('admin/special_test_questions.php?id=' . $testId));
    }

    $row = [
        'id' => $id,
        'question_text' => $text,
        'option_a' => $a,
        'option_b' => $b,
        'option_c' => $c,
        'option_d' => $d,
        'correct_option' => $correct,
        'sort_order' => $order,
    ];
}

$row = is_array($row) ? $row : [];
$editId = (int) ($row['id'] ?? 0);
$pageTitle = $editId > 0 ? 'Edit special question' : 'Add special question';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1><?= $editId > 0 ? 'Edit question' : 'Add question' ?></h1>
    <p style="color:var(--muted)">Special test: <?= h($test['title']) ?></p>
    <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="">
        <input type="hidden" name="test_id" value="<?= (int) $testId ?>">
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <div class="form-row">
            <label for="sort_order">Order</label>
            <input type="number" id="sort_order" name="sort_order" required min="1" max="1000000"
                   value="<?= h((string) ($row['sort_order'] ?? '1')) ?>">
        </div>
        <div class="form-row">
            <label for="question_text">Question</label>
            <textarea id="question_text" name="question_text" required><?= h($row['question_text'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <label for="option_a">Option A</label>
            <input type="text" id="option_a" name="option_a" required maxlength="512" value="<?= h($row['option_a'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="option_b">Option B</label>
            <input type="text" id="option_b" name="option_b" required maxlength="512" value="<?= h($row['option_b'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="option_c">Option C</label>
            <input type="text" id="option_c" name="option_c" required maxlength="512" value="<?= h($row['option_c'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="option_d">Option D</label>
            <input type="text" id="option_d" name="option_d" required maxlength="512" value="<?= h($row['option_d'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="correct_option">Correct answer (admin only)</label>
            <select id="correct_option" name="correct_option" required>
                <?php foreach (['A', 'B', 'C', 'D'] as $L): ?>
                    <option value="<?= $L ?>" <?= (($row['correct_option'] ?? '') === $L) ? 'selected' : '' ?>><?= $L ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Save</button>
        <a class="btn secondary" href="<?= h(url('admin/special_test_questions.php?id=' . $testId)) ?>" style="margin-left:0.5rem">Cancel</a>
    </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
