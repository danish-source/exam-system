<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

function generate_special_test_code(PDO $pdo, int $len = 10): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;
    for ($tries = 0; $tries < 25; $tries++) {
        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        $stmt = $pdo->prepare('SELECT id FROM special_tests WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }
    throw new RuntimeException('Could not generate a unique code.');
}

$error = '';
$setupError = '';

if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    if ($delId > 0) {
        try {
            $pdo->prepare('DELETE FROM special_tests WHERE id = ?')->execute([$delId]);
            redirect(url('admin/special_tests.php?deleted=1'));
        } catch (PDOException $e) {
            if (($e->getCode() ?? '') === '42S02') {
                $setupError = 'Special tests tables are not installed yet. Import database/special_tests_migration.sql (or re-import database/schema.sql).';
            } else {
                $error = 'Could not delete special test.';
            }
        }
    } else {
        redirect(url('admin/special_tests.php'));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $durationMinutes = (int) ($_POST['duration_minutes'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        $error = 'Title is required.';
    } elseif ($durationMinutes < 1 || $durationMinutes > 600) {
        $error = 'Duration must be between 1 and 600 minutes.';
    } else {
        try {
            $code = generate_special_test_code($pdo);
            $durationSeconds = $durationMinutes * 60;
            $adminId = (int) current_user()['id'];
            $stmt = $pdo->prepare(
                'INSERT INTO special_tests (code, title, duration_seconds, is_active, created_by) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([$code, $title, $durationSeconds, $isActive, $adminId]);
            redirect(url('admin/special_tests.php?ok=1'));
        } catch (PDOException $e) {
            if (($e->getCode() ?? '') === '42S02') {
                $setupError = 'Special tests tables are not installed yet. Import database/special_tests_migration.sql (or re-import database/schema.sql).';
            } else {
                $error = 'Could not create special test.';
            }
        }
    }
}

$tests = [];
try {
    $tests = $pdo->query(
        'SELECT t.id, t.code, t.title, t.duration_seconds, t.is_active, t.created_at,
                (SELECT COUNT(*) FROM special_test_questions q WHERE q.special_test_id = t.id) AS q_count
         FROM special_tests t
         ORDER BY t.created_at DESC
         LIMIT 200'
    )->fetchAll();
} catch (PDOException $e) {
    if (($e->getCode() ?? '') === '42S02') {
        $setupError = 'Special tests tables are not installed yet. Import database/special_tests_migration.sql (or re-import database/schema.sql).';
    } else {
        $setupError = 'Could not load special tests.';
    }
}

$pageTitle = 'Special tests';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Special tests</h1>
    <p style="color:var(--muted)">Admin creates a test with a unique ID (code). Students can attempt only if they know the code. Timer is for the whole test.</p>
    <?php if (isset($_GET['ok'])): ?><div class="msg ok">Special test created.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="msg ok">Special test deleted.</div><?php endif; ?>
    <?php if ($setupError): ?><div class="msg error"><?= h($setupError) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>

    <h2>Create special test</h2>
    <form method="post" action="">
        <div class="form-row">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required maxlength="191" value="<?= h($_POST['title'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="duration_minutes">Duration (minutes)</label>
            <input type="number" id="duration_minutes" name="duration_minutes" required min="1" max="600"
                   value="<?= h((string) ($_POST['duration_minutes'] ?? '20')) ?>">
        </div>
        <div class="form-row">
            <label style="display:flex; gap:.5rem; align-items:center">
                <input type="checkbox" name="is_active" value="1" <?= isset($_POST['is_active']) || $_SERVER['REQUEST_METHOD'] !== 'POST' ? 'checked' : '' ?>>
                Active (students can attempt)
            </label>
        </div>
        <button type="submit" class="btn">Create</button>
    </form>
</div>

<div class="card">
    <h2>Recent special tests</h2>
    <?php if ($tests === []): ?>
        <p style="color:var(--muted)">No special tests yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Duration</th>
                    <th>Questions</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tests as $t): ?>
                    <tr>
                        <td><?= (int) $t['id'] ?></td>
                        <td><strong><?= h($t['code']) ?></strong></td>
                        <td><?= h($t['title']) ?></td>
                        <td><?= (int) floor(((int) $t['duration_seconds']) / 60) ?> min</td>
                        <td><?= (int) $t['q_count'] ?></td>
                        <td><?= (int) $t['is_active'] === 1 ? 'Active' : 'Disabled' ?></td>
                        <td><?= h($t['created_at']) ?></td>
                        <td class="inline-actions">
                            <a class="btn small secondary" href="<?= h(url('admin/special_test_questions.php?id=' . (int) $t['id'])) ?>">Questions</a>
                            <a class="btn small secondary" href="<?= h(url('admin/special_test_results.php?id=' . (int) $t['id'])) ?>">Results</a>
                            <a class="btn small danger" href="<?= h(url('admin/special_tests.php?delete=' . (int) $t['id'])) ?>"
                               onclick="return confirm('Delete this special test? This will delete its questions and attempts too.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
