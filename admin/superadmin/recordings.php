<?php
require_once('config.php');

$lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;
if ($lead_id === 0) {
    http_response_code(400);
    die("Invalid Lead ID.");
}

/**
 * Determine audio MIME type from file extension
 * Supports: mp3, ogg/oga/opus, wav, aac, m4a/mp4, webm, flac, amr, awb
 */
function audio_mime_from_path(string $path): string {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'mp3'        => 'audio/mpeg',
        'ogg', 'oga' => 'audio/ogg',
        'opus'       => 'audio/ogg',     // Opus commonly inside Ogg
        'wav'        => 'audio/wav',
        'aac'        => 'audio/aac',
        'm4a'        => 'audio/mp4',     // AAC/ALAC in MP4/M4A
        'mp4'        => 'audio/mp4',
        'webm'       => 'audio/webm',
        'flac'       => 'audio/flac',
        'amr'        => 'audio/amr',     // AMR-NB
        'awb'        => 'audio/amr-wb',  // AMR-WB
        default      => 'audio/mpeg',    // safe fallback
    };
}

// Fetch Client Info (prepared statement)
$client_row = ['client_name' => 'N/A', 'contact' => 'N/A'];
if ($stmt = $conn->prepare("
    SELECT CONCAT(TRIM(COALESCE(firstname,'')),
                  CASE WHEN TRIM(COALESCE(middlename,'')) <> '' THEN CONCAT(' ', TRIM(middlename)) ELSE '' END,
                  CASE WHEN TRIM(COALESCE(lastname,'')) <> '' THEN CONCAT(' ', TRIM(lastname)) ELSE '' END
           ) AS client_name,
           contact
    FROM client_list
    WHERE lead_id = ?
    LIMIT 1
")) {
    $stmt->bind_param('i', $lead_id);
    $stmt->execute();
    $result_client = $stmt->get_result();
    if ($result_client && $result_client->num_rows > 0) {
        $client_row = $result_client->fetch_assoc();
    }
    $stmt->close();
}

// Fetch Recordings (prepared statement)
$recordings = [];
if ($stmt2 = $conn->prepare("
    SELECT id, phone, uploaded_at, file_path
    FROM call_recordings
    WHERE lead_id = ?
    ORDER BY uploaded_at ASC
")) {
    $stmt2->bind_param('i', $lead_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2) {
        while ($row = $res2->fetch_assoc()) {
            $recordings[] = $row;
        }
    }
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Recordings for Lead <?php echo htmlspecialchars((string)$lead_id); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root{
            --bg:#0f172a;
            --panel:#111827;
            --card:#0b1220;
            --muted:#94a3b8;
            --text:#e5e7eb;
            --brand:#6366f1;
            --brand-600:#5457ee;
            --accent:#22c55e;
            --danger:#ef4444;
            --border:#1f2937;
            --highlight:#0ea5e9;
            --shadow: 0 10px 30px rgba(0,0,0,0.35);
            --radius:14px;
        }
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;background:linear-gradient(180deg, #0b1020 0%, #0f172a 100%);color:var(--text);font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;line-height:1.45}
        a{color:var(--brand);text-decoration:none}
        a:hover{color:var(--brand-600)}
        .navbar{
            position:sticky;top:0;z-index:50;
            backdrop-filter: blur(10px);
            background: rgba(2,6,23,0.6);
            border-bottom:1px solid rgba(255,255,255,0.06);
        }
        .nav-inner{
            max-width:1100px;margin:0 auto;
            display:flex;align-items:center;justify-content:space-between;
            padding:14px 20px;
        }
        .brand{
            display:flex;align-items:center;gap:10px;font-weight:700;font-size:18px;letter-spacing:0.2px
        }
        .brand-badge{
            width:34px;height:34px;border-radius:10px;
            background: radial-gradient(120% 120% at 10% 10%, var(--brand) 0%, #22d3ee 60%, #06b6d4 100%);
            box-shadow: 0 8px 24px rgba(99,102,241,0.45), inset 0 0 18px rgba(255,255,255,0.25);
        }
        .nav-actions{display:flex;align-items:center;gap:10px}
        .btn{
            display:inline-flex;align-items:center;gap:8px;
            padding:9px 14px;border-radius:10px;border:1px solid var(--border);
            background:linear-gradient(180deg, #0f172a 0%, #0b1220 100%);
            color:var(--text);font-weight:600;transition:all .2s ease;cursor:pointer
        }
        .btn:hover{transform:translateY(-1px);border-color:#2a3547;box-shadow:0 6px 16px rgba(0,0,0,0.35)}
        .btn-outline{background:transparent}
        .btn-primary{background:linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);border-color:transparent}
        .btn-primary:hover{filter:brightness(1.05)}
        .header{max-width:1100px;margin:22px auto 0;padding:0 20px 10px}
        .page-title{font-size:28px;font-weight:800;letter-spacing:.2px;margin:14px 0 4px}
        .subhead{color:var(--muted);font-size:14px}
        .container{max-width:1100px;margin:12px auto 42px;padding:0 20px}
        .card{
            background:linear-gradient(180deg, #0b1220 0%, #0a1428 100%);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            overflow:hidden
        }
        .card-header{
            display:flex;justify-content:space-between;align-items:center;
            padding:18px 18px;border-bottom:1px solid rgba(255,255,255,0.06);
            background:linear-gradient(180deg, rgba(99,102,241,0.08), transparent)
        }
        .meta{display:flex;flex-wrap:wrap;gap:14px;color:var(--muted);font-size:14px}
        .meta strong{color:var(--text)}
        .table-wrap{width:100%;overflow:auto}
        table{border-collapse:separate;border-spacing:0;width:100%;min-width:760px}
        th,td{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:left}
        th{position:sticky;top:0;background:rgba(11,18,32,0.9);backdrop-filter:blur(6px);font-size:13px;color:#cbd5e1}
        tbody tr{transition:background .15s ease}
        tbody tr:hover{background:rgba(148,163,184,0.06)}
        td .badge{
            display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;
            border:1px solid rgba(255,255,255,0.12);color:#cbd5e1
        }
        audio{width:220px;height:34px;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.3))}
        .download-link{
            display:inline-flex;align-items:center;gap:8px;
            padding:8px 12px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);
            background:linear-gradient(180deg, #0f172a 0%, #0b1220 100%);color:var(--text);font-weight:600
        }
        .download-link:hover{border-color:#2a3547;transform:translateY(-1px)}
        .missing{color:var(--danger);font-weight:600}
        .empty{ text-align:center;color:var(--muted);padding:60px 20px }
        .empty .emoji{font-size:42px;margin-bottom:10px;opacity:.9}
        .footer-space{height:14px}
        @media (max-width:720px){
            .page-title{font-size:22px}
            .meta{font-size:13px}
            audio{width:180px}
        }
        .hint { color:#f59e0b; font-size:12px; margin-top:6px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <div class="brand">
                <div class="brand-badge" aria-hidden="true"></div>
                <span>Call Console</span>
            </div>
            <div class="nav-actions">
                <a class="btn btn-outline" href="data.php" title="Back to Client List">⬅ Back</a>
                <button class="btn btn-primary" onclick="location.reload()" title="Refresh">↻ Refresh</button>
            </div>
        </div>
    </nav>
    <header class="header">
        <div class="page-title">Call Recordings</div>
        <div class="subhead">Review, play, and download recordings associated with this lead.</div>
    </header>
    <main class="container">
        <section class="card">
            <div class="card-header">
                <div class="meta">
                    <div><span>Lead ID:</span> <strong>#<?php echo htmlspecialchars((string)$lead_id); ?></strong></div>
                    <div><span>Client:</span> <strong><?php echo htmlspecialchars($client_row['client_name'] ?? 'N/A'); ?></strong></div>
                    <div><span>Contact:</span> <strong><?php echo htmlspecialchars($client_row['contact'] ?? 'N/A'); ?></strong></div>
                </div>
            </div>
            <div class="table-wrap">
                <table role="table" aria-label="Call recordings table">
                    <thead>
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>Phone</th>
                            <th>Uploaded At</th>
                            <th>Playback</th>
                            <th style="width:140px;">Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($recordings)) {
                            foreach ($recordings as $row) {
                                $dbPath = $row['file_path']; // e.g., uploads/filename.ext
                                $webPath = '../../' . ltrim($dbPath, '/');
                                $fullPath = null;
                                if (!empty($dbPath)) {
                                    $fullPath = realpath(__DIR__ . '/../../' . ltrim($dbPath, '/'));
                                }
                                $fileExists = $fullPath && file_exists($fullPath);

                                $mime = $fileExists ? audio_mime_from_path($dbPath) : 'audio/mpeg';
                                $ext  = strtolower(pathinfo((string)$dbPath, PATHINFO_EXTENSION));
                                $mayNotPlay = in_array($ext, ['amr','awb'], true); // limited desktop support
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['id']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($row['phone'] ?: '—'); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['uploaded_at'] ?: '—'); ?></td>
                                    <td>
                                        <?php if ($fileExists) { ?>
                                            <audio controls preload="metadata">
                                                <source src="<?php echo htmlspecialchars($webPath); ?>" type="<?php echo htmlspecialchars($mime); ?>">
                                                Your browser does not support the audio element.
                                            </audio>
                                            <?php if ($mayNotPlay) { ?>
                                                <div class="hint">Note: This format (<?php echo htmlspecialchars($ext); ?>) may not play in some browsers. Use Download if playback fails.</div>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="missing">File missing</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($fileExists) { ?>
                                            <a class="download-link" href="<?php echo htmlspecialchars($webPath); ?>" download>
                                                ⬇ Download
                                            </a>
                                        <?php } else { ?>
                                            <span class="missing">Not found</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else { ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty">
                                        <div class="emoji">🔎</div>
                                        No recordings found for this lead.
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
        <div class="footer-space"></div>
    </main>
<?php $conn->close(); ?>
</body>
</html>
