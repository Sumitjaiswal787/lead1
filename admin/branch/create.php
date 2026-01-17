<?php
include '../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = md5($_POST['password']);

    $stmt = $conn->prepare("INSERT INTO branch_managers (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        $success = "✅ Branch Manager Created Successfully.";
    } else {
        $error = "❌ Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Create Branch Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

  <style>
    body {
      background: linear-gradient(to right, #1d2b64, #f8cdda);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    .form-control {
      border-radius: 10px;
      height: 45px;
    }
    .btn-primary {
      border-radius: 10px;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="card p-4" style="width: 100%; max-width: 480px;">
  <div class="text-center mb-4">
    <h3><i class="fas fa-user-plus text-primary"></i> Create Branch Manager</h3>
    <p class="text-muted">Fill the form to create a new branch manager</p>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Name <i class="fas fa-user"></i></label>
      <input type="text" name="name" class="form-control" placeholder="Manager's name" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Email <i class="fas fa-envelope"></i></label>
      <input type="email" name="email" class="form-control" placeholder="Manager's email" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Password <i class="fas fa-lock"></i></label>
      <input type="password" name="password" class="form-control" placeholder="Manager's password" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">
      <i class="fas fa-save"></i> Create Manager
    </button>
  </form>
</div>

</body>
</html>
