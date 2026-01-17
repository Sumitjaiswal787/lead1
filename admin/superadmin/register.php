<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('config.php'); // must define $conn (mysqli)

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // QUICK CHECK: ensure $conn exists and is OK
    if (!isset($conn) || $conn->connect_error) {
        die("DB connection not available in register page.");
    }

    // Sanitize and retrieve form data
    $firstname  = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $lastname   = trim($_POST['lastname'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $type       = (int)($_POST['type'] ?? 1); // default to Admin (1)

    if ($firstname === '' || $lastname === '' || $username === '' || $password === '') {
        $message = "Please fill all required fields.";
        $message_type = "error";
    } else {
        // Map int type to role enum
        switch ($type) {
            case 1:
                $role_enum = 'admin';
                break;
            case 2:
                $role_enum = 'staff';
                break;
            case 4:
                $role_enum = 'super_admin';
                break;
            default:
                $role_enum = 'staff';
        }

        // Hash password with MD5 to match existing pattern
        $hashed_password = md5($password);

        // Prepare insert query – matches your `users` table
        $sql = "INSERT INTO users 
                    (firstname, middlename, lastname, username, password, type, date_added, role, created_by) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $message = "Prepare failed: " . $conn->error;
            $message_type = "error";
        } else {
            // For now, created_by is NULL (no foreign key enforced on null)
            $created_by = null;

            // types: s s s s s i s i
            $stmt->bind_param(
                "sssssisi",
                $firstname,
                $middlename,
                $lastname,
                $username,
                $hashed_password,
                $type,
                $role_enum,
                $created_by
            );

            if ($stmt->execute()) {
                $message = "New user registered successfully!";
                $message_type = "success";
            } else {
                $message = "Error inserting user: " . $stmt->error;
                $message_type = "error";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 28rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            color: #374151;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background-color: #2563eb;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
            margin-bottom: 0.5rem;
        }
        .btn-submit:hover {
            background-color: #1d4ed8;
        }
        .message {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
        }
        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }
        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .back-btn {
            display: inline-block;
            text-align: center;
            width: 100%;
            padding: 0.6rem 0.75rem;
            border-radius: 0.5rem;
            background: #111827;
            color: #f9fafb;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .back-btn:hover {
            background: #030712;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Register New User</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" required class="rounded-lg">
            </div>
            <div class="form-group">
                <label for="middlename">Middle Name:</label>
                <input type="text" id="middlename" name="middlename" class="rounded-lg">
            </div>
            <div class="form-group">
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" required class="rounded-lg">
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required class="rounded-lg">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required class="rounded-lg">
            </div>
            <div class="form-group">
                <label for="type">User Role:</label>
                <select id="type" name="type" required class="rounded-lg">
                    <option value="1" selected>Admin</option>
                    <option value="2">Staff</option>
                    <option value="4">Super Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-submit rounded-lg">Register</button>
            <a href="superadmin_dashboard.php" class="back-btn">
                ← Back to Dashboard
            </a>
        </form>
    </div>
</body>
</html>
