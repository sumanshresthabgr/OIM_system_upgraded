<?php
require_once '../config/database.php';
session_start();

$error = '';
$success = '';

$questions = [
    'What is your favourite food?',
    'What is your favourite movie?',
    'What is your favourite game?',
    'What was your first crush name?',
    'What is your hobby?'
];

function normalizeSecurityAnswer(string $answer): string {
    $answer = trim($answer);
    $answer = preg_replace('/\s+/', ' ', $answer);
    return mb_strtolower($answer, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $answers = $_POST['security_answers'] ?? [];

    if (!is_array($answers)) {
        $answers = [];
    }

    $cleanAnswers = [];

    foreach ($questions as $i => $question) {
        $cleanAnswers[$i] = normalizeSecurityAnswer(
            (string)($answers[$i] ?? '')
        );
    }

    if ($username === '' || $password === '' || $confirm_password === '') {
        $error = 'Please fill out all required fields.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';

    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';

    } elseif (in_array('', $cleanAnswers, true)) {
        $error = 'Please answer all five security questions.';

    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id FROM admins WHERE username = :username'
            );

            $stmt->execute(['username' => $username]);

            if ($stmt->fetch()) {
                $error = 'Username already exists. Please choose another username.';

            } else {
                $answerHashes = [];

                foreach ($cleanAnswers as $answer) {
                    $answerHashes[] = password_hash(
                        $answer,
                        PASSWORD_DEFAULT
                    );
                }

                $insert = $pdo->prepare(
                    "INSERT INTO admins
                    (
                        username,
                        password,
                        role,
                        security_answer_1,
                        security_answer_2,
                        security_answer_3,
                        security_answer_4,
                        security_answer_5
                    )
                    VALUES
                    (
                        :username,
                        :password,
                        'user',
                        :a1,
                        :a2,
                        :a3,
                        :a4,
                        :a5
                    )"
                );

                $insert->execute([
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'a1' => $answerHashes[0],
                    'a2' => $answerHashes[1],
                    'a3' => $answerHashes[2],
                    'a4' => $answerHashes[3],
                    'a5' => $answerHashes[4]
                ]);

                $success = 'Account created successfully! You can now log in.';
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Account creation failed due to a database error. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="grid-center">
    <div class="card auth-grid-width">

        <div class="card-header">
            <h3>Create New Account</h3>
        </div>

        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Registration Failed!</strong><br>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Registration Successful!</strong><br>
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    <br><br>
                    <a href="login.php" class="btn btn-success">
                        Go to Login
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>

            <form action="register.php" method="POST">

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        required
                        maxlength="50"
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Minimum 6 characters"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <hr>

                <h4>Security Questions</h4>

                <p style="color:#64748b;">
                    Remember your answers. You will need all five to recover your password.
                </p>

                <?php foreach ($questions as $i => $question): ?>

                    <div class="form-group">
                        <label class="form-label">
                            <?= ($i + 1) . '. ' . htmlspecialchars($question, ENT_QUOTES, 'UTF-8') ?>
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
                    class="btn btn-success"
                    style="width:100%; padding:12px; font-weight:700;"
                >
                    Sign Up
                </button>

            </form>

            <?php endif; ?>

            <div style="margin-top:20px; text-align:center; font-size:0.9rem;">
                <span style="color:#64748b;">
                    Already have an account?
                </span>

                <a
                    href="login.php"
                    style="color:#2563eb; font-weight:600; text-decoration:none;"
                >
                    Log In Here
                </a>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
