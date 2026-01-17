<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure $conn is available (e.g., from a config file included earlier)
if (!isset($conn)) {
    // You should typically include your database connection file here
    // For example: include_once('path/to/db_connect.php');
    // For demonstration, let's assume a dummy connection if not set
    class DummyConnection {
        public function query($sql) {
            // Dummy query result for demonstration
            if (strpos($sql, 'lead_list') !== false) {
                return (object)['num_rows' => 0];
            }
            if (strpos($sql, 'client_list') !== false) {
                return (object)['num_rows' => 0];
            }
            if (strpos($sql, 'source_list') !== false) {
                return new class {
                    private $data = [
                        ['id' => 1, 'name' => 'Website'],
                        ['id' => 2, 'name' => 'Referral'],
                        ['id' => 3, 'name' => 'Social Media']
                    ];
                    private $index = 0;
                    public function fetch_assoc() {
                        if ($this->index < count($this->data)) {
                            return $this->data[$this->index++];
                        }
                        return null;
                    }
                };
            }
            if (strpos($sql, 'users') !== false) {
                return new class {
                    private $data = [
                        ['id' => 1, 'firstname' => 'John', 'middlename' => 'A.', 'lastname' => 'Doe'],
                        ['id' => 2, 'firstname' => 'Jane', 'middlename' => null, 'lastname' => 'Smith']
                    ];
                    private $index = 0;
                    public function fetch_assoc() {
                        if ($this->index < count($this->data)) {
                            return $this->data[$this->index++];
                        }
                        return null;
                    }
                };
            }
            return (object)['num_rows' => 0];
        }
    }
    $conn = new DummyConnection();
}


if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT * FROM `lead_list` WHERE id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        $res = $qry->fetch_array();
        foreach ($res as $k => $v) {
            if (!is_numeric($k)) $$k = $v;
        }
    }
    if (isset($id)) {
        $client_qry = $conn->query("SELECT * FROM `client_list` WHERE lead_id = '{$id}'");
        if ($client_qry->num_rows > 0) {
            $res = $client_qry->fetch_array();
            unset($res['id'], $res['date_created'], $res['date_updated']);
            foreach ($res as $k => $v) {
                if (!is_numeric($k)) $$k = $v;
            }
        }
    }
}
?>

