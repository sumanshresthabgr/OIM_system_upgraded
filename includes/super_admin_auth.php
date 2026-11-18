<?php
// Main Admin protection. Include this at the top of every super-admin page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Verify the role against the database on every request.
// Do not rely only on a role value stored in the browser/session.
$stmt_role = $pdo->prepare("SELECT id, username, role FROM admins WHERE id = :id LIMIT 1");
$stmt_role->execute(['id' => (int) $_SESSION['admin_id']]);
$current_admin = $stmt_role->fetch(PDO::FETCH_ASSOC);

if (!$current_admin || $current_admin['role'] !== 'superadmin') {
    http_response_code(403);
    exit("Access denied. Main Admin permission is required.");
}

$_SESSION['username'] = $current_admin['username'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function super_admin_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

function super_admin_verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    if (!$submitted || !$stored || !hash_equals($stored, $submitted)) {
        http_response_code(400);
        exit("Invalid security token. Please go back, refresh the page, and try again.");
    }
}
?>
