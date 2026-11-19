<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$invitation_id = (int) $_GET['id'];

// Restructured event query to pull smoothly by Invitation ID parameter
$stmt_event = $pdo->prepare("SELECT * FROM invitations WHERE id = :id");
$stmt_event->execute(['id' => $invitation_id]);
$event = $stmt_event->fetch();
// Prevent unauthorized access
if (!$event) {
    http_response_code(403);
    exit("Access denied. You cannot access this invitation.");
}

// Get the current filename dynamically so it never throws a 404 on submission
$current_page = basename(__FILE__);

// Secure Deletion processing router logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rsvp_id'])) {
    $delete_rsvp_id = (int) $_POST['delete_rsvp_id'];

    // Verify the target entry strictly belongs to this valid controlled invitation reference link
    $stmt_del = $pdo->prepare("DELETE FROM rsvps WHERE id = :id AND invitation_id = :invitation_id");
    $stmt_del->execute([
        'id' => $delete_rsvp_id,
        'invitation_id' => $invitation_id
    ]);

    header("Location: " . $current_page . "?id=" . $invitation_id);
    exit();
}

$stmt_rsvps = $pdo->prepare("SELECT * FROM rsvps WHERE invitation_id = :invitation_id ORDER BY submitted_at DESC");
$stmt_rsvps->execute(['invitation_id' => $invitation_id]);
$rsvps = $stmt_rsvps->fetchAll();

// Calculate operational transaction metrics for the ledger header
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
    /* CSS Grid Fluid Layout Adjustments for Responsive Display */
    .ledger-action-btn {
        background: #ef4444;
        color: #ffffff;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .ledger-action-btn:hover {
        background: #dc2626;
    }

    .ledger-flex-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 15px;
        font-family: 'Montserrat', sans-serif;
    }

    /* Search & Export Toolbar */
    .ledger-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 20px;
        padding: 14px 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .ledger-search-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        flex: 1;
        min-width: 240px;
    }

    .ledger-search-input {
        padding: 8px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        flex: 1;
        min-width: 190px;
        max-width: 320px;
    }

    .ledger-search-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .ledger-filter-select {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.88rem;
        background: #f8fafc;
        color: #1e293b;
        cursor: pointer;
    }

    .ledger-export-group {
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
        .ledger-flex-container {
            flex-direction: column;
            gap: 10px;
        }

        .ledger-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .ledger-search-input {
            max-width: 100%;
        }

        .ledger-export-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .dashboard-table thead {
            display: none;
            /* Hide standard head blocks on small viewports */
        }

        .dashboard-table,
        .dashboard-table tbody,
        .dashboard-table tr,
        .dashboard-table td {
            display: block;
            width: 100%;
        }

        .dashboard-table tr {
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            background: #ffffff;
            box-sizing: border-box;
        }

        .dashboard-table td {
            text-align: right !important;
            padding-left: 50% !important;
            position: relative;
            border: none !important;
            box-sizing: border-box;
            min-height: 35px;
        }

        .dashboard-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            width: 45%;
            font-weight: 700;
            text-align: left;
            color: #475569;
        }
    }

    /* Clean PDF / Print Layout Rules */
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

