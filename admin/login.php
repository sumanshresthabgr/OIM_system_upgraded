<?php
require_once '../config/database.php';
session_start();

// 1. If the user is already logged in, bypass the login page
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // 2. Fetch the user safely using a Prepared Statement
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Strict verification of the password using PHP's built-in password_verify
        if ($admin && password_verify($password, $admin['password'])) {
            
            // 4. CASE SENSITIVITY CHECK: Ensure exact capitalization match (e.g., Suman vs SUMAN)
            if ($admin['username'] !== $username) {
                $error = 'Invalid credentials. Please verify your username and password again.';
            } else {
                // 5. SECURITY FIX: Regenerate session ID to prevent Session Fixation attacks
                session_regenerate_id(true);

                // 6. Establish session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                
                header("Location: dashboard.php");
                exit();
            }
        } else {
            // Generic error message keeps attackers from guessing whether the user exists
            $error = 'Invalid credentials. Please verify your username and password again.';
        }
    } else {
        $error = 'Please fill out all details.';
    }
}
include '../includes/header.php';
?>

<div class="grid-center">
    <div class="card auth-grid-width">
        <div class="card-header">
            <h3>Login to OIMS Engine</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight:700;">Login</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center; font-size: 0.9rem;">
                <span style="color:#64748b;">New event planner?</span> 
                <a href="register.php" style="color: #16a34a; font-weight:600; text-decoration:none;">Create New User Account</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>