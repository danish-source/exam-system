<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

$user = current_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $new_username = trim((string) ($_POST['username'] ?? ''));
        $new_email = trim((string) ($_POST['email'] ?? ''));
        
        if ($new_username === '' || $new_email === '') {
            $error = 'Username and email are required.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                $stmt->execute([$new_username, $new_email, $user['id']]);
                
                // Update session data
                $_SESSION['user']['username'] = $new_username;
                $_SESSION['user']['email'] = $new_email;
                $user = current_user();
                
                $success = 'Profile updated successfully.';
            } catch (PDOException $e) {
                if ((int) $e->errorInfo[1] === 1062) {
                    $error = 'Email already in use.';
                } else {
                    $error = 'Update failed.';
                }
            }
        }
    } elseif ($action === 'update_password') {
        $current_password = (string) ($_POST['current_password'] ?? '');
        $new_password = (string) ($_POST['new_password'] ?? '');
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');
        
        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            $error = 'All password fields are required.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            // Fetch the current password hash from database
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            
            if (!$row || !password_verify($current_password, $row['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                try {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([$new_hash, $user['id']]);
                    $success = 'Password updated successfully.';
                } catch (PDOException $e) {
                    $error = 'Password update failed.';
                }
            }
        }
    }
}

$pageTitle = 'My Profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="profile-container">
    <div class="card">
        <h1>My Profile</h1>
        <p style="color:var(--muted)">Manage your account information and security settings.</p>
        
        <?php if ($success): ?><div class="msg ok"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg error"><?= h($error) ?></div><?php endif; ?>
    </div>

    <!-- Profile Information Section -->
    <div class="card">
        <h2>Profile Information</h2>
        <form method="post" action="" class="profile-form">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required maxlength="64"
                       value="<?= h($user['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required maxlength="191"
                       value="<?= h($user['email'] ?? '') ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <!-- Password Change Section -->
    <div class="card">
        <h2>Change Password</h2>
        <p style="color:var(--muted)">Enter your current password and choose a new one.</p>
        <form method="post" action="" class="profile-form">
            <input type="hidden" name="action" value="update_password">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<style>
.profile-container {
    max-width: 600px;
}

.profile-form .form-group {
    margin-bottom: 1.25rem;
}

.profile-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.profile-form input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    font-family: inherit;
}

.profile-form input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-glow);
}

.btn-primary {
    background: var(--primary);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition-fast);
}

.btn-primary:hover {
    background: var(--primary-hover);
}
</style>

<?php require __DIR__ . '/../includes/footer.php'; ?>
