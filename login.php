<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(url('dashboard.php'));
}

$error = '';
$ok = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Enter email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['password_hash'])) {
            login_user($row);
            redirect(url('dashboard.php'));
        }
        $error = 'Invalid credentials.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>
        <?php if ($ok): ?><div class="msg ok">Registration successful. You can log in now.</div><?php endif; ?>
        <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" action="" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       value="<?= h($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="auth-footer">
            <p>Don't have an account? <a href="<?= h(url('register.php')) ?>">Register here</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