<div style="margin-bottom: 25px;">
    <a href="dashboard.php" class="btn btn-outline no-print"
        style="padding: 6px 12px; margin-bottom: 15px; display: inline-block; text-decoration: none;">&larr; Back to
        Console</a>
    <h2 style="word-wrap: break-word; overflow-wrap: break-word;">Interactive Guest Ledger: <span
            style="color: #2563eb;"><?= htmlspecialchars($event['title']) ?></span></h2>

    <div class="ledger-flex-container">
        <div
            style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 600; text-align: center;">
            Attending: <span style="font-weight: 700; font-size: 1rem;"><?= $count_attending ?></span>
        </div>
        <div
            style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 600; text-align: center;">
            Declined: <span style="font-weight: 700; font-size: 1rem;"><?= $count_declined ?></span>
        </div>
    </div>

    <?php if (!empty($rsvps)): ?>
        <div class="ledger-toolbar no-print">
            <div class="ledger-search-group">
                <input type="text" id="attendeeSearchInput" class="ledger-search-input"
                    placeholder="🔍 Search attendee name...">
                <select id="statusFilterSelect" class="ledger-filter-select">
                    <option value="all">All Statuses (<?= count($rsvps) ?>)</option>
                    <option value="Attending">Attending Only (<?= $count_attending ?>)</option>
                    <option value="Declined">Declined Only (<?= $count_declined ?>)</option>
                </select>
            </div>

            <div class="ledger-export-group">
                <button type="button" onclick="downloadExcel()" class="btn-export-excel">📊 Download Excel</button>
                <button type="button" onclick="downloadPDF()" class="btn-export-pdf">📄 Download PDF</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="dashboard-table" id="guestLedgerTable">
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Guest Nominal Trace</th>
                    <th>Dual-State Feedback</th>
                    <th>Timestamp</th>
                    <th class="col-actions" style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rsvps)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b;">No transaction entries recorded.</td>
                    </tr>
                <?php else:
                    $sn = 1;
                    foreach ($rsvps as $rsvp): ?>
                        <tr class="attendee-row"
                            data-name="<?= htmlspecialchars(strtolower($rsvp['guest_name'])) ?>"
                            data-status="<?= htmlspecialchars($rsvp['status']) ?>">
                            <td data-label="S.N." class="row-sn" style="font-weight: 700;"><?= $sn++ ?></td>
                            <td data-label="Guest Nominal Trace" class="row-guest-name" style="word-break: break-all;">
                                <?= htmlspecialchars($rsvp['guest_name']) ?></td>
                            <td data-label="Dual-State Feedback" class="row-status">
                                <?php if ($rsvp['status'] === 'Attending'): ?>
                                    <span class="badge badge-success">Attending ✓</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Declined ✗</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Timestamp" class="row-timestamp"><span
                                    style="font-size:0.85rem; color:#64748b;"><?= htmlspecialchars($rsvp['submitted_at']) ?></span>
                            </td>
                            <td data-label="Actions" class="col-actions" style="text-align: center;">
                                <form action="<?= htmlspecialchars($current_page) ?>?id=<?= $invitation_id ?>" method="POST"
                                    onsubmit="return confirm('Are you sure you want to completely drop this guest transaction record?');"
                                    style="display: inline-block; margin: 0;">
                                    <input type="hidden" name="delete_rsvp_id" value="<?= $rsvp['id'] ?>">
                                    <button type="submit" class="ledger-action-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="noSearchMatchRow" style="display: none;">
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">
                            No attendees match your current search or filter.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("attendeeSearchInput");
    const statusFilter = document.getElementById("statusFilterSelect");
    const rows = document.querySelectorAll(".attendee-row");
    const noMatchRow = document.getElementById("noSearchMatchRow");

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

// Export currently visible/filtered rows as an Excel-compatible file (.xls)
function downloadExcel() {
    const eventTitle = <?= json_encode($event['title']) ?>;
    const rows = document.querySelectorAll(".attendee-row");
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
                    <tr><th colspan="4" style="font-size:14pt; background:#f1f5f9;">Guest List: ${escapeHtml(eventTitle)}</th></tr>
                    <tr>
                        <th>S.N.</th>
                        <th>Guest Name</th>
                        <th>Status</th>
                        <th>Submitted Timestamp</th>
                    </tr>
                </thead>
                <tbody>${tableRows}</tbody>
            </table>
        </body>
        </html>`;

    const blob = new Blob([excelTemplate], { type: "application/vnd.ms-excel;charset=utf-8;" });
    const link = document.createElement("a");
    const safeFilename = eventTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase() || "guest_ledger";
    link.href = URL.createObjectURL(blob);
    link.download = `${safeFilename}_attendees.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Trigger browser PDF save/print with clean ledger print styling
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