<?php
require_once('../config.php');

// Enable error reporting temporarily (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    switch ($_SESSION['role']) {
        // case 'super_admin':
        //     header("Location: super_admin_dashboard.php");
        //     break;
        case 'admin':
            header("Location: admin_dashboard.php");
            break;
        case 'staff':
            header("Location: staff_dashboard.php");
            break;
        default:
            session_destroy();
            header("Location: login.php?error=invalid_role_session");
            break;
    }
    exit();
}

// Handle login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $conn = $_settings->conn;
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['loggedin'] = true;

            // Optional: set admin_id if user is an admin
            if ($user['role'] === 'admin') {
                $_SESSION['admin_id'] = $user['id'];
            }

            switch ($user['role']) {
                case 'super_admin':
                    header("Location: super_admin_dashboard.php");
                    break;
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'staff':
                    header("Location: staff_dashboard.php");
                    break;
                default:
                    $_SESSION['error_message'] = "Your account has an unrecognized role.";
                    session_destroy();
                    header("Location: login.php");
                    break;
            }
            exit();
        } else {
            $_SESSION['error_message'] = "Invalid username or password.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
<body class="hold-transition ">
<script>start_loader();</script>
<style>
  html, body {
  height: 100% !important;
  width: 100% !important;
  overflow-y: auto; /* enable vertical scrolling */
  -webkit-overflow-scrolling: touch; /* smooth scrolling on iOS */
}

  body {
    background-color: #1a1a1a;
    background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
  }
  .login-title {
    font-size: 2.5em;
    font-weight: bold;
    color: white;
    text-shadow: 2px 2px 5px rgba(0,0,0,0.7);
    margin-bottom: 0px;
    letter-spacing: 2px;
  }
  #logo-img {
    height: 100px;
    width: 100px;
    margin-top: 50px;
    object-fit: scale-down;
    object-position: center center;
    border-radius: 100%;
    margin-bottom: 10px;
    background-color: rgba(255,255,255,0.1);
    padding: 5px;
  }
  .login-box {
    width: 360px;
    margin-top: 20px;
    background: #fff;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 0 15px rgba(0,0,0,0.5);
  }
  .card { border: none !important; box-shadow: none !important; }
  .card-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 15px;
    margin-bottom: 15px;
  }
  .card-header h4 { color: #007bff; font-weight: bold; }
  .input-group-text .fas { cursor: pointer; }
  .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
  }
  .btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
  }
  .login-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 20px;
  }
  .main-branding {
    text-align: center;
    margin-bottom: 40px;
  }
  @media (max-width: 768px) {
    .login-title { font-size: 2.5em; }
    .login-box { width: 90%; max-width: 360px; }
    .main-branding { margin-bottom: 20px; }
    padding-bottom: 40px;
  }
</style>

<div class="login-wrapper">
  <div class="main-branding">
    <img src="<?= validate_image($_settings->info('logo')) ?>" alt="" id="logo-img">
    <h1 class="login-title"><b><?php echo $_settings->info('name') ?></b></h1>
  </div>

  <div class="login-box">
    <div class="card card-outline card-primary rounded-0 shadow">
      <div class="card-header rounded-0">
        <h4 class="text-primary text-center"><b>Login</b></h4>
      </div>
      <div class="card-body rounded-0">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger mb-3"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success mb-3"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <form id="login-frm" action="" method="post">
          <div class="input-group mb-3">
            <input type="text" class="form-control" name="username" placeholder="Username" required autofocus>
            <div class="input-group-append">
              <div class="input-group-text"><span class="fas fa-user"></span></div>
            </div>
          </div>
          <div class="input-group mb-4">
            <input type="password" class="form-control" name="password" placeholder="Password" id="password-field" required>
            <div class="input-group-append">
              <div class="input-group-text"><span class="fas fa-lock" id="toggle-password"></span></div>
            </div>
          </div>
          <!--<div class="row">-->
          <!--  <div class="col-8">-->
          <!--    <div class="icheck-primary">-->
          <!--      <input type="checkbox" id="remember">-->
          <!--      <label for="remember">Remember Me</label>-->
          <!--    </div>-->
          <!--  </div>-->
            <div class="col-15">
              <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
            </div>
          </div>
        </form>
        
      </div>
    </div>
  </div>
</div>
<div class="text-center mt-4">
    <a href="/admin/superadmin" class="btn btn-danger btn-lg">
        🔐 Login as Super Admin
    </a>
</div>
<br>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script>
  $(document).ready(function(){
    end_loader();
    const togglePassword = document.querySelector('#toggle-password');
    const passwordField = document.querySelector('#password-field');

    if (togglePassword && passwordField) {
      togglePassword.addEventListener('click', function () {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('fa-lock');
        this.classList.toggle('fa-eye');
      });
    }
  });
</script>
</body>
</html>