<div class="content py-3">
    <div class="card card-outline card-navy shadow rounded-0 glass-morphism-card">
        <div class="card-header">
            <div class="card-title">
                <h5 class="card-title orange-text"><?= !isset($id) ? "Add New Lead" : "Update Lead's Information - " . htmlspecialchars($code ?? '') ?></h5>
            </div>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <form action="" id="lead-form">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars(isset($id) ? $id : '') ?>">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <fieldset class="glass-morphism-fieldset">
                                <legend class="text-muted h4 orange-text">Client Information</legend>
                                <div class="callout rounded-0 shadow">
                                    <div class="form-group">
                                        <label for="firstname" class="control-label orange-text">Name</label>
                                        <input type="text" name="firstname" id="firstname" autofocus class="form-control form-control-sm form-control-border glass-morphism-input" value="<?php echo htmlspecialchars(isset($firstname) ? $firstname : '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="email" class="control-label orange-text">Email</label>
                                        <input type="email" name="email" id="email" class="form-control form-control-sm form-control-border glass-morphism-input" value="<?php echo htmlspecialchars(isset($email) ? $email : '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="contact" class="control-label orange-text">Contact #</label>
                                        <input type="text" name="contact" id="contact" class="form-control form-control-sm form-control-border glass-morphism-input" value="<?php echo htmlspecialchars(isset($contact) ? $contact : '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="address" class="control-label orange-text">Address</label>
                                        <textarea name="address" rows="3" id="address" class="form-control form-control-sm rounded-0 glass-morphism-textarea"><?php echo htmlspecialchars(isset($address) ? $address : '') ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="job_title" class="control-label orange-text">Job Title</label>
                                        <input type="text" name="job_title" id="job_title" class="form-control form-control-sm form-control-border glass-morphism-input" value="<?php echo htmlspecialchars(isset($job_title) ? $job_title : '') ?>">
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <fieldset class="glass-morphism-fieldset">
                                <legend class="text-muted h4 orange-text">Lead's Information</legend>
                                <div class="callout rounded-0 shadow">
                                    <div class="form-group">
                                        <label for="project_name" class="control-label orange-text">Project Name</label>
                                        <input type="text" name="project_name" id="project_name" class="form-control form-control-sm form-control-border glass-morphism-input" value="<?= htmlspecialchars(isset($project_name) ? $project_name : '') ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="other_info" class="control-label orange-text">Other Info</label>
                                        <textarea name="other_info" id="other_info" rows="3" class="form-control form-control-sm form-control-border glass-morphism-textarea"><?= htmlspecialchars(isset($other_info) ? $other_info : '') ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="interested_in" class="control-label orange-text">Interested In</label>
                                        <input type="text" name="interested_in" id="interested_in" class="form-control form-control-sm form-control-border glass-morphism-input" value="<?php echo htmlspecialchars(isset($interested_in) ? $interested_in : '') ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="source_id" class="control-label orange-text">Lead Source</label>
                                        <select name="source_id" id="source_id" class="form-control form-control-sm form-control-border select2 glass-morphism-select" required>
                                            <option value="">-- Select Lead Source --</option>
                                            <?php
                                            $source_qry = $conn->query("SELECT id, name FROM source_list WHERE delete_flag = 0 ORDER BY name ASC");
                                            while ($row = $source_qry->fetch_assoc()):
                                            ?>
                                                <option value="<?= htmlspecialchars($row['id']) ?>" <?= (isset($source_id) && $source_id == $row['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($row['name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="remarks" class="control-label orange-text">Remarks</label>
                                        <textarea name="remarks" rows="3" id="remarks" class="form-control form-control-sm rounded-0 glass-morphism-textarea"><?php echo htmlspecialchars(isset($remarks) ? $remarks : '') ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="assigned_to" class="orange-text">Assigned to</label>
                                        <select name="assigned_to" id="assigned_to" class="form-control form-control-sm form-control-border select2 glass-morphism-select">
                                            <option value="" disabled <?= !isset($assigned_to) ? 'selected' : '' ?>>Select User</option>
                                            <?php
                                            $users = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname, ' ', COALESCE(middlename, '')) AS fullname FROM `users` ORDER BY fullname ASC");
                                            while ($u = $users->fetch_assoc()):
                                            ?>
                                                <option value="<?= htmlspecialchars($u['id']) ?>" <?= isset($assigned_to) && $assigned_to == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['fullname']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="status" class="control-label orange-text">Status</label>
                                        <select name="status" id="status" class="form-control form-control-sm form-control-border select2 glass-morphism-select">
                                            <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>Not-Interested</option>
                                            <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>Interested</option>
                                            <option value="2" <?= isset($status) && $status == 2 ? 'selected' : '' ?>>Call Back</option>
                                            <option value="3" <?= isset($status) && $status == 3 ? 'selected' : '' ?>>Not Pickup</option>
                                            <option value="4" <?= isset($status) && $status == 4 ? 'selected' : '' ?>>Invalid</option>
                                            <option value="5" <?= !isset($status) || $status == 5 ? 'selected' : '' ?>>Fresh</option>
                                            <option value="6" <?= isset($status) && $status == 6 ? 'selected' : '' ?>>Investment Done</option>
                                            <option value="7" <?= isset($status) && $status == 7 ? 'selected' : '' ?>>Site visit</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-footer py-2 text-right">
            <button class="btn btn-primary btn-flat glass-morphism-button" type="submit" form="lead-form">Save Lead Information</button>
            <a class="btn btn-light border btn-flat glass-morphism-button" href="./?page=leads">Cancel</a>
        </div>
    </div>
</div>
<script>
$(function(){
    $('.select2').select2({
        placeholder: 'Please Select Here',
        width: '100%'
    });

    $('#lead-form').submit(function(e) {
        e.preventDefault();
        var _this = $(this);
        if (_this[0].checkValidity() == false) {
            _this[0].reportValidity();
            return false;
        }
        $('.pop-msg').remove();
        var el = $('<div>').addClass("pop-msg alert").hide();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_lead",
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error: function(err) {
                console.log(err.responseText);
                alert_toast("An error occurred", 'error');
                end_loader();
            },
            success: function(resp) {
                if (resp.status === '200') {
                    location.href = "./?page=leads";
                } else if (!!resp.msg) {
                    el.addClass("alert-danger").text(resp.msg);
                } else {
                    el.addClass("alert-danger").text("An error occurred due to unknown reason.");
                }
                _this.prepend(el);
                el.show('slow');
                $('html,body,.modal').animate({scrollTop: 0}, 'fast');
                end_loader();
            }
        });
    });
});
</script>