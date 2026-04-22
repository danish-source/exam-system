<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(url('dashboard.php'));
}

$pageTitle = SITE_NAME;
$guestFullWidthMain = true;
$includeSiteFooter = true;
require __DIR__ . '/includes/header.php';
?>
<section class="landing-hero" aria-labelledby="landing-title">
    <h1 id="landing-title" class="landing-title"><?= h(SITE_NAME) ?></h1>
    <p class="landing-tagline">Register as a student to take timed exams by category. Build skills, track progress, and level up.</p>
    <a class="btn btn-landing-start" href="<?= h(url('login.php')) ?>">Let&apos;s Start</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
