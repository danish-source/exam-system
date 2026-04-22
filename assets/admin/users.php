<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$users = $pdo->query(
    "SELECT id, username, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC"
)->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <h1>Registered users</h1>
    <?php if ($users === []): ?>
        <p style="color:var(--muted)">No student accounts yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td><?= h($u['username']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
