<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('config.php');

// ✅ Check session
if (!isset($_SESSION['username'])) {
    header("Location: superadmin_login.php");
    exit;
}

// ✅ Fetch user info
$currentUser = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $currentUser);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$success = $error = '';

// ✅ Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name']);
    $newPass = trim($_POST['new_password']);
    $avatar = $_FILES['avatar'];
    $filename = $user['avatar']; // Keep old avatar by default

    // ✅ Validate avatar upload
    if (!empty($avatar['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($avatar['type'], $allowedTypes)) {
            $error = "❌ Only JPG, PNG, GIF, or WebP files allowed.";
        } elseif ($avatar['size'] > $maxSize) {
            $error = "❌ File too large. Max 2MB allowed.";
        } else {
            $uploadDir = "uploads/avatars/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = uniqid() . "_" . basename($avatar['name']);
            $uploadPath = $uploadDir . $filename;
            move_uploaded_file($avatar["tmp_name"], $uploadPath);
        }
    }

    if (empty($error)) {
        // ✅ Hash new password if provided, otherwise keep existing
        $hashedPassword = $newPass ? password_hash($newPass, PASSWORD_DEFAULT) : $user['password'];

        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, avatar=? WHERE id=?");
        $stmt->bind_param("sssi", $newName, $hashedPassword, $filename, $user['id']);

        if ($stmt->execute()) {
            $_SESSION['username'] = $newName;
            $_SESSION['avatar'] = $filename;
            $success = "✅ Profile updated successfully.";
            $user['username'] = $newName;
            $user['avatar'] = $filename;
        } else {
            $error = "❌ Update failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Super Admin Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: #f0f2f5;
        }
        .card {
            border-radius: 1rem;
        }
        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow-sm">
                <h4 class="mb-4 text-center"><i class="fas fa-user-circle"></i> Edit Profile</h4>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><strong>Username / Name</strong></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label><strong>New Password</strong> <small>(Leave blank to keep current)</small></label>
                        <input type="password" name="new_password" class="form-control">
                    </div>

                    <div class="form-group">
                        <label><strong>Avatar</strong></label><br>
                        <img src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.png') ?>"
                             class="avatar-preview mb-2"
                             alt="Avatar"
                             onerror="this.src='uploads/avatars/default.png'">
                        <input type="file" name="avatar" class="form-control-file" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">💾 Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
