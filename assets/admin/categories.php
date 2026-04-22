<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$error = '';
$setupError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $pass = (int) ($_POST['pass_percentage'] ?? 50);
    $durationMinutes = (int) ($_POST['duration_minutes'] ?? 20);
    $durationSeconds = $durationMinutes * 60;
    if ($name === '') {
        $error = 'Category name is required.';
    } elseif ($pass < 0 || $pass > 100) {
        $error = 'Pass percentage must be 0–100.';
    } elseif ($durationMinutes < 1 || $durationMinutes > 600) {
        $error = 'Duration must be between 1 and 600 minutes.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO categories (name, pass_percentage, duration_seconds) VALUES (?, ?, ?)');
        try {
            $stmt->execute([$name, $pass, $durationSeconds]);
            redirect(url('admin/categories.php?ok=1'));
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                $error = 'That category name already exists.';
            } elseif (($e->getCode() ?? '') === '42S22') {
                $setupError = 'Category exam duration is not installed yet. Import database/category_duration_migration.sql.';
            } else {
                $error = 'Could not add category.';
            }
        }
    }
}

$list = [];
try {
    $list = $pdo->query('SELECT id, name, pass_percentage, duration_seconds FROM categories ORDER BY name')->fetchAll();
} catch (PDOException $e) {
    if (($e->getCode() ?? '') === '42S22') {
        $setupError = 'Category exam duration is not installed yet. Import database/category_duration_migration.sql.';
        $list = $pdo->query('SELECT id, name, pass_percentage FROM categories ORDER BY name')->fetchAll();
    } else {
        throw $e;
    }
}

$pageTitle = 'Categories';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Categories</h1>
    <?php if (isset($_GET['ok'])): ?><div class="msg ok">Category added.</div><?php endif; ?>
    <?php if ($setupError): ?><div class="msg error"><?= h($setupError) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>

    <h2>Add category</h2>
    <form method="post" action="">
        <div class="form-row">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required maxlength="128" value="<?= h($_POST['name'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="pass_percentage">Pass mark (%)</label>
            <input type="number" id="pass_percentage" name="pass_percentage" min="0" max="100"
                   value="<?= h((string) ($_POST['pass_percentage'] ?? '50')) ?>">
        </div>
        <div class="form-row">
            <label for="duration_minutes">Duration (minutes) — whole exam</label>
            <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="600"
                   value="<?= h((string) ($_POST['duration_minutes'] ?? '20')) ?>">
        </div>
        <button type="submit" class="btn">Add</button>
    </form>
</div>

<div class="card">
    <h2>Existing categories</h2>
    <table>
        <thead><tr><th>Name</th><th>Pass %</th><th>Duration</th></tr></thead>
        <tbody>
            <?php foreach ($list as $c): ?>
                <tr>
                    <td><?= h($c['name']) ?></td>
                    <td><?= (int) $c['pass_percentage'] ?></td>
                    <td><?= isset($c['duration_seconds']) ? ((int) floor(((int) ($c['duration_seconds'] ?? 1200)) / 60) . ' min') : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
