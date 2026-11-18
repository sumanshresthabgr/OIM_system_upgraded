<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$stmt = $pdo->prepare("SELECT i.*, COUNT(r.id) as total_rsvps FROM invitations i LEFT JOIN rsvps r ON i.id = r.invitation_id WHERE i.admin_id = :admin_id GROUP BY i.id ORDER BY i.created_at DESC");
$stmt->execute(['admin_id' => $_SESSION['admin_id']]);
$invitations = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
    .dashboard-container {
        padding: 10px;
    }
    .input-copy-group {
        display: flex;
        width: 100%;
        min-width: 280px;
    }
    .input-copy-group input {
        flex-grow: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .input-copy-group button.copy-btn {
        border-radius: 0;
        border-left: 0;
    }
    .input-copy-group button.qr-btn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: 0;
        background-color: #19191a;
        color: #ffffff;
    }
    .action-cell {
        white-space: nowrap; 
        display: flex; 
        gap: 6px; 
        justify-content: flex-start; 
        align-items: center;
    }

    /* QR Modal Styles */
    .qr-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .qr-modal-backdrop.active {
        display: flex;
    }
    .qr-modal-box {
        background: #ffffff;
        border-radius: 12px;
        max-width: 360px;
        width: 100%;
        padding: 24px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
    }
    #qrCanvasContainer {
        display: inline-block;
        padding: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin: 16px 0;
    }
    #qrCanvasContainer img,
    #qrCanvasContainer canvas {
        display: block;
        margin: 0 auto;
    }
    
    /* Mobile Alignment and Transformation Fixes */
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .dashboard-header-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .dashboard-header-actions a {
            width: 100%;
            text-align: center;
        }
        .input-copy-group {
            min-width: 100%;
        }
        
        table.dashboard-table, 
        table.dashboard-table thead, 
        table.dashboard-table tbody, 
        table.dashboard-table th, 
        table.dashboard-table td, 
        table.dashboard-table tr { 
            display: block; 
        }
        
        table.dashboard-table thead tr { 
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        
        table.dashboard-table tr {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 10px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        table.dashboard-table td { 
            border: none;
            position: relative;
            padding: 8px 5px !important;
            width: 100%;
            box-sizing: border-box;
        }
        
        table.dashboard-table td:nth-of-type(1):before { content: "Event: "; font-weight: bold; color: #4a5568; }
        table.dashboard-table td:nth-of-type(2):before { content: "Published: "; font-weight: bold; color: #4a5568; }
        table.dashboard-table td:nth-of-type(3):before { content: "Theme: "; font-weight: bold; color: #4a5568; }
        table.dashboard-table td:nth-of-type(4):before { content: "Tallies: "; font-weight: bold; color: #4a5568; }
        table.dashboard-table td:nth-of-type(5):before { content: "Share Link & QR: "; font-weight: bold; color: #4a5568; display: block; margin-bottom: 5px; }
        
        .action-cell {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 8px;
            margin-top: 5px;
            border-top: 1px solid #edf2f7;
            padding-top: 12px !important;
        }
        .action-cell a {
            text-align: center;
            flex: 1;
            min-width: 80px;
            font-size: 0.8rem !important;
            padding: 8px 4px !important;
        }
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-weight: 700; margin-bottom: 5px;">Live Host Tracking Dashboard</h2>
            <p style="color: #64748b; margin: 0;">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></p>
        </div>
        <div class="dashboard-header-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
                <a href="super_admin_dashboard.php" class="btn btn-outline">Main Admin Panel</a>
            <?php endif; ?>
            <a href="create_event.php" class="btn btn-primary">+ Design New Invitation Model</a>
        </div>
    </div>

    <?php if (empty($invitations)): ?>
        <div class="alert alert-info">
            <h4>No invitations registered on your profile yet.</h4>
            <p style="margin-top: 5px; font-size: 0.9rem;">Deploy your first dynamic aesthetic Invitation right now!</p>
        </div>
    <?php else: ?>
        <div class="card" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: transparent; border: none;">
            <div class="table-responsive" style="overflow-x: visible; -webkit-overflow-scrolling: touch;">
                <table class="dashboard-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Published</th>
                            <th>Theme</th>
                            <th>Tallies</th>
                            <th>Link & QR</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invitations as $row): 
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $hostName = $_SERVER['HTTP_HOST'];
                            $currentDir = dirname($_SERVER['REQUEST_URI']); 
                            $rootDir = str_replace('/admin', '', $currentDir); 
                            
                            $publicUrl = $protocol . "://" . $hostName . $rootDir . "/view.php?id=" . $row['share_token'];
                            $btnId = "copyBtn_" . $row['id'];
                        ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="font-weight: 600; padding: 12px;"><?= htmlspecialchars($row['title']) ?></td>
                            <td style="padding: 12px;"><span style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($row['date_time']) ?></span></td>
                            <td style="padding: 12px;"><span class="badge badge-secondary"><?= htmlspecialchars($row['theme']) ?></span></td>
                            <td style="padding: 12px;"><span class="badge badge-info"><?= (int)$row['total_rsvps'] ?> Records</span></td>
                            <td style="padding: 12px;">
                                <div class="input-copy-group">
                                    <input type="text" class="form-control" style="padding: 6px; font-size:0.85rem;" value="<?= htmlspecialchars($publicUrl) ?>" readonly>
                                    <button class="btn btn-outline copy-btn" style="padding: 6px 12px; font-size:0.85rem; background-color: #edf2f7;" type="button" id="<?= $btnId ?>" onclick="copyToClipboardMobile('<?= htmlspecialchars($publicUrl, ENT_QUOTES) ?>', '<?= $btnId ?>')">Copy</button>
                                    <button class="btn qr-btn" style="padding: 6px 12px; font-size:0.85rem;" type="button" onclick="openQrModal('<?= htmlspecialchars($publicUrl, ENT_QUOTES) ?>', '<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>')">QR</button>
                                </div>
                            </td>
                            <td class="action-cell" style="padding: 12px;">
                                <a href="view_rsvps.php?id=<?= $row['id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">Open Ledger</a>
                                <a href="edit_event.php?id=<?= $row['id'] ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem; background-color: #3182ce; border-color: #3182ce;">Edit</a>
                                <a href="delete_event.php?id=<?= $row['id'] ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem; background-color: #e53e3e; border-color: #e53e3e; color: white;" onclick="return confirm('⚠️ Are you completely sure you want to drop this invitation layout sequence? All associated guest RSVPs will be immediately purged!');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="qr-modal-backdrop" onclick="closeQrModal(event)">
    <div class="qr-modal-box" onclick="event.stopPropagation()">
        <h3 id="qrModalTitle" style="margin: 0 0 6px; font-size: 1.15rem; color: #1b1b1b;">Event QR Code</h3>
        <p style="margin: 0; font-size: 0.85rem; color: #343639;">Scan with a phone camera to open the invitation</p>
        
        <div id="qrCanvasContainer"></div>
        
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 8px;">
            <button type="button" class="btn btn-primary" style="flex: 1;" onclick="downloadCurrentQr()">Download</button>
            <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeQrModal()">Close</button>
        </div>
    </div>
</div>

<!-- QRCode.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
let currentQrTitle = 'invitation';

function openQrModal(url, title) {
    currentQrTitle = title;
    document.getElementById('qrModalTitle').textContent = title;
    
    const container = document.getElementById('qrCanvasContainer');
    container.innerHTML = ''; // Clear previous QR
    
    new QRCode(container, {
        text: url,
        width: 200,
        height: 200,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    document.getElementById('qrModal').classList.add('active');
}

function closeQrModal() {
    document.getElementById('qrModal').classList.remove('active');
}

function downloadCurrentQr() {
    const container = document.getElementById('qrCanvasContainer');
    const img = container.querySelector('img');
    const canvas = container.querySelector('canvas');
    
    const dataUrl = (img && img.src && img.src.startsWith('data:')) 
        ? img.src 
        : canvas.toDataURL('image/png');
        
    const safeName = currentQrTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = safeName + '_qr_invite.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function copyToClipboardMobile(text, buttonId) {
    const btn = document.getElementById(buttonId);
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            handleCopySuccess(btn);
        }).catch(err => {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.width = "2em";
    textArea.style.height = "2em";
    textArea.style.padding = "0";
    textArea.style.border = "none";
    textArea.style.outline = "none";
    textArea.style.boxShadow = "none";
    textArea.style.background = "transparent";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    if (navigator.userAgent.match(/ipad|ipod|iphone/i)) {
        const range = document.createRange();
        range.selectNodeContents(textArea);
        const select = window.getSelection();
        select.removeAllRanges();
        select.addRange(range);
        textArea.setSelectionRange(0, 999999);
    }

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            handleCopySuccess(btn);
        } else {
            alert('Unable to copy. Please copy manually.');
        }
    } catch (err) {
        alert('Copy failing on this mobile architecture.');
    }

    document.body.removeChild(textArea);
}

function handleCopySuccess(btn) {
    const originalText = btn.textContent;
    btn.textContent = "Copied!";
    btn.style.backgroundColor = "#48bb78";
    btn.style.color = "white";
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.backgroundColor = "#edf2f7";
        btn.style.color = "";
    }, 2000);
}
</script>

<?php include '../includes/footer.php'; ?>