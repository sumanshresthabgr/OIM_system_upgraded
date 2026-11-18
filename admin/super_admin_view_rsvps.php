<?php
require_once '../includes/super_admin_auth.php';

$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$event_id || $event_id < 1) {
    header("Location: super_admin_dashboard.php");
    exit();
}

$stmt_event = $pdo->prepare("
    SELECT i.id, i.title, i.admin_id, a.username
    FROM invitations i
    INNER JOIN admins a ON a.id = i.admin_id
    WHERE i.id = :id
    LIMIT 1
");
$stmt_event->execute(['id' => $event_id]);
$event = $stmt_event->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit("Invitation not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rsvp_id'])) {
    super_admin_verify_csrf();

    $rsvp_id = filter_input(INPUT_POST, 'delete_rsvp_id', FILTER_VALIDATE_INT);
    if ($rsvp_id && $rsvp_id > 0) {
        $delete = $pdo->prepare("
            DELETE FROM rsvps
            WHERE id = :rsvp_id AND invitation_id = :invitation_id
        ");
        $delete->execute([
            'rsvp_id' => $rsvp_id,
            'invitation_id' => $event_id
        ]);
    }

    header("Location: super_admin_view_rsvps.php?id=" . (int) $event_id);
    exit();
}

$stmt_rsvps = $pdo->prepare("
    SELECT id, guest_name, status, submitted_at
    FROM rsvps
    WHERE invitation_id = :id
    ORDER BY submitted_at DESC
");
$stmt_rsvps->execute(['id' => $event_id]);
$rsvps = $stmt_rsvps->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="dashboard-container">
    <p>
        <a href="super_admin_user.php?id=<?= (int) $event['admin_id'] ?>" class="btn btn-outline">← Back to User</a>
    </p>

    <h2>RSVP Ledger: <?= htmlspecialchars($event['title']) ?></h2>
    <p style="color:#64748b;">Invitation owner: <?= htmlspecialchars($event['username']) ?></p>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="dashboard-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Response</th>
                            <th>Submitted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rsvps): ?>
                        <tr><td colspan="4" style="text-align:center;">No RSVP records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rsvps as $rsvp): ?>
                            <tr>
                                <td><?= htmlspecialchars($rsvp['guest_name']) ?></td>
                                <td><?= htmlspecialchars($rsvp['status']) ?></td>
                                <td><?= htmlspecialchars($rsvp['submitted_at']) ?></td>
                                <td>
                                    <form method="POST"
                                          action="super_admin_view_rsvps.php?id=<?= (int) $event_id ?>"
                                          onsubmit="return confirm('Delete this RSVP record?');">
                                        <?= super_admin_csrf_field() ?>
                                        <input type="hidden" name="delete_rsvp_id" value="<?= (int) $rsvp['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:6px 10px;">Delete RSVP</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
