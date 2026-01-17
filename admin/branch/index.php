<?php
include '../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM branch_managers WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $_SESSION['bm_user'] = $result->fetch_assoc();
        header("Location: branch_manager_dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branch Manager Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background: linear-gradient(to right, #1c92d2, #f2fcfe);
            height: 100vh;
        }
        .login-box {
            max-width: 420px;
            margin: auto;
            margin-top: 80px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .login-box h2 {
            margin-bottom: 20px;
            font-weight: bold;
        }
        .form-control {
            height: 45px;
        }
        .btn-primary {
            width: 100%;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="text-center mb-3">
            <h2><i class="fas fa-user-shield"></i> Branch Manager Login</h2>
            <p class="text-muted">Enter your credentials to access the dashboard</p>
        </div>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email <i class="fas fa-envelope"></i></label>
                <input type="email" name="email" class="form-control" placeholder="Enter email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password <i class="fas fa-lock"></i></label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center p-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Login <i class="fas fa-sign-in-alt"></i></button>
        </form>
    </div>
</body>
</html>
