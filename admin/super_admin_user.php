<?php
require_once '../includes/super_admin_auth.php';

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id || $user_id < 1) {
    header("Location: super_admin_dashboard.php");
    exit();
}

$stmt_user = $pdo->prepare("
    SELECT id, username
    FROM admins
    WHERE id = :id AND role = 'user'
    LIMIT 1
");
$stmt_user->execute(['id' => $user_id]);
$selected_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$selected_user) {
    http_response_code(404);
    exit("User not found.");
}

$stmt_events = $pdo->prepare("
    SELECT
        i.id, i.title, i.date_time, i.venue, i.theme, i.share_token, i.created_at,
        COUNT(r.id) AS total_rsvps
    FROM invitations i
    LEFT JOIN rsvps r ON r.invitation_id = i.id
    WHERE i.admin_id = :admin_id
    GROUP BY i.id
    ORDER BY i.created_at DESC
");
$stmt_events->execute(['admin_id' => $user_id]);
$invitations = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="dashboard-container">
    <p><a href="super_admin_dashboard.php" class="btn btn-outline">← Main Admin Dashboard</a></p>

    <div style="margin:20px 0;">
        <h2><?= htmlspecialchars($selected_user['username']) ?>'s Invitations</h2>
        <p style="color:#64748b;">Total invitations: <?= count($invitations) ?></p>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="dashboard-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Date & Time</th>
                            <th>Theme</th>
                            <th>RSVPs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$invitations): ?>
                        <tr><td colspan="5" style="text-align:center;">This user has not created any invitations.</td></tr>
                    <?php else: ?>
                        <?php foreach ($invitations as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['title']) ?></td>
                                <td><?= htmlspecialchars($event['date_time']) ?></td>
                                <td><?= htmlspecialchars($event['theme']) ?></td>
                                <td><?= (int) $event['total_rsvps'] ?></td>
                                <td style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a class="btn btn-outline"
                                       href="super_admin_view_rsvps.php?id=<?= (int) $event['id'] ?>"
                                       style="padding:6px 10px;">RSVPs</a>
                                    <a class="btn btn-primary"
                                       href="super_admin_edit_event.php?id=<?= (int) $event['id'] ?>"
                                       style="padding:6px 10px;">Edit</a>
                                    <form action="super_admin_delete_event.php" method="POST"
                                          onsubmit="return confirm('Delete this invitation and all its RSVP records? This cannot be undone.');">
                                        <?= super_admin_csrf_field() ?>
                                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $user_id ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:6px 10px;">Delete</button>
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
