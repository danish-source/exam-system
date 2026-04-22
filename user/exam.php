<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_lib.php';
require_user_role();

if (!isset($_SESSION['exam'])) {
    redirect(url('user/dashboard.php'));
}

$ex = $_SESSION['exam'];
$uid = current_user()['id'];

$stmt = $pdo->prepare('SELECT user_id, finished_at FROM exam_attempts WHERE id = ?');
$stmt->execute([$ex['attempt_id']]);
$row = $stmt->fetch();
if (!$row || (int) $row['user_id'] !== $uid || $row['finished_at'] !== null) {
    unset($_SESSION['exam']);
    redirect(url('user/dashboard.php'));
}

$startedEpoch = (int) ($ex['started_epoch'] ?? time());
$duration = max(60, (int) ($ex['duration_seconds'] ?? 0));
$endEpoch = $startedEpoch + $duration;
$remaining = $endEpoch - time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'next');
    $jumpIndex = (int) ($_POST['jump_index'] ?? -1);
    $qid = (int) ($_POST['question_id'] ?? 0);
    $chosen = $_POST['chosen'] ?? '';
    $chosen = is_string($chosen) && $chosen !== '' ? strtoupper($chosen) : null;
    if ($chosen !== null && !in_array($chosen, ['A', 'B', 'C', 'D'], true)) {
        $chosen = null;
    }

    $expected = $ex['question_ids'][$ex['current']] ?? null;
    if ($expected === null || $qid !== $expected) {
        redirect(url('user/exam.php'));
    }

    $qstmt = $pdo->prepare('SELECT correct_option FROM questions WHERE id = ? AND category_id = ?');
    $qstmt->execute([$qid, $ex['category_id']]);
    $qrow = $qstmt->fetch();
    if (!$qrow) {
        unset($_SESSION['exam']);
        redirect(url('user/dashboard.php'));
    }

    $correctOpt = $qrow['correct_option'];
    $isCorrect = ($chosen !== null && $chosen === $correctOpt) ? 1 : 0;

    if ($chosen !== null) {
        $ins = $pdo->prepare(
            'INSERT INTO exam_responses (attempt_id, question_id, chosen_option, is_correct, answered_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE chosen_option = VALUES(chosen_option), is_correct = VALUES(is_correct), answered_at = NOW()'
        );
        $ins->execute([$ex['attempt_id'], $qid, $chosen, $isCorrect]);
    }

    $totalQ = count($ex['question_ids']);

    if ($action === 'submit') {
        finalize_exam_attempt($pdo, $ex['attempt_id'], $ex['category_id'], $ex['question_ids']);
        $aid = $ex['attempt_id'];
        unset($_SESSION['exam']);
        redirect(url('user/result.php?attempt=' . $aid));
    }

    if ($action === 'prev') {
        $ex['current'] = max(0, (int) $ex['current'] - 1);
    } elseif ($action === 'jump') {
        if ($jumpIndex >= 0 && $jumpIndex < $totalQ) {
            $ex['current'] = $jumpIndex;
        }
    } else {
        $ex['current']++;
    }

    if ($ex['current'] >= $totalQ) {
        finalize_exam_attempt($pdo, $ex['attempt_id'], $ex['category_id'], $ex['question_ids']);
        $aid = $ex['attempt_id'];
        unset($_SESSION['exam']);
        redirect(url('user/result.php?attempt=' . $aid));
    }

    $_SESSION['exam'] = $ex;
    redirect(url('user/exam.php'));
}

if ($remaining <= 0) {
    finalize_exam_attempt($pdo, $ex['attempt_id'], $ex['category_id'], $ex['question_ids']);
    $aid = $ex['attempt_id'];
    unset($_SESSION['exam']);
    redirect(url('user/result.php?attempt=' . $aid));
}

$idx = $ex['current'];
$qid = $ex['question_ids'][$idx];
$stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
$stmt->execute([$qid]);
$q = $stmt->fetch();
if (!$q) {
    unset($_SESSION['exam']);
    redirect(url('user/dashboard.php'));
}

$selected = null;
try {
    $rstmt = $pdo->prepare('SELECT chosen_option FROM exam_responses WHERE attempt_id = ? AND question_id = ? LIMIT 1');
    $rstmt->execute([$ex['attempt_id'], $qid]);
    $selected = $rstmt->fetchColumn();
    $selected = is_string($selected) ? strtoupper($selected) : null;
} catch (PDOException $e) {
    $selected = null;
}

$total = count($ex['question_ids']);
$pageTitle = 'Exam — question ' . ($idx + 1) . ' of ' . $total;
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
        <div class="timer" id="timerBox" style="margin-bottom:0">Time left: <span id="timeLeft"></span></div>
        <button form="examForm" type="submit" class="btn danger"
                onclick="document.getElementById('examAction').value='submit'; return confirm('Submit the exam now?');">
            Submit exam
        </button>
    </div>
    <h1>Question <?= (int) ($idx + 1) ?> of <?= (int) $total ?></h1>
    <p><?= nl2br(h($q['question_text'])) ?></p>
    <form id="examForm" method="post" action="<?= h(url('user/exam.php')) ?>">
        <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
        <input type="hidden" name="action" id="examAction" value="next">
        <input type="hidden" name="jump_index" id="jumpIndex" value="-1">
        <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $letter => $text): ?>
            <label class="option-row" style="display:block;cursor:pointer">
                <input type="radio" name="chosen" value="<?= h($letter) ?>" <?= $selected === $letter ? 'checked' : '' ?>>
                <strong><?= h($letter) ?>.</strong> <?= h($text) ?>
            </label>
        <?php endforeach; ?>
        <div class="inline-actions" style="margin-top:1rem;gap:.35rem">
            <?php for ($i = 0; $i < $total; $i++): ?>
                <button type="submit" class="btn small <?= $i === $idx ? '' : 'secondary' ?>"
                        onclick="document.getElementById('examAction').value='jump';document.getElementById('jumpIndex').value='<?= (int) $i ?>'">
                    <?= (int) ($i + 1) ?>
                </button>
            <?php endfor; ?>
        </div>
        <p style="margin-top:1rem">
            <button type="submit" class="btn secondary" <?= $idx <= 0 ? 'disabled' : '' ?>
                    onclick="document.getElementById('examAction').value='prev'">Previous</button>
            <button type="submit" class="btn"
                    onclick="document.getElementById('examAction').value='next'">Next</button>
        </p>
    </form>
</div>
<script>
(function () {
    var left = <?= (int) $remaining ?>;
    var form = document.getElementById('examForm');
    var el = document.getElementById('timeLeft');
    var box = document.getElementById('timerBox');
    var action = document.getElementById('examAction');

    function pad2(n) {
        return String(n < 10 ? '0' + n : n);
    }
    function fmt(sec) {
        sec = Math.max(0, sec | 0);
        var h = Math.floor(sec / 3600);
        var m = Math.floor((sec % 3600) / 60);
        var s = sec % 60;
        return pad2(h) + ':' + pad2(m) + ':' + pad2(s);
    }

    if (el) el.textContent = fmt(left);
    var t = setInterval(function () {
        left--;
        if (el) el.textContent = fmt(left);
        if (left <= 60 && box) box.classList.add('warn');
        if (left <= 0) {
            clearInterval(t);
            if (action) action.value = 'submit';
            if (form) form.submit();
        }
    }, 1000);
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
