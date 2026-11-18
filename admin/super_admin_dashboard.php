<?php
require_once '../includes/super_admin_auth.php';

// Counts for the dashboard cards.
$total_users = (int) $pdo->query(
    "SELECT COUNT(*) FROM admins WHERE role = 'user'"
)->fetchColumn();

$total_invitations = (int) $pdo->query(
    "SELECT COUNT(*) FROM invitations"
)->fetchColumn();

$total_rsvps = (int) $pdo->query(
    "SELECT COUNT(*) FROM rsvps"
)->fetchColumn();

// Show each normal user and the number of invitations they created.
$stmt_users = $pdo->query("
    SELECT
        a.id,
        a.username,
        COUNT(i.id) AS invitation_count
    FROM admins a
    LEFT JOIN invitations i ON i.admin_id = a.id
    WHERE a.role = 'user'
    GROUP BY a.id, a.username
    ORDER BY a.username ASC
");
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header" style="display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:25px;">
        <div>
            <h2>Main Admin Dashboard</h2>
            <p style="color:#64748b;margin:0;">
                Welcome, <?= htmlspecialchars($_SESSION['username']) ?>.
                You have system-wide management access.
            </p>
        </div>
        <a href="logout.php" class="btn btn-outline">Logout</a>
    </div>

    <div class="grid-2col" style="margin-bottom:25px;">
        <div class="card">
            <div class="card-body">
                <p style="color:#64748b;margin:0;">Registered Users</p>
                <h2 style="font-size:2rem;margin:5px 0;"><?= $total_users ?></h2>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p style="color:#64748b;margin:0;">Total Invitations</p>
                <h2 style="font-size:2rem;margin:5px 0;"><?= $total_invitations ?></h2>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p style="color:#64748b;margin:0;">Total RSVP Records</p>
                <h2 style="font-size:2rem;margin:5px 0;"><?= $total_rsvps ?></h2>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p style="color:#64748b;margin:0;">Your Account</p>
                <h3 style="margin:5px 0;">Main Admin</h3>
                <span class="badge badge-success">Superadmin</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Registered Users</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="dashboard-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Username</th>
                            <th>Invitations Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="4" style="text-align:center;">No normal users registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= (int) $user['invitation_count'] ?></td>
                                <td>
                                    <a class="btn btn-primary"
                                       href="super_admin_user.php?id=<?= (int) $user['id'] ?>"
                                       style="padding:6px 12px;">View Invitations</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p style="margin-top:18px;">
        <a href="dashboard.php" class="btn btn-outline">Open My Regular Dashboard</a>
    </p>
</div>

<?php include '../includes/footer.php'; ?>
