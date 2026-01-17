<?php
// self_destruct.php

$secretKey = 'sumit';       // GET key
$destructPassword = 'sumit123';   // Form password

// Validate GET key
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Access denied. Invalid key.');
}

// Show confirmation form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Self-Destruct Confirmation</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #111;
                color: #fff;
                text-align: center;
                padding-top: 60px;
            }
            .box {
                background: #222;
                padding: 30px;
                border: 2px solid #ff5555;
                display: inline-block;
                border-radius: 10px;
                box-shadow: 0 0 10px #ff0000;
            }
            input, button {
                padding: 10px;
                margin: 10px;
                font-size: 16px;
            }
            input {
                border-radius: 5px;
                border: 1px solid #aaa;
                width: 200px;
            }
            button {
                background: #ff0000;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: not-allowed;
            }
            button.active {
                cursor: pointer;
                background: #cc0000;
            }
        </style>
        <script>
            let countdown = 5;
            function startTimer() {
                const btn = document.getElementById('confirmBtn');
                const countdownText = document.getElementById('countdown');
                const interval = setInterval(() => {
                    countdown--;
                    countdownText.textContent = countdown;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        btn.disabled = false;
                        btn.classList.add("active");
                        btn.textContent = "YES, Destroy Everything";
                    }
                }, 1000);
            }
            window.onload = startTimer;
        </script>
    </head>
    <body>
        <div class="box">
            <h2>🚨 WARNING: PERMANENT DESTRUCTION</h2>
            <p>This will delete <strong>all files and folders</strong> in this project, including this script.</p>
            <p>Please enter the password and wait <span id="countdown">5</span> seconds to confirm.</p>
            <form method="post">
                <input type="password" name="password" placeholder="Enter password" required><br>
                <button type="submit" id="confirmBtn" disabled>Wait...</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Process deletion
if (!isset($_POST['password']) || $_POST['password'] !== $destructPassword) {
    die('<h2 style="color:red; text-align:center;">❌ Incorrect password. Access denied.</h2>');
}

function deleteFolder($folder) {
    $items = array_diff(scandir($folder), ['.', '..']);
    foreach ($items as $item) {
        $path = $folder . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteFolder($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($folder);
}

$baseDir = realpath(__DIR__);

if (!$baseDir || $baseDir === '/' || strlen($baseDir) < 10) {
    die('Aborting: Invalid base directory.');
}

$files = array_diff(scandir($baseDir), ['.', '..']);
foreach ($files as $file) {
    $path = $baseDir . DIRECTORY_SEPARATOR . $file;
    if (is_dir($path)) {
        deleteFolder($path);
    } else {
        @unlink($path);
    }
}

// Delete self
@unlink(__FILE__);

echo "<h2 style='color: red; text-align: center;'>🔥 Project and script destroyed at " . date('Y-m-d H:i:s') . "</h2>";
