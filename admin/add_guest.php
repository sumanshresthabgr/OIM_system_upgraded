<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

$error = "";

// Fetch events belonging to the logged-in admin
$stmt = $pdo->prepare("
    SELECT id, title
    FROM invitations
    WHERE admin_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$admin_id]);
$events = $stmt->fetchAll();

// Process form submission
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

    // Validation
    if (
        empty($invitation_id) ||
        empty($guest_name)
    ) {
        $error = "Please select an event and enter the guest name.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($plus_ones < 0) {
        $error = "Additional guests cannot be negative.";
    } else {

        // Verify that the selected event belongs to this admin
        $check = $pdo->prepare("
            SELECT id
            FROM invitations
            WHERE id = ? AND admin_id = ?
        ");
        $check->execute([$invitation_id, $admin_id]);

        if (!$check->fetch()) {
            $error = "Invalid event selection.";
        } else {

            // Insert guest
            $insert = $pdo->prepare("
                INSERT INTO guests (
                    invitation_id,
                    guest_name,
                    email,
                    phone,
                    guest_group,
                    invitation_status,
                    rsvp_status,
                    plus_ones,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $invitation_id,
                $guest_name,
                !empty($email) ? $email : null,
                !empty($phone) ? $phone : null,
                $guest_group,
                $invitation_status,
                $rsvp_status,
                $plus_ones,
                !empty($notes) ? $notes : null
            ]);

            header("Location: guests.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Guest</title>

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
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            color: #333;
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

        textarea {
            resize: vertical;
        }

        button, .btn {
            display: inline-block;
            padding: 12px 18px;
            margin-top: 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }

        .submit-btn {
            background: #28a745;
        }

        .back-btn {
            background: #6c757d;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .no-events {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Add New Guest</h1>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($events)): ?>

        <div class="no-events">
            You need to create an event before adding guests.
        </div>

        <a href="create_event.php" class="btn back-btn">
            Create Event
        </a>

    <?php else: ?>

        <form method="POST">

            <label for="invitation_id">Select Event *</label>

            <select name="invitation_id" id="invitation_id" required>
                <option value="">-- Select Event --</option>

                <?php foreach ($events as $event): ?>
                    <option
                        value="<?= $event['id'] ?>"
                        <?= (($_POST['invitation_id'] ?? '') == $event['id']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($event['title']) ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <label for="guest_name">Guest Name *</label>

            <input
                type="text"
                id="guest_name"
                name="guest_name"
                value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>"
                required
            >

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            >

            <label for="phone">Phone Number</label>

            <input
                type="text"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
            >

            <label for="guest_group">Guest Group</label>

            <select name="guest_group" id="guest_group">
                <option value="Family">Family</option>
                <option value="Friends">Friends</option>
                <option value="Colleagues">Colleagues</option>
                <option value="VIP">VIP</option>
                <option value="Other" selected>Other</option>
            </select>

            <label for="invitation_status">Invitation Status</label>

            <select name="invitation_status" id="invitation_status">
                <option value="Not Sent">Not Sent</option>
                <option value="Sent">Sent</option>
            </select>

            <label for="rsvp_status">RSVP Status</label>

            <select name="rsvp_status" id="rsvp_status">
                <option value="Pending">Pending</option>
                <option value="Accepted">Accepted</option>
                <option value="Declined">Declined</option>
            </select>

            <label for="plus_ones">Additional Guests</label>

            <input
                type="number"
                id="plus_ones"
                name="plus_ones"
                min="0"
                value="0"
            >

            <label for="notes">Notes</label>

            <textarea
                id="notes"
                name="notes"
                rows="4"
            ><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>

            <button type="submit" class="submit-btn">
                Save Guest
            </button>

            <a href="guests.php" class="btn back-btn">
                Cancel
            </a>

        </form>

    <?php endif; ?>

</div>

</body>
</html>