<?php
require_once 'config.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime('+30 minutes'));

    $conn->query("INSERT INTO password_resets (username, token, expires_at)
                  VALUES ('$username', '$token', '$expires')");

    $reset_link = "https://yourdomain.com/reset_password.php?token=$token";
    $message = "A reset link has been sent (displayed below for testing):<br><a href='$reset_link' class='text-blue-400 underline'>$reset_link</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black bg-cover bg-center bg-fixed min-h-screen flex items-center justify-center" style="background-image: url('/your-cover.jpg')">
    <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-8 w-full max-w-md shadow-lg text-white relative z-10">
        <h2 class="text-xl font-semibold mb-4 text-center">Forgot Password</h2>
        <?php if ($message): ?>
            <div class="bg-green-100 text-green-900 px-4 py-3 rounded mb-4 text-sm"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="block mb-1">Username</label>
                <input type="text" name="username" class="w-full px-4 py-2 rounded bg-white text-black" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">Send Reset Link</button>
        </form>
        <div class="text-center mt-4">
            <a href="login.php" class="text-sm text-gray-300 hover:underline">Back to Login</a>
        </div>
    </div>
</body>
</html>
