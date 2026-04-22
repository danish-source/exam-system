<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        redirect(url('admin/questions.php'));
    }
}

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
if ($cats === []) {
    exit('Add at least one category first.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $text = trim((string) ($_POST['question_text'] ?? ''));
    $a = trim((string) ($_POST['option_a'] ?? ''));
    $b = trim((string) ($_POST['option_b'] ?? ''));
    $c = trim((string) ($_POST['option_c'] ?? ''));
    $d = trim((string) ($_POST['option_d'] ?? ''));
    $correct = strtoupper(trim((string) ($_POST['correct_option'] ?? '')));
    $timer = (int) ($_POST['timer_seconds'] ?? 60);

    if ($categoryId <= 0 || $text === '' || $a === '' || $b === '' || $c === '' || $d === '') {
        $error = 'Fill all required fields.';
    } elseif (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        $error = 'Select a valid correct option.';
    } elseif ($timer < 5 || $timer > 3600) {
        $error = 'Timer must be between 5 and 3600 seconds.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE questions SET category_id=?, question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?, timer_seconds=? WHERE id=?'
            );
            $stmt->execute([$categoryId, $text, $a, $b, $c, $d, $correct, $timer, $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO questions (category_id, question_text, option_a, option_b, option_c, option_d, correct_option, timer_seconds)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$categoryId, $text, $a, $b, $c, $d, $correct, $timer]);
        }
        redirect(url('admin/questions.php'));
    }

    $row = [
        'id'             => $id,
        'category_id'    => $categoryId,
        'question_text'  => $text,
        'option_a'       => $a,
        'option_b'       => $b,
        'option_c'       => $c,
        'option_d'       => $d,
        'correct_option' => $correct,
        'timer_seconds'  => $timer,
    ];
}

$row = is_array($row) ? $row : [];

$editId = (int) ($row['id'] ?? 0);
$pageTitle = $editId > 0 ? 'Edit question' : 'Add question';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1><?= $editId > 0 ? 'Edit question' : 'Add question' ?></h1>
    <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="">
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <div class="form-row">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                        <?= (int) ($row['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
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
            <label for="correct_option">Correct answer</label>
            <select id="correct_option" name="correct_option" required>
                <?php foreach (['A', 'B', 'C', 'D'] as $L): ?>
                    <option value="<?= $L ?>" <?= (($row['correct_option'] ?? '') === $L) ? 'selected' : '' ?>><?= $L ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="timer_seconds">Timer (seconds)</label>
            <input type="number" id="timer_seconds" name="timer_seconds" required min="5" max="3600"
                   value="<?= h((string) ($row['timer_seconds'] ?? '60')) ?>">
        </div>
        <button type="submit" class="btn">Save</button>
        <a class="btn secondary" href="<?= h(url('admin/questions.php')) ?>" style="margin-left:0.5rem">Cancel</a>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
