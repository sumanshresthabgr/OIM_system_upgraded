<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Search and filter values
$search = trim($_GET['search'] ?? '');
$event_filter = $_GET['event'] ?? '';
$group_filter = $_GET['group'] ?? '';
$rsvp_filter = $_GET['rsvp'] ?? '';
$invitation_filter = $_GET['invitation'] ?? '';

// Fetch events belonging to this admin
$event_stmt = $pdo->prepare("
    SELECT id, title
    FROM invitations
    WHERE admin_id = ?
    ORDER BY created_at DESC
");

$event_stmt->execute([$admin_id]);
$events = $event_stmt->fetchAll();

// Base query
$sql = "
    SELECT
        g.*,
        i.title AS event_title
    FROM guests g
    INNER JOIN invitations i ON g.invitation_id = i.id
    WHERE i.admin_id = :admin_id
";

$params = [
    ':admin_id' => $admin_id
];

// Search
if ($search !== '') {
    $sql .= "
        AND (
            g.guest_name LIKE :search
            OR g.email LIKE :search
            OR g.phone LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}

// Event filter
if ($event_filter !== '') {
    $sql .= " AND g.invitation_id = :event_id";
    $params[':event_id'] = $event_filter;
}

// Group filter
if ($group_filter !== '') {
    $sql .= " AND g.guest_group = :guest_group";
    $params[':guest_group'] = $group_filter;
}

// RSVP filter
if ($rsvp_filter !== '') {
    $sql .= " AND g.rsvp_status = :rsvp_status";
    $params[':rsvp_status'] = $rsvp_filter;
}

// Invitation filter
if ($invitation_filter !== '') {
    $sql .= " AND g.invitation_status = :invitation_status";
    $params[':invitation_status'] = $invitation_filter;
}

$sql .= " ORDER BY g.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$guests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Guest Management</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1 {
            color: #333;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button, .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }

        .search-btn {
            background: #555;
        }

        .add-btn {
            background: #28a745;
        }

        .back-btn {
            background: #6c757d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f1f1;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }

        .accepted {
            background: #d4edda;
            color: #155724;
        }

        .declined {
            background: #f8d7da;
            color: #721c24;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .sent {
            background: #d1ecf1;
            color: #0c5460;
        }

        .not-sent {
            background: #e2e3e5;
            color: #383d41;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 15px;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="top-bar">
        <h1>Guest Management</h1>

        <div>
            <a href="dashboard.php" class="btn back-btn">Dashboard</a>
            <a href="add_guest.php" class="btn add-btn">+ Add Guest</a>
        </div>
    </div>

    <form method="GET" class="filter-form">

    <input
        type="text"
        name="search"
        placeholder="Search name, email or phone"
        value="<?= htmlspecialchars($search) ?>"
    >

    <select name="event">
        <option value="">All Events</option>

        <?php foreach ($events as $event): ?>
            <option
                value="<?= $event['id'] ?>"
                <?= ($event_filter == $event['id']) ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($event['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="group">
        <option value="">All Groups</option>

        <?php foreach (['Family', 'Friends', 'Colleagues', 'VIP', 'Other'] as $group): ?>
            <option
                value="<?= htmlspecialchars($group) ?>"
                <?= ($group_filter == $group) ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($group) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="rsvp">
        <option value="">All RSVP Statuses</option>
        <option value="Pending" <?= ($rsvp_filter == 'Pending') ? 'selected' : '' ?>>Pending</option>
        <option value="Accepted" <?= ($rsvp_filter == 'Accepted') ? 'selected' : '' ?>>Accepted</option>
        <option value="Declined" <?= ($rsvp_filter == 'Declined') ? 'selected' : '' ?>>Declined</option>
    </select>

    <select name="invitation">
        <option value="">All Invitation Statuses</option>
        <option value="Not Sent" <?= ($invitation_filter == 'Not Sent') ? 'selected' : '' ?>>Not Sent</option>
        <option value="Sent" <?= ($invitation_filter == 'Sent') ? 'selected' : '' ?>>Sent</option>
    </select>

    <button type="submit" class="search-btn">Apply Filters</button>

    <a href="guests.php" class="btn back-btn">Reset</a>

</form>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Guest Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Event</th>
                <th>Group</th>
                <th>Invitation</th>
                <th>RSVP</th>
                <th>Additional Guests</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php if (!empty($guests)): ?>

            <?php foreach ($guests as $index => $guest): ?>

                <tr>
                    <td><?= $index + 1 ?></td>

                    <td>
                        <?= htmlspecialchars($guest['guest_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($guest['email'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($guest['phone'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($guest['event_title']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($guest['guest_group'] ?? 'Other') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($guest['invitation_status'] ?? 'Not Sent') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($guest['rsvp_status'] ?? 'Pending') ?>
                    </td>

                    <td>
                        <?= (int)$guest['plus_ones'] ?>
                    </td>

                    <td>
    <a href="edit_guest.php?id=<?= $guest['id'] ?>"
       style="background:#007bff;color:white;padding:6px 10px;text-decoration:none;border-radius:4px;">
       Edit
    </a>

    <a href="delete_guest.php?id=<?= $guest['id'] ?>"
       onclick="return confirm('Are you sure you want to delete this guest?');"
       style="background:#dc3545;color:white;padding:6px 10px;text-decoration:none;border-radius:4px;">
       Delete
    </a>
</td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="10" class="empty">
                    No guests found. Click "Add Guest" to add your first guest.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>