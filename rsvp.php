<?php
require_once __DIR__ . '/config/database.php';

$message = "";
$guest = null;

// Get token from URL
$token = $_GET['token'] ?? '';

// Find guest using token
if (!empty($token)) {

    $stmt = $pdo->prepare("
    SELECT
        g.*,
        i.title AS invitation_title,
        i.date_time AS event_date,
        i.venue AS event_venue,
        i.description AS event_description,
        i.theme AS event_theme,
        i.dress_code,
        i.registry,
        i.special_notes
    FROM guests g
    JOIN invitations i ON g.invitation_id = i.id
    WHERE g.share_token = ?
");

$stmt->execute([$token]);

$guest = $stmt->fetch();

}

// Handle RSVP submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $guest) {

    $status = $_POST['rsvp_status'] ?? '';

    if ($status === 'Accepted' || $status === 'Declined') {

        $stmt = $pdo->prepare(
            "UPDATE guests
             SET rsvp_status = ?
             WHERE share_token = ?"
        );

        $stmt->execute([$status, $token]);

        $message = "Your response has been submitted successfully!";

        // Update displayed status
        $guest['rsvp_status'] = $status;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Invitation RSVP</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background: white;
            padding: 40px;
            width: 90%;
            max-width: 450px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 {
            color: #8b5cf6;
        }

        .guest-name {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }

        .button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }

        .accept {
            background: #16a34a;
        }

        .decline {
            background: #dc2626;
        }

        .message {
            margin: 20px 0;
            color: #2563eb;
            font-weight: bold;
        }

        .status {
            margin-top: 20px;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 8px;
        }

        .event-details {
    background: #f9f5ff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 10px;
    text-align: left;
}

.event-details p {
    margin: 12px 0;
    line-height: 1.6;
}

.card h2 {
    color: #6d28d9;
    margin-top: 20px;
}

    </style>

</head>

<body>

<div class="card">

    <?php if ($guest): ?>

        <h1>You're Invited!</h1>

<p>Dear</p>

<div class="guest-name">
    <?= htmlspecialchars($guest['guest_name']) ?>
</div>

<h2>
    <?= htmlspecialchars($guest['invitation_title']) ?>
</h2>

<p>
    <?= htmlspecialchars($guest['event_description'] ?? '') ?>
</p>

<div class="event-details">

    <p>
        <strong>Date & Time:</strong>
        <?= date('F j, Y - g:i A', strtotime($guest['event_date'])) ?>
    </p>

    <p>
        <strong>Venue:</strong>
        <?= htmlspecialchars($guest['event_venue']) ?>
    </p>

    <?php if (!empty($guest['dress_code'])): ?>

        <p>
            <strong>Dress Code:</strong>
            <?= htmlspecialchars($guest['dress_code']) ?>
        </p>

    <?php endif; ?>

    <?php if (!empty($guest['special_notes'])): ?>

        <p>
            <strong>Special Notes:</strong>
            <?= htmlspecialchars($guest['special_notes']) ?>
        </p>

    <?php endif; ?>

</div>

        <?php if (!empty($message)): ?>

            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>

        <div class="status">

            Current RSVP Status:

            <strong>
                <?= htmlspecialchars($guest['rsvp_status'] ?? 'Pending') ?>
            </strong>

        </div>

        <form method="POST">

            <button
                type="submit"
                name="rsvp_status"
                value="Accepted"
                class="button accept">

                Accept Invitation

            </button>

            <button
                type="submit"
                name="rsvp_status"
                value="Declined"
                class="button decline">

                Decline Invitation

            </button>

        </form>

    <?php else: ?>

        <h1>Invalid Invitation</h1>

        <p>This invitation link is invalid or does not exist.</p>

    <?php endif; ?>

</div>

</body>

</html>