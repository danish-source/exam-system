<?php
declare(strict_types=1);
if (!isset($pageTitle)) {
    $pageTitle = SITE_NAME;
}
$u = function_exists('current_user') ? current_user() : null;
$guestLayout = $u === null;
$hideAppChrome = !empty($hideAppChrome ?? false);
$fullScreenMode = !empty($fullScreenMode ?? false);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePrefix = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
if ($basePrefix !== '' && str_starts_with($requestPath, $basePrefix)) {
    $requestPath = substr($requestPath, strlen($basePrefix)) ?: '/';
}
$requestPath = '/' . ltrim($requestPath, '/');

$isHomePage = $requestPath === '/index.php' || $requestPath === '/';
if ($guestLayout) {
    $pageBgClass = $isHomePage ? 'page-bg-home' : 'page-bg-guest';
} else {
    $pageBgClass = (($u['role'] ?? '') === 'admin') ? 'page-bg-admin' : 'page-bg-user';
}

/**
 * @param non-empty-string $relativePath path relative to app root, e.g. admin/questions.php
 */
function nav_active(string $relativePath): string
{
    global $requestPath;
    $target = url($relativePath);
    $target = '/' . ltrim($target, '/');
    return ($requestPath === $target) ? ' is-active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(url('assets/css/style.css')) ?>">
</head>
<body class="<?= h(trim(($guestLayout ? 'is-guest-layout ' : '') . $pageBgClass . ($fullScreenMode ? ' exam-fullscreen' : ''))) ?>">
<div class="app-shell<?= $guestLayout ? ' app-shell--guest' : '' ?>">
    <?php if (!$guestLayout && !$hideAppChrome): ?>
    <aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
        <div class="sidebar-top">
            <a class="sidebar-brand" href="<?= h(url(($u['role'] ?? '') === 'admin' ? 'admin/questions.php' : 'dashboard.php')) ?>">
                <span class="sidebar-brand-mark" aria-hidden="true"></span>
                <span class="sidebar-brand-text"><?= h(SITE_NAME) ?></span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <?php if (($u['role'] ?? '') === 'admin'): ?>
                <a class="sidebar-link<?= nav_active('admin/questions.php') ?>" href="<?= h(url('admin/questions.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Questions
                </a>
                <a class="sidebar-link<?= nav_active('admin/special_tests.php') ?>" href="<?= h(url('admin/special_tests.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Special Tests
                </a>
                <a class="sidebar-link<?= nav_active('admin/categories.php') ?>" href="<?= h(url('admin/categories.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Categories
                </a>
                <a class="sidebar-link<?= nav_active('admin/users.php') ?>" href="<?= h(url('admin/users.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Users
                </a>
                <a class="sidebar-link<?= nav_active('admin/attempts.php') ?>" href="<?= h(url('admin/attempts.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Attempts
                </a>
            <?php else: ?>
                <a class="sidebar-link<?= nav_active('user/dashboard.php') ?>" href="<?= h(url('user/dashboard.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Dashboard
                </a>
                <a class="sidebar-link<?= nav_active('user/special_test_join.php') ?>" href="<?= h(url('user/special_test_join.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Special Test (Code)
                </a>
                <a class="sidebar-link<?= nav_active('user/history.php') ?>" href="<?= h(url('user/history.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> History
                </a>
                <a class="sidebar-link<?= nav_active('user/analysis.php') ?>" href="<?= h(url('user/analysis.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Analysis
                </a>
                <a class="sidebar-link<?= nav_active('user/profile.php') ?>" href="<?= h(url('user/profile.php')) ?>">
                    <span class="sidebar-link-icon" aria-hidden="true"></span> Profile
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <span class="sidebar-user-name"><?= h($u['username']) ?></span>
                <span class="sidebar-user-role"><?= h(($u['role'] ?? '') === 'admin' ? 'Administrator' : 'Student') ?></span>
            </div>
            <a class="sidebar-link sidebar-link-logout" href="<?= h(url('logout.php')) ?>">
                <span class="sidebar-link-icon" aria-hidden="true"></span> Log out
            </a>
        </div>
    </aside>
    <?php endif; ?>

    <div class="main-area">
        <?php if ($guestLayout): ?>
        <header class="guest-topbar">
            <div class="guest-topbar-inner">
                <nav class="guest-topbar-actions" aria-label="Account">
                    <a class="guest-topbar-link" href="<?= h(url('index.php')) ?>">Home</a>
                    <a class="guest-topbar-link" href="<?= h(url('index.php')) ?>#contact">Contact us</a>
                    <a class="guest-topbar-link<?= nav_active('login.php') ?>" href="<?= h(url('login.php')) ?>">Login</a>
                    <a class="btn btn-topbar-register<?= nav_active('register.php') ?>" href="<?= h(url('register.php')) ?>">Register</a>
                </nav>
            </div>
        </header>
        <?php elseif (!$hideAppChrome): ?>
        <header class="topbar">
            <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-expanded="true" aria-controls="app-sidebar" aria-label="Hide menu">
                <span class="sidebar-toggle-bar" aria-hidden="true"></span>
                <span class="sidebar-toggle-bar" aria-hidden="true"></span>
                <span class="sidebar-toggle-bar" aria-hidden="true"></span>
            </button>
            <span class="topbar-brand-mobile"><?= h(SITE_NAME) ?></span>
        </header>
        <?php endif; ?>
        <main class="wrap<?= $guestLayout && empty($guestFullWidthMain ?? false) ? ' wrap--guest' : '' ?><?= $fullScreenMode ? ' wrap--full' : '' ?>">
