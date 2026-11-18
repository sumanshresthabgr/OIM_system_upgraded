<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

$guest_id = $_GET['id'] ?? '';

if (empty($guest_id)) {
    die("Guest ID is missing.");
}

// Verify guest belongs to the logged-in admin
$stmt = $pdo->prepare("
    SELECT g.id, g.guest_name
    FROM guests g
    INNER JOIN invitations i ON g.invitation_id = i.id
    WHERE g.id = ? AND i.admin_id = ?
");

$stmt->execute([$guest_id, $admin_id]);
$guest = $stmt->fetch();

if (!$guest) {
    die("Guest not found or access denied.");
}

// Delete guest
$delete = $pdo->prepare("
    DELETE FROM guests
    WHERE id = ?
");

$delete->execute([$guest_id]);

header("Location: guests.php");
exit();
?>