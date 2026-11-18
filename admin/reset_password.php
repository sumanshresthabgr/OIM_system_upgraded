
<?php
require_once '../config/database.php';
session_start();

$adminId = (int)($_SESSION['password_recovery_verified'] ?? 0);

$verifiedAt = (int)(
    $_SESSION['password_recovery_verified_at'] ?? 0
);

// Check recovery authorization
if (
    !$adminId ||
    !$verifiedAt ||
    time() - $verifiedAt > 600
) {

    unset(
        $_SESSION['password_recovery_verified'],
        $_SESSION['password_recovery_verified_at']
    );

    header('Location: forgot_password.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password === '' || $confirm === '') {

        $error = 'Please fill out both password fields.';

    } elseif (strlen($password) < 6) {

        $error = 'Password must be at least 6 characters long.';

    } elseif ($password !== $confirm) {

        $error = 'Passwords do not match.';

    } else {

        // Update password securely
        $stmt = $pdo->prepare(
            'UPDATE admins
             SET password = :password
             WHERE id = :id'
        );

        $stmt->execute([
            'password' => password_hash(
                $password,
                PASSWORD_DEFAULT
            ),
            'id' => $adminId
        ]);

        // Destroy recovery authorization
        unset(
            $_SESSION['password_recovery_verified'],
            $_SESSION['password_recovery_verified_at']
        );

        session_regenerate_id(true);

        $success = 'Your password has been changed successfully. You can now log in.';

        header('Refresh: 3; URL=login.php');
    }
}

include '../includes/header.php';
?>

<div class="grid-center">
    <div class="card auth-grid-width">

        <div class="card-header">
            <h3>Set a New Password</h3>
        </div>

        <div class="card-body">

            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>

            <?php if ($success): ?>

                <div class="alert alert-success">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <p style="text-align:center;">
                    Redirecting to login page...
                </p>

            <?php else: ?>

                <form method="POST" action="reset_password.php">

                    <div class="form-group">

                        <label class="form-label">
                            New Password
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            class="form-control"
                            minlength="6"
                            required
                            autocomplete="new-password"
                        >

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            minlength="6"
                            required
                            autocomplete="new-password"
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-success"
                        style="width:100%; padding:12px;"
                    >
                        Update Password
                    </button>

                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
