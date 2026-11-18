<?php
require_once '../includes/super_admin_auth.php';

$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$event_id || $event_id < 1) {
    header("Location: super_admin_dashboard.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT i.*, a.username
    FROM invitations i
    INNER JOIN admins a ON a.id = i.admin_id
    WHERE i.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit("Invitation not found.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    super_admin_verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $date_time = trim($_POST['date_time'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $theme = trim($_POST['theme'] ?? '');
    $dress_code = trim($_POST['dress_code'] ?? '');
    $registry = trim($_POST['registry'] ?? '');
    $special_notes = trim($_POST['special_notes'] ?? '');

    $allowed_themes = ['wedding', 'birthday', 'corporate'];

    if ($title === '' || $date_time === '' || $venue === '' || !in_array($theme, $allowed_themes, true)) {
        $error = 'Please complete all required fields and choose a valid theme.';
    } else {
        $update = $pdo->prepare("
            UPDATE invitations
            SET title = :title,
                date_time = :date_time,
                venue = :venue,
                description = :description,
                theme = :theme,
                dress_code = :dress_code,
                registry = :registry,
                special_notes = :special_notes
            WHERE id = :id
        ");

        $update->execute([
            'title' => $title,
            'date_time' => $date_time,
            'venue' => $venue,
            'description' => $description !== '' ? $description : null,
            'theme' => $theme,
            'dress_code' => $dress_code !== '' ? $dress_code : null,
            'registry' => $registry !== '' ? $registry : null,
            'special_notes' => $special_notes !== '' ? $special_notes : null,
            'id' => $event_id
        ]);

        header("Location: super_admin_user.php?id=" . (int) $event['admin_id']);
        exit();
    }

    // Keep submitted values visible if validation fails.
    $event = array_merge($event, [
        'title' => $title,
        'date_time' => $date_time,
        'venue' => $venue,
        'description' => $description,
        'theme' => $theme,
        'dress_code' => $dress_code,
        'registry' => $registry,
        'special_notes' => $special_notes
    ]);
}

include '../includes/header.php';
?>

<div class="card form-grid-width" style="margin-top:25px;">
    <div class="card-header">
        <h3>Main Admin: Edit Invitation</h3>
        <p style="margin:5px 0 0;color:#64748b;">
            Owner: <?= htmlspecialchars($event['username']) ?>
        </p>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="super_admin_edit_event.php?id=<?= (int) $event_id ?>">
            <?= super_admin_csrf_field() ?>

            <div class="form-group">
                <label class="form-label">Event Title</label>
                <input class="form-control" type="text" name="title"
                       value="<?= htmlspecialchars($event['title']) ?>" required>
            </div>

            <div class="grid-2col">
                <div class="form-group">
                    <label class="form-label">Event Date & Time</label>
                    <input class="form-control" type="datetime-local" name="date_time"
                           value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['date_time']))) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Theme</label>
                    <select class="form-control" name="theme" required>
                        <option value="wedding" <?= $event['theme'] === 'wedding' ? 'selected' : '' ?>>Wedding</option>
                        <option value="birthday" <?= $event['theme'] === 'birthday' ? 'selected' : '' ?>>Birthday</option>
                        <option value="corporate" <?= $event['theme'] === 'corporate' ? 'selected' : '' ?>>Corporate</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Venue</label>
                <input class="form-control" type="text" name="venue"
                       value="<?= htmlspecialchars($event['venue']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
            </div>

            <div class="grid-2col">
                <div class="form-group">
                    <label class="form-label">Dress Code</label>
                    <input class="form-control" type="text" name="dress_code"
                           value="<?= htmlspecialchars($event['dress_code'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Gift Registry URL</label>
                    <input class="form-control" type="url" name="registry"
                           value="<?= htmlspecialchars($event['registry'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Special Notes</label>
                <textarea class="form-control" name="special_notes" rows="3"><?= htmlspecialchars($event['special_notes'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <a class="btn btn-outline"
                   href="super_admin_user.php?id=<?= (int) $event['admin_id'] ?>">Cancel</a>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
