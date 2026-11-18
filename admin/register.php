
<?php
require_once '../config/database.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (!empty($username) && !empty($password) && !empty($confirm_password)) {

        if ($password !== $confirm_password) {

            $error = 'Passwords do not match.';

        } elseif (strlen($password) < 6) {

            $error = 'Password must be at least 6 characters long.';

        } else {

            // Check whether username already exists
            $stmt = $pdo->prepare(
                "SELECT id FROM admins WHERE username = :username"
            );

            $stmt->execute(['username' => $username]);

            if ($stmt->fetch()) {

                $error = 'Username is already registered. Please select another.';

            } else {

                // Hash password securely
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // ALWAYS create a normal user account
                // Users cannot select or submit their own role
                $insert = $pdo->prepare(
                    "INSERT INTO admins (username, password, role)
                     VALUES (:username, :password, 'user')"
                );

                $insert->execute([
                    'username' => $username,
                    'password' => $hashed_password
                ]);

                $success = 'Account created successfully! Redirecting to login...';

                header("Refresh: 2; URL=login.php");
            }
        }

    } else {

        $error = 'Please fill out all required fields.';
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
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="e.g., organizer2026"
                        required
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
                        placeholder="Repeat password"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-success"
                    style="width: 100%; padding: 12px; font-weight:700;"
                >
                    Sign Up
                </button>

            </form>

            <div style="margin-top: 20px; text-align: center; font-size: 0.9rem;">
                <span style="color:#64748b;">
                    Already have an account?
                </span>

                <a
                    href="login.php"
                    style="color: #2563eb; font-weight:600; text-decoration:none;"
                >
                    Log In Here
                </a>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
