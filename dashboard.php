<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$u = current_user();
if (($u['role'] ?? '') === 'admin') {
    redirect(url('admin/questions.php'));
}
redirect(url('user/dashboard.php'));
