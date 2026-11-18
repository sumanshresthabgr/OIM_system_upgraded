<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$error = "";

$guest_id = $_GET['id'] ?? $_POST['guest_id'] ?? '';

if (empty($guest_id)) {
    die("Guest ID is missing.");
}

// Fetch guest and verify ownership
$stmt = $pdo->prepare("
    SELECT g.*, i.title AS event_title
    FROM guests g
    INNER JOIN invitations i ON g.invitation_id = i.id
    WHERE g.id = ? AND i.admin_id = ?
");

$stmt->execute([$guest_id, $admin_id]);
$guest = $stmt->fetch();

if (!$guest) {
    die("Guest not found or access denied.");
}

// Fetch events belonging to this admin
$event_stmt = $pdo->prepare("
    SELECT id, title
    FROM invitations
    WHERE admin_id = ?
    ORDER BY created_at DESC
");

$event_stmt->execute([$admin_id]);
$events = $event_stmt->fetchAll();

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $invitation_id = $_POST['invitation_id'] ?? '';
    $guest_name = trim($_POST['guest_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $guest_group = $_POST['guest_group'] ?? 'Other';
    $invitation_status = $_POST['invitation_status'] ?? 'Not Sent';
    $rsvp_status = $_POST['rsvp_status'] ?? 'Pending';
    $plus_ones = (int)($_POST['plus_ones'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (empty($invitation_id) || empty($guest_name)) {
        $error = "Event and guest name are required.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($plus_ones < 0) {
        $error = "Additional guests cannot be negative.";
    } else {

        // Verify event ownership
        $check = $pdo->prepare("
            SELECT id
            FROM invitations
            WHERE id = ? AND admin_id = ?
        ");

        $check->execute([$invitation_id, $admin_id]);

        if (!$check->fetch()) {
            $error = "Invalid event selection.";
        } else {

            $update = $pdo->prepare("
                UPDATE guests
                SET invitation_id = ?,
                    guest_name = ?,
                    email = ?,
                    phone = ?,
                    guest_group = ?,
                    invitation_status = ?,
                    rsvp_status = ?,
                    plus_ones = ?,
                    notes = ?
                WHERE id = ?
            ");

            $update->execute([
                $invitation_id,
                $guest_name,
                !empty($email) ? $email : null,
                !empty($phone) ? $phone : null,
                $guest_group,
                $invitation_status,
                $rsvp_status,
                $plus_ones,
                !empty($notes) ? $notes : null,
                $guest_id
            ]);

            header("Location: guests.php");
            exit();
        }
    }

    // Preserve submitted values if validation fails
    $guest = array_merge($guest, $_POST);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Guest</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button, .btn {
            display: inline-block;
            padding: 12px 18px;
            margin-top: 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            cursor: pointer;
        }

        .save-btn {
            background: #007bff;
        }

        .back-btn {
            background: #6c757d;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Edit Guest</h1>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="guest_id" value="<?= htmlspecialchars((string)$guest_id) ?>">

        <label>Event *</label>

        <select name="invitation_id" required>
            <?php foreach ($events as $event): ?>
                <option
                    value="<?= $event['id'] ?>"
                    <?= ($guest['invitation_id'] == $event['id']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($event['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Guest Name *</label>

        <input
            type="text"
            name="guest_name"
            value="<?= htmlspecialchars($guest['guest_name'] ?? '') ?>"
            required
        >

        <label>Email</label>

        <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($guest['email'] ?? '') ?>"
        >

        <label>Phone</label>

        <input
            type="text"
            name="phone"
            value="<?= htmlspecialchars($guest['phone'] ?? '') ?>"
        >

        <label>Guest Group</label>

        <select name="guest_group">
            <?php
            $groups = ['Family', 'Friends', 'Colleagues', 'VIP', 'Other'];
            foreach ($groups as $group):
            ?>
                <option
                    value="<?= htmlspecialchars($group) ?>"
                    <?= (($guest['guest_group'] ?? '') == $group) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($group) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Invitation Status</label>

        <select name="invitation_status">
            <?php foreach (['Not Sent', 'Sent'] as $status): ?>
                <option
                    value="<?= htmlspecialchars($status) ?>"
                    <?= (($guest['invitation_status'] ?? '') == $status) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($status) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>RSVP Status</label>

        <select name="rsvp_status">
            <?php foreach (['Pending', 'Accepted', 'Declined'] as $status): ?>
                <option
                    value="<?= htmlspecialchars($status) ?>"
                    <?= (($guest['rsvp_status'] ?? '') == $status) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($status) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Additional Guests</label>

        <input
            type="number"
            name="plus_ones"
            min="0"
            value="<?= htmlspecialchars((string)($guest['plus_ones'] ?? 0)) ?>"
        >

        <label>Notes</label>

        <textarea name="notes" rows="4"><?= htmlspecialchars($guest['notes'] ?? '') ?></textarea>

        <button type="submit" class="save-btn">
            Update Guest
        </button>

        <a href="guests.php" class="btn back-btn">
            Cancel
        </a>

    </form>

</div>

</body>
</html>
