<?php
require_once '../includes/super_admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed.");
}

super_admin_verify_csrf();

$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$event_id || $event_id < 1) {
    header("Location: super_admin_dashboard.php");
    exit();
}

// Confirm the invitation exists and retrieve its actual owner.
$stmt = $pdo->prepare("SELECT admin_id FROM invitations WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $event_id]);
$owner_id = $stmt->fetchColumn();

if (!$owner_id) {
    header("Location: super_admin_dashboard.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // Delete dependent RSVP records first, then the invitation.
    $delete_rsvps = $pdo->prepare("DELETE FROM rsvps WHERE invitation_id = :id");
    $delete_rsvps->execute(['id' => $event_id]);

    $delete_invitation = $pdo->prepare("DELETE FROM invitations WHERE id = :id");
    $delete_invitation->execute(['id' => $event_id]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Main Admin invitation deletion failed: " . $e->getMessage());
    http_response_code(500);
    exit("The invitation could not be deleted. Check the server error log.");
}

// Redirect to the real owner page; ignore any mismatched submitted user_id.
header("Location: super_admin_user.php?id=" . (int) $owner_id);
exit();
?>
