<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_user_role();

function finalize_special_attempt(PDO $pdo, int $attemptId, int $specialTestId, array $questionIds): void
{
    $total = count($questionIds);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM special_test_responses WHERE attempt_id = ? AND is_correct = 1');
    $stmt->execute([$attemptId]);
    $correct = (int) $stmt->fetchColumn();
    $wrong = $total - $correct;
    $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;

    $upd = $pdo->prepare(
        'UPDATE special_test_attempts
         SET finished_at = NOW(), total_questions = ?, correct_answers = ?, wrong_answers = ?, score_percent = ?
         WHERE id = ? AND special_test_id = ?'
    );
    $upd->execute([$total, $correct, $wrong, $score, $attemptId, $specialTestId]);
}

if (!isset($_SESSION['special_exam'])) {
    redirect(url('user/special_test_join.php'));
}

$ex = $_SESSION['special_exam'];
$uid = (int) current_user()['id'];

$stmt = $pdo->prepare('SELECT user_id, finished_at FROM special_test_attempts WHERE id = ? AND special_test_id = ?');
$stmt->execute([(int) $ex['attempt_id'], (int) $ex['special_test_id']]);
$arow = $stmt->fetch();
if (!$arow || (int) $arow['user_id'] !== $uid || $arow['finished_at'] !== null) {
    unset($_SESSION['special_exam']);
    redirect(url('user/special_test_join.php'));
}

$startedEpoch = (int) ($ex['started_epoch'] ?? time());
$duration = max(30, (int) ($ex['duration_seconds'] ?? 0));
$endEpoch = $startedEpoch + $duration;
$remaining = $endEpoch - time();

if ($remaining <= 0) {
    finalize_special_attempt($pdo, (int) $ex['attempt_id'], (int) $ex['special_test_id'], (array) $ex['question_ids']);
    $aid = (int) $ex['attempt_id'];
    unset($_SESSION['special_exam']);
    redirect(url('user/special_test_done.php?attempt=' . $aid));
}

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
        redirect(url('user/special_test_exam.php'));
    }

    $qstmt = $pdo->prepare('SELECT correct_option FROM special_test_questions WHERE id = ? AND special_test_id = ?');
    $qstmt->execute([$qid, (int) $ex['special_test_id']]);
    $qrow = $qstmt->fetch();
    if (!$qrow) {
        unset($_SESSION['special_exam']);
        redirect(url('user/special_test_join.php'));
    }

    $correctOpt = $qrow['correct_option'];
    $isCorrect = ($chosen !== null && $chosen === $correctOpt) ? 1 : 0;

    if ($chosen !== null) {
        $ins = $pdo->prepare(
            'INSERT INTO special_test_responses (attempt_id, question_id, chosen_option, is_correct, answered_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE chosen_option = VALUES(chosen_option), is_correct = VALUES(is_correct), answered_at = NOW()'
        );
        $ins->execute([(int) $ex['attempt_id'], $qid, $chosen, $isCorrect]);
    }

    $totalQ = count($ex['question_ids']);

    if ($action === 'submit') {
        finalize_special_attempt($pdo, (int) $ex['attempt_id'], (int) $ex['special_test_id'], (array) $ex['question_ids']);
        $aid = (int) $ex['attempt_id'];
        unset($_SESSION['special_exam']);
        redirect(url('user/special_test_done.php?attempt=' . $aid));
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
        finalize_special_attempt($pdo, (int) $ex['attempt_id'], (int) $ex['special_test_id'], (array) $ex['question_ids']);
        $aid = (int) $ex['attempt_id'];
        unset($_SESSION['special_exam']);
        redirect(url('user/special_test_done.php?attempt=' . $aid));
    }

    $_SESSION['special_exam'] = $ex;
    redirect(url('user/special_test_exam.php'));
}

$idx = (int) $ex['current'];
$qid = (int) $ex['question_ids'][$idx];
$stmt = $pdo->prepare('SELECT question_text, option_a, option_b, option_c, option_d FROM special_test_questions WHERE id = ?');
$stmt->execute([$qid]);
$q = $stmt->fetch();
if (!$q) {
    unset($_SESSION['special_exam']);
    redirect(url('user/special_test_join.php'));
}

$selected = null;
try {
    $rstmt = $pdo->prepare('SELECT chosen_option FROM special_test_responses WHERE attempt_id = ? AND question_id = ? LIMIT 1');
    $rstmt->execute([(int) $ex['attempt_id'], $qid]);
    $selected = $rstmt->fetchColumn();
    $selected = is_string($selected) ? strtoupper($selected) : null;
} catch (PDOException $e) {
    $selected = null;
}

$total = count($ex['question_ids']);
$pageTitle = 'Special test — question ' . ($idx + 1) . ' of ' . $total;
$hideAppChrome = true;
$fullScreenMode = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
        <div class="timer" id="timerBox" style="margin-bottom:0">Time left: <span id="timeLeft"></span></div>
        <button form="examForm" type="submit" class="btn danger"
                onclick="document.getElementById('examAction').value='submit'; return confirm('Submit the test now?');">
            Submit test
        </button>
    </div>
    <h1>Question <?= (int) ($idx + 1) ?> of <?= (int) $total ?></h1>
    <p><?= nl2br(h((string) $q['question_text'])) ?></p>
    <form id="examForm" method="post" action="<?= h(url('user/special_test_exam.php')) ?>">
        <input type="hidden" name="question_id" value="<?= (int) $qid ?>">
        <input type="hidden" name="action" id="examAction" value="next">
        <input type="hidden" name="jump_index" id="jumpIndex" value="-1">
        <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $letter => $text): ?>
            <label class="option-row" style="display:block;cursor:pointer">
                <input type="radio" name="chosen" value="<?= h($letter) ?>" <?= $selected === $letter ? 'checked' : '' ?>>
                <strong><?= h($letter) ?>.</strong> <?= h((string) $text) ?>
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

