<?php
error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1); // Show errors in browser
ini_set('display_startup_errors', 1);
require_once("../config.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// only admin allowed
$admin_id = $_SESSION['admin_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // clear old links
    $conn->query("DELETE FROM sheet_links WHERE admin_id = $admin_id");

    // insert new links (max 5)
    for ($i = 1; $i <= 5; $i++) {
        $link = trim($_POST["sheet_$i"] ?? '');
        if ($link !== '') {
            $conn->query("INSERT INTO sheet_links (admin_id, sheet_url) VALUES ($admin_id, '{$conn->real_escape_string($link)}')");
        }
    }
    echo "<div style='background:#e6ffed;color:#006d32;padding:10px;border-radius:8px;margin-bottom:15px;'>✅ Links updated!</div>";
}

// fetch existing
$res = $conn->query("SELECT * FROM sheet_links WHERE admin_id = $admin_id LIMIT 5");
$links = [];
while ($r = $res->fetch_assoc()) {
    $links[] = $r['sheet_url'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Manage Google Sheet Links</title>
    <a href="https://lead1.aurifie.com/admin/" style="
    display: inline-block;
    padding: 10px 20px;
    background: #6b7280;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    margin-bottom: 15px;
    transition: background 0.25s;
">
    ← Back to Home
</a>

    <style>
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #f9fafb;
            padding: 20px;
            color: #333;
        }
        h2 {
            text-align: center;
            color: #222;
            margin-bottom: 20px;
        }
        form {
            max-width: 700px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .field {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #444;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border 0.2s;
        }
        input[type="text"]:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        button {
            display: block;
            width: 100%;
            padding: 12px;
            background: #2563eb;
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.25s;
        }
        button:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>
    <h2>Manage Google Sheet Links</h2>
    <form method="post">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="field">
                <label for="sheet_<?= $i ?>">Sheet <?= $i ?></label>
                <input type="text" id="sheet_<?= $i ?>" name="sheet_<?= $i ?>" value="<?= htmlspecialchars($links[$i-1] ?? '') ?>" placeholder="Enter Google Sheet export CSV link...">
            </div>
        <?php endfor; ?>
        <button type="submit">💾 Save Links</button>
    </form>
</body>
</html>
