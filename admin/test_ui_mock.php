<?php
// MOCK ENVIRONMENT
define('base_url', '../');

// Define redirect function to avoid fatal error
function redirect($url) {
    echo "<script>location.href='$url';</script>";
    exit;
}

class MockSettings {
    public function info($key) {
        if ($key == 'title') return 'Admin Panel';
        if ($key == 'name') return 'My CRM';
        if ($key == 'short_name') return 'CRM';
        if ($key == 'logo') return 'logo.png';
        return '';
    }
    public function userdata($key) {
        if ($key == 'avatar') return 'avatar.png';
        if ($key == 'firstname') return 'John';
        if ($key == 'lastname') return 'Doe';
        if ($key == 'type') return 1; // Admin
        if ($key == 'id') return 1;
        return '';
    }
}
$_settings = new MockSettings();

function validate_image($path) {
    return '../dist/img/default-150x150.png'; 
}
function isMobileDevice() { return false; }

// SETUP SESSION to bypass sess_auth.php checks
session_start();
$_SESSION['userdata'] = [
    'id' => 1,
    'firstname' => 'John',
    'lastname' => 'Doe',
    'username' => 'admin',
    'type' => 1,
    'login_type' => 1,
    'avatar' => 'uploads/avatar-1.png'
];

$_settings->userdata = $_SESSION['userdata']; // Just in case, though the class uses userdata method.

?>
<!DOCTYPE html>
<html lang="en">
<?php 
// We need to buffer output because sess_auth might try to redirect if logic fails (header already sent)
ob_start();
include 'inc/header.php'; 
ob_end_flush();
?>
<body class="layout-fixed control-sidebar-slide-open layout-navbar-fixed text-sm">
<div class="wrapper">
    <?php include 'inc/topBarNav.php'; ?>
    <?php include 'inc/navigation.php'; ?>
    
    <div class="content-wrapper pt-3">
        <section class="content">
            <div class="container-fluid">
                <!-- MOCK DASHBOARD CONTENT from home.php -->
                <div class="container pt-5">
                    <h1>Welcome to My CRM</h1>
                </div>
                <hr class="border-primary">
                
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <!-- MOCKED GLASS BOX -->
                        <div class="info-box glass-box p-3">
                            <span class="info-box-icon bg-gradient-teal elevation-1"><i class="fas fa-stream"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Leads</span>
                                <span class="info-box-number">125</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-box glass-box p-3">
                            <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-users-cog"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">System Users</span>
                                <span class="info-box-number">8</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-box p-3 mt-4">
                    <h3>📊 Staff Lead Comparison</h3>
                    <div style="height: 300px; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center;">
                        <span class="text-muted">Chart Placeholder</span>
                    </div>
                </div>
                
                <!-- Buttons Check -->
                <div class="mt-4">
                    <button class="btn btn-primary">Primary Button</button>
                    <button class="btn btn-success">Success Button</button>
                    <button class="btn btn-warning">Warning Button</button>
                    <button class="btn btn-danger">Danger Button</button>
                </div>
                
                 <!-- Table Check -->
                <div class="card mt-4">
                  <div class="card-header">
                    <h3 class="card-title">Latest Leads</h3>
                  </div>
                  <div class="card-body p-0">
                    <table class="table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Name</th>
                          <th>Status</th>
                          <th>Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>1</td>
                          <td>Jane Smith</td>
                          <td><span class="badge badge-primary">New</span></td>
                          <td>2026-01-18</td>
                        </tr>
                        <tr>
                          <td>2</td>
                          <td>Bob Jones</td>
                          <td><span class="badge badge-success">Closed</span></td>
                          <td>2026-01-17</td>
                        </tr>
                        <tr>
                          <td>3</td>
                          <td>Alice Brown</td>
                          <td><span class="badge badge-warning">Follow Up</span></td>
                          <td>2026-01-16</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- END MOCK CONTENT -->
            </div>
        </section>
    </div>
    
    <?php include 'inc/footer.php'; ?>
</div>
</body>
</html>