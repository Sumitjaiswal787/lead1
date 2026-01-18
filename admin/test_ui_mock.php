<?php
// MOCK ENVIRONMENT
define('base_url', '../');

// Define redirect function to avoid fatal error
function redirect($url)
{
    echo "<script>location.href='$url';</script>";
    exit;
}

class MockSettings
{
    public function info($key)
    {
        if ($key == 'title')
            return 'Admin Panel';
        if ($key == 'name')
            return 'My CRM';
        if ($key == 'short_name')
            return 'CRM';
        if ($key == 'logo')
            return 'logo.png';
        return '';
    }
    public function userdata($key)
    {
        if ($key == 'avatar')
            return 'avatar.png';
        if ($key == 'firstname')
            return 'John';
        if ($key == 'lastname')
            return 'Doe';
        if ($key == 'type')
            return 1; // Admin
        if ($key == 'id')
            return 1;
        return '';
    }
}
$_settings = new MockSettings();

function validate_image($path)
{
    return '../dist/img/default-150x150.png';
}
function isMobileDevice()
{
    return false;
}

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
                    <!-- Main Content Implementation -->
                    <div class="card card-outline card-primary shadow-sm" style="border-top: 3px solid #007bff;">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title"><i class="fas fa-home mr-1"></i> Lead Property List</h3>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="assigned_to" class="font-weight-bold"
                                        style="font-size: 0.9rem;">Assigned To:</label>
                                    <select class="form-control form-control-sm" id="assigned_to">
                                        <option value="">All Agents</option>
                                        <option value="1">Sumit Jaiswal</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="lead_status" class="font-weight-bold" style="font-size: 0.9rem;">Lead
                                        Status:</label>
                                    <select class="form-control form-control-sm" id="lead_status">
                                        <option value="">Filter By Status</option>
                                        <option value="1">New</option>
                                        <option value="2">In Progress</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-sm">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 40px;"><input type="checkbox"></th>
                                            <th>Client Name</th>
                                            <th>Interested In</th>
                                            <th>Contact Number</th>
                                            <th>Assigned To</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center p-5">
                                                <div class="d-flex flex-column align-items-center justify-content-center"
                                                    style="min-height: 300px; color: #ccc;">
                                                    <i class="fas fa-image fa-4x mb-3"></i>
                                                    <h3>IMAGE NOT AVAILABLE</h3>
                                                    <p>No leads found matching criteria</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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