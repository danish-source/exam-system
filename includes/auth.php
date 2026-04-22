<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect(url('login.php'));
    }
}

function require_role(string $role): void
{
    require_login();
    $u = current_user();
    if (($u['role'] ?? '') !== $role) {
        redirect(url('dashboard.php'));
    }
}

function require_admin(): void
{
    require_role('admin');
}

function require_user_role(): void
{
    require_login();
    $u = current_user();
    if (($u['role'] ?? '') !== 'user') {
        redirect(url('admin/questions.php'));
    }
}

function login_user(array $row): void
{
    $_SESSION['user'] = [
        'id'       => (int) $row['id'],
        'username' => $row['username'],
        'email'    => $row['email'],
        'role'     => $row['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
