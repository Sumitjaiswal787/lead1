<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
session_start();

// Database connection setup
$db_host = defined('DB_SERVER') ? DB_SERVER : (isset($servername) ? $servername : '127.0.0.1');
$db_user = defined('DB_USERNAME') ? DB_USERNAME : (isset($username) ? $username : 'u828453283_lead1');
$db_pass = defined('DB_PASSWORD') ? DB_PASSWORD : (isset($password) ? $password : 'Sumit@78787');
$db_name = defined('DB_NAME') ? DB_NAME : (isset($dbname) ? $dbname : 'u828453283_lead1');

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// System branding
$logo_path = '';
$cover_path = '';
$company_name = 'Sampada Investcare';

$sql_system_info = "SELECT meta_field, meta_value FROM system_info WHERE meta_field IN ('logo', 'cover', 'name')";
$result = $conn->query($sql_system_info);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['meta_field'] === 'logo') $logo_path = $row['meta_value'];
        if ($row['meta_field'] === 'cover') $cover_path = $row['meta_value'];
        if ($row['meta_field'] === 'name') $company_name = $row['meta_value'];
    }
}

// Login logic
$message = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $input_password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, firstname, lastname, username, password, type, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows) {
        $user = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($input_password, $user['password'])) {
            if ($user['type'] == 4) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['is_super_admin'] = true;
                $_SESSION['firstname'] = $user['firstname'];

                header("Location: ./superadmin_dashboard.php");
                exit;
            } else {
                $message = "Access denied. You are not authorized.";
            }
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #000;
            background-image: url('/<?php echo htmlspecialchars($cover_path); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        .container, .header-content {
            z-index: 2;
        }
        .container {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 2rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }
        .form-group {
            margin-bottom: 1rem;
            position: relative;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            border-radius: 0.5rem;
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
        }
        .form-group .icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        .btn-submit {
            width: 100%;
            background-color: #1a73e8;
            padding: 0.75rem;
            color: white;
            font-weight: bold;
            border-radius: 0.5rem;
        }
        .btn-submit:hover {
            background-color: #1558b0;
        }
        .message {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        label {
            color: white;
        }
    </style>
</head>
<body>

    <div class="header-content text-center mb-8">
        <?php if (!empty($logo_path)): ?>
            <div class="mb-4">
                <img src="/<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="mx-auto w-32 rounded-lg">
            </div>
        <?php endif; ?>
        <h1 class="text-white text-3xl font-bold drop-shadow-lg"><?php echo htmlspecialchars($company_name); ?></h1>
    </div>

    <div class="container">
        <h2 class="text-blue-500 text-xl font-bold mb-4 text-center">Login</h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
            <div class="form-group">
                <i class="fas fa-user icon"></i>
                <input type="text" name="username" placeholder="Username" required autofocus>
            </div>
            <div class="form-group">
                <i class="fas fa-lock icon"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
<!--            <div class="text-right mb-4">-->
<!--    <a href="reset_password.php" class="text-sm text-blue-300 hover:underline">Reset Password</a>-->
<!--</div>-->


            <button type="submit" class="btn-submit">Sign In</button>
        </form>
        <div class="text-center mt-4">
    <p class="text-white text-sm mb-2">Are you an Admin or Staff?</p>
    <a href="../../login.php" class="inline-block px-4 py-2 bg-gray-200 text-black font-semibold rounded hover:bg-gray-300">
        Login as Admin/Staff
    </a>
</div>
    </div>

</body>
</html>
