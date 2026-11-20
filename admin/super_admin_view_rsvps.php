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

$count_attending = 0;
$count_declined = 0;
foreach ($rsvps as $rsvp) {
    if ($rsvp['status'] === 'Attending') {
        $count_attending++;
    } else {
        $count_declined++;
    }
}

include '../includes/header.php';
?>

<style>
    .sa-ledger-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin: 18px 0 20px 0;
        padding: 14px 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .sa-search-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        flex: 1;
        min-width: 240px;
    }

    .sa-search-input {
        padding: 8px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        flex: 1;
        min-width: 190px;
        max-width: 320px;
    }

    .sa-search-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .sa-filter-select {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.88rem;
        background: #f8fafc;
        color: #1e293b;
        cursor: pointer;
    }

    .sa-export-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .btn-export-excel {
        background: #15803d;
        color: #ffffff;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .btn-export-excel:hover {
        background: #166534;
    }

    .btn-export-pdf {
        background: #1e293b;
        color: #ffffff;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .btn-export-pdf:hover {
        background: #0f172a;
    }

    @media (max-width: 600px) {
        .sa-ledger-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .sa-search-input {
            max-width: 100%;
        }

        .sa-export-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
    }

    @media print {
        .main-navbar,
        .main-footer,
        .no-print,
        .col-actions {
            display: none !important;
        }

        body {
            background: #ffffff !important;
            color: #000000 !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
        }

        .dashboard-table th,
        .dashboard-table td {
            border: 1px solid #cbd5e1 !important;
            padding: 8px 12px !important;
        }
    }
</style>

<div class="dashboard-container">
    <p class="no-print">
        <a href="super_admin_user.php?id=<?= (int) $event['admin_id'] ?>" class="btn btn-outline">← Back to User</a>
    </p>

    <h2>RSVP Ledger: <?= htmlspecialchars($event['title']) ?></h2>
    <p style="color:#64748b;">
        Invitation owner: <?= htmlspecialchars($event['username']) ?> &middot;
        Attending: <strong><?= $count_attending ?></strong> &middot;
        Declined: <strong><?= $count_declined ?></strong>
    </p>

    <?php if ($rsvps): ?>
        <div class="sa-ledger-toolbar no-print">
            <div class="sa-search-group">
                <input type="text" id="saSearchInput" class="sa-search-input"
                    placeholder="🔍 Search guest name...">
                <select id="saStatusFilter" class="sa-filter-select">
                    <option value="all">All Responses (<?= count($rsvps) ?>)</option>
                    <option value="Attending">Attending Only (<?= $count_attending ?>)</option>
                    <option value="Declined">Declined Only (<?= $count_declined ?>)</option>
                </select>
            </div>

            <div class="sa-export-group">
                <button type="button" onclick="downloadExcel()" class="btn-export-excel">📊 Download Excel</button>
                <button type="button" onclick="downloadPDF()" class="btn-export-pdf">📄 Download PDF</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="dashboard-table" id="saLedgerTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th>S.N.</th>
                            <th>Guest Name</th>
                            <th>Response</th>
                            <th>Submitted At</th>
                            <th class="col-actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rsvps): ?>
                        <tr><td colspan="5" style="text-align:center;">No RSVP records found.</td></tr>
                    <?php else:
                        $sn = 1;
                        foreach ($rsvps as $rsvp): ?>
                            <tr class="sa-attendee-row"
                                data-name="<?= htmlspecialchars(strtolower($rsvp['guest_name'])) ?>"
                                data-status="<?= htmlspecialchars($rsvp['status']) ?>">
                                <td class="row-sn" style="font-weight:700;"><?= $sn++ ?></td>
                                <td class="row-guest-name"><?= htmlspecialchars($rsvp['guest_name']) ?></td>
                                <td class="row-status"><?= htmlspecialchars($rsvp['status']) ?></td>
                                <td class="row-timestamp"><?= htmlspecialchars($rsvp['submitted_at']) ?></td>
                                <td class="col-actions">
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
                        <tr id="saNoMatchRow" style="display: none;">
                            <td colspan="5" style="text-align:center; color:#64748b; padding:20px;">
                                No attendees match your current search or filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("saSearchInput");
    const statusFilter = document.getElementById("saStatusFilter");
    const rows = document.querySelectorAll(".sa-attendee-row");
    const noMatchRow = document.getElementById("saNoMatchRow");

    function filterAttendees() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const selectedStatus = statusFilter ? statusFilter.value : "all";
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute("data-name") || "";
            const status = row.getAttribute("data-status") || "";

            const matchesName = name.includes(query);
            const matchesStatus = (selectedStatus === "all" || status === selectedStatus);

            if (matchesName && matchesStatus) {
                row.style.display = "";
                visibleCount++;
                const snCell = row.querySelector(".row-sn");
                if (snCell) snCell.textContent = visibleCount;
            } else {
                row.style.display = "none";
            }
        });

        if (noMatchRow) {
            noMatchRow.style.display = (visibleCount === 0 && rows.length > 0) ? "" : "none";
        }
    }

    if (searchInput) searchInput.addEventListener("input", filterAttendees);
    if (statusFilter) statusFilter.addEventListener("change", filterAttendees);
});

function downloadExcel() {
    const eventTitle = <?= json_encode($event['title']) ?>;
    const ownerName = <?= json_encode($event['username']) ?>;
    const rows = document.querySelectorAll(".sa-attendee-row");
    let tableRows = "";
    let index = 1;

    rows.forEach(row => {
        if (row.style.display !== "none") {
            const guestName = row.querySelector(".row-guest-name").innerText.trim();
            const status = row.getAttribute("data-status");
            const timestamp = row.querySelector(".row-timestamp").innerText.trim();

            tableRows += `
                <tr>
                    <td>${index++}</td>
                    <td>${escapeHtml(guestName)}</td>
                    <td>${escapeHtml(status)}</td>
                    <td>${escapeHtml(timestamp)}</td>
                </tr>`;
        }
    });

    const excelTemplate = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8"></head>
        <body>
            <table border="1">
                <thead>
                    <tr><th colspan="4" style="font-size:14pt; background:#f1f5f9;">RSVP Ledger: ${escapeHtml(eventTitle)} (Owner: ${escapeHtml(ownerName)})</th></tr>
                    <tr>
                        <th>S.N.</th>
                        <th>Guest Name</th>
                        <th>Response</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>${tableRows}</tbody>
            </table>
        </body>
        </html>`;

    const blob = new Blob([excelTemplate], { type: "application/vnd.ms-excel;charset=utf-8;" });
    const link = document.createElement("a");
    const safeFilename = eventTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase() || "super_admin_ledger";
    link.href = URL.createObjectURL(blob);
    link.download = `${safeFilename}_rsvps.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadPDF() {
    window.print();
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../includes/footer.php'; ?>