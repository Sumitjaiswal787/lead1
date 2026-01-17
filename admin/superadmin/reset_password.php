<?php
require_once 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $result = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($result && $result->num_rows > 0) {
        $conn->query("UPDATE users SET password = '$hashed_password' WHERE username = '$username'");
        $message = "✅ Password updated successfully.";
    } else {
        $message = "❌ Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black min-h-screen flex items-center justify-center">
    <div class="bg-white bg-opacity-10 backdrop-blur-lg p-8 rounded-lg w-full max-w-md text-white shadow-lg">
        <h2 class="text-xl font-bold mb-4 text-center">Reset Password (No Auth)</h2>

        <?php if ($message): ?>
            <div class="bg-white bg-opacity-20 text-sm text-center px-4 py-2 mb-4 rounded"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block mb-1">Username</label>
                <input type="text" name="username" class="w-full px-3 py-2 text-black rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1">New Password</label>
                <input type="password" name="new_password" class="w-full px-3 py-2 text-black rounded" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">
                Reset Password
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="login.php" class="text-sm text-blue-300 hover:underline">Back to Login</a>
        </div>
    </div>
</body>
</html>
