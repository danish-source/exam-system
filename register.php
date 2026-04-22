<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(url('dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $pass     = (string) ($_POST['password'] ?? '');
    $pass2    = (string) ($_POST['password2'] ?? '');

    if ($username === '' || $email === '' || $pass === '') {
        $error = 'Please fill all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?,?,?,?)');
        try {
            $stmt->execute([$username, $email, $hash, 'user']);
            redirect(url('login.php?registered=1'));
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                $error = 'Username or email already exists.';
            } else {
                $error = 'Registration failed.';
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Join Skill UpLearner</h1>
            <p>Create your account to start learning</p>
        </div>
        <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" action="" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required maxlength="64"
                       value="<?= h($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required maxlength="191"
                       value="<?= h($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password2">Confirm Password</label>
                <input type="password" id="password2" name="password2" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <div class="auth-footer">
            <p>Already have an account? <a href="<?= h(url('login.php')) ?>">Login here</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
