<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$error = '';
$setupError = '';
$codeValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeValue = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $codeValue = preg_replace('/[^A-Z0-9]/', '', $codeValue) ?? '';

    if ($codeValue === '') {
        $error = 'Enter the special test code.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, title, is_active FROM special_tests WHERE code = ? LIMIT 1');
            $stmt->execute([$codeValue]);
            $t = $stmt->fetch();
            if (!$t || (int) $t['is_active'] !== 1) {
                $error = 'Invalid code or test is disabled.';
            } else {
                $uid = (int) current_user()['id'];
                $attemptStmt = $pdo->prepare(
                    'SELECT id
                     FROM special_test_attempts
                     WHERE special_test_id = ? AND user_id = ? AND finished_at IS NOT NULL
                     LIMIT 1'
                );
                $attemptStmt->execute([(int) $t['id'], $uid]);
                $alreadyFinished = $attemptStmt->fetch();

                if ($alreadyFinished) {
                    $error = 'You have already attempted this special test.';
                } else {
                    $_SESSION['special_join_code'] = $codeValue;
                    redirect(url('user/special_test_start.php'));
                }
            }
        } catch (PDOException $e) {
            if (($e->getCode() ?? '') === '42S02') {
                $setupError = 'Special tests feature is not installed in the database yet.';
            } else {
                $error = 'Could not verify code.';
            }
        }
    }
}

$pageTitle = 'Special test';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Special test (join by code)</h1>
    <p style="color:var(--muted)">Enter the unique code provided by your teacher/admin to start the special test.</p>
    <?php if ($setupError): ?><div class="msg error"><?= h($setupError) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>

    <form method="post" action="">
        <div class="form-row">
            <label for="code">Special test code</label>
            <input type="text" id="code" name="code" required maxlength="16" autocomplete="off"
                   value="<?= h($codeValue) ?>" placeholder="e.g. AB12CD34EF">
        </div>
        <button type="submit" class="btn">Continue</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
