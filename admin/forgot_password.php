
<?php
require_once '../config/database.php';
session_start();

$questions = [
    'What is your favourite food?',
    'What is your favourite movie?',
    'What is your favourite game?',
    'What was your first crush name?',
    'What is your hobby?'
];

// Normalize answers before verification
function normalizeSecurityAnswer(string $answer): string {
    $answer = trim($answer);
    $answer = preg_replace('/\s+/', ' ', $answer);
    return mb_strtolower($answer, 'UTF-8');
}

$error = '';

$step = $_SESSION['recovery_admin_id'] ?? null;

// Recovery session expires after 10 minutes
if (
    isset($_SESSION['recovery_started']) &&
    time() - $_SESSION['recovery_started'] > 600
) {
    unset(
        $_SESSION['recovery_admin_id'],
        $_SESSION['recovery_started']
    );

    $step = null;
    $error = 'Your recovery session expired. Please start again.';
}

// STEP 1: Find account using username
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['start_recovery'])
) {

    $username = trim($_POST['username'] ?? '');

    if ($username === '') {

        $error = 'Please enter your username.';

    } else {

        $stmt = $pdo->prepare(
            'SELECT
                id,
                recovery_locked_until,
                security_answer_1,
                security_answer_2,
                security_answer_3,
                security_answer_4,
                security_answer_5
             FROM admins
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute(['username' => $username]);

        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        $hasAnswers = $account &&
            !empty($account['security_answer_1']) &&
            !empty($account['security_answer_2']) &&
            !empty($account['security_answer_3']) &&
            !empty($account['security_answer_4']) &&
            !empty($account['security_answer_5']);

        if (!$hasAnswers) {

            $error = 'This account is not set up for security-question recovery. Contact the system administrator.';

        } elseif (
            !empty($account['recovery_locked_until']) &&
            strtotime($account['recovery_locked_until']) > time()
        ) {

            $error = 'Too many incorrect attempts. Please try again after 15 minutes.';

        } else {

            session_regenerate_id(true);

            $_SESSION['recovery_admin_id'] = (int)$account['id'];
            $_SESSION['recovery_started'] = time();

            $step = (int)$account['id'];
        }
    }
}

// STEP 2: Verify all five answers
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['verify_answers'])
) {

    $adminId = (int)($_SESSION['recovery_admin_id'] ?? 0);

    if (
        !$adminId ||
        !isset($_SESSION['recovery_started']) ||
        time() - $_SESSION['recovery_started'] > 600
    ) {

        unset(
            $_SESSION['recovery_admin_id'],
            $_SESSION['recovery_started']
        );

        $step = null;
        $error = 'Your recovery session expired. Please start again.';

    } else {

        $stmt = $pdo->prepare(
            'SELECT
                id,
                recovery_attempts,
                recovery_locked_until,
                security_answer_1,
                security_answer_2,
                security_answer_3,
                security_answer_4,
                security_answer_5
             FROM admins
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $adminId]);

        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {

            $error = 'Unable to verify this account. Please start again.';

            unset(
                $_SESSION['recovery_admin_id'],
                $_SESSION['recovery_started']
            );

            $step = null;

        } elseif (
            !empty($account['recovery_locked_until']) &&
            strtotime($account['recovery_locked_until']) > time()
        ) {

            $error = 'Too many incorrect attempts. Please try again after 15 minutes.';

        } else {

            $submitted = $_POST['security_answers'] ?? [];

            $columns = [
                'security_answer_1',
                'security_answer_2',
                'security_answer_3',
                'security_answer_4',
                'security_answer_5'
            ];

            $allCorrect = is_array($submitted);

            foreach ($columns as $i => $column) {

                $answer = normalizeSecurityAnswer(
                    (string)($submitted[$i] ?? '')
                );

                if (
                    $answer === '' ||
                    empty($account[$column]) ||
                    !password_verify($answer, $account[$column])
                ) {
                    $allCorrect = false;
                }
            }

            if ($allCorrect) {

                // Reset failed attempts
                $reset = $pdo->prepare(
                    'UPDATE admins
                     SET recovery_attempts = 0,
                         recovery_locked_until = NULL
                     WHERE id = :id'
                );

                $reset->execute(['id' => $adminId]);

                session_regenerate_id(true);

                $_SESSION['password_recovery_verified'] = $adminId;
                $_SESSION['password_recovery_verified_at'] = time();

                unset(
                    $_SESSION['recovery_admin_id'],
                    $_SESSION['recovery_started']
                );

                header('Location: reset_password.php');
                exit();

            } else {

                // Increase failed attempts
                $attempts = (int)$account['recovery_attempts'] + 1;

                if ($attempts >= 5) {

                    // Lock account recovery for 15 minutes
                    $update = $pdo->prepare(
                        'UPDATE admins
                         SET recovery_attempts = 0,
                             recovery_locked_until =
                             DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                         WHERE id = :id'
                    );

                    $update->execute(['id' => $adminId]);

                    $error = 'Too many incorrect answers. Recovery is locked for 15 minutes.';

                    unset(
                        $_SESSION['recovery_admin_id'],
                        $_SESSION['recovery_started']
                    );

                    $step = null;

                } else {

                    $update = $pdo->prepare(
                        'UPDATE admins
                         SET recovery_attempts = :attempts
                         WHERE id = :id'
                    );

                    $update->execute([
                        'attempts' => $attempts,
                        'id' => $adminId
                    ]);

                    $remaining = 5 - $attempts;

                    $error = 'One or more answers are incorrect. Attempts remaining: ' . $remaining . '.';

                    $step = $adminId;
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="grid-center">
    <div class="card auth-grid-width">

        <div class="card-header">
            <h3>Recover Your Password</h3>
        </div>

        <div class="card-body">

            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>

            <?php if (!$step): ?>

                <p>
                    Enter your username to begin account recovery.
                </p>

                <form method="POST" action="forgot_password.php">

                    <div class="form-group">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            required
                            autocomplete="username"
                        >

                    </div>

                    <button
                        type="submit"
                        name="start_recovery"
                        value="1"
                        class="btn btn-primary"
                        style="width:100%; padding:12px;"
                    >
                        Continue
                    </button>

                </form>

            <?php else: ?>

                <p>
                    Answer all five security questions exactly as you entered them during registration.
                </p>

                <form method="POST" action="forgot_password.php">

                    <?php foreach ($questions as $i => $question): ?>

                        <div class="form-group">

                            <label class="form-label">
                                <?= ($i + 1) . '. ' .
                                htmlspecialchars($question, ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <input
                                type="text"
                                name="security_answers[<?= $i ?>]"
                                class="form-control"
                                required
                                maxlength="200"
                                autocomplete="off"
                            >

                        </div>

                    <?php endforeach; ?>

                    <button
                        type="submit"
                        name="verify_answers"
                        value="1"
                        class="btn btn-success"
                        style="width:100%; padding:12px;"
                    >
                        Verify Answers
                    </button>

                </form>

            <?php endif; ?>

            <div style="margin-top:20px; text-align:center;">

                <a href="login.php">
                    Back to Login
                </a>

            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
