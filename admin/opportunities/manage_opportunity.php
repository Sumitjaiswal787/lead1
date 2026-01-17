<?php
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `lead_list` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
    if(isset($id)){
    $client_qry = $conn->query("SELECT * FROM `client_list` where lead_id = '{$id}' ");
    if($client_qry->num_rows > 0){
        $res = $client_qry->fetch_array();
        unset($res['id']);
        unset($res['date_created']);
        unset($res['date_updated']);
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
    }
}
?>
<div class="content py-3">
    <div class="card card-outline card-navy shadow rounded-0">
        <div class="card-header">
            <div class="card-title">
                <h5 class="card-title"><?= !isset($id) ? "Add New Lead" : "Update Lead's Information - ".$code ?></h5>
            </div>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <form action="" id="lead-form">
                    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                    <input type="hidden" name="in_opportunity" value="1">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <fieldset>
                                <legend class="text-muted h4">Client Information</legend>
                                <div class="callout rounded-0 shadow">
                                    <div class="form-group">
                                        <label for="firstname" class="control-label">First Name</label>
                                        <input type="text" name="firstname" id="firstname" autofocus class="form-control form-control-sm form-control-border" value ="<?php echo isset($firstname) ? $firstname : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="middlename" class="control-label">Middle Name</label>
                                        <input type="text" name="middlename" id="middlename" class="form-control form-control-sm form-control-border" value ="<?php echo isset($middlename) ? $middlename : '' ?>" placeholder="optional">
                                    </div>
                                    <div class="form-group">
                                        <label for="lastname" class="control-label">Last Name</label>
                                        <input type="text" name="lastname" id="lastname" class="form-control form-control-sm form-control-border" value ="<?php echo isset($lastname) ? $lastname : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender" class="control-label">Gender</label>
                                        <select name="gender" id="gender" class="form-control form-control-sm form-control-border" required>
                                            <option <?= isset($gender) && $gender == 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option <?= isset($gender) && $gender == 'Female' ? 'selected' : '' ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="dob" class="control-label">Birthday</label>
                                        <input type="date" name="dob" id="dob" class="form-control form-control-sm form-control-border" value ="<?php echo isset($dob) ? $dob : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email" class="control-label">Email</label>
                                        <input type="email" name="email" id="email" class="form-control form-control-sm form-control-border" value ="<?php echo isset($email) ? $email : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="contact" class="control-label">Contact #</label>
                                        <input type="text" name="contact" id="contact" class="form-control form-control-sm form-control-border" value ="<?php echo isset($contact) ? $contact : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="address" class="control-label">Address</label>
                                        <textarea name="address" rows="3" id="address" class="form-control form-control-sm rounded-0" required><?php echo isset($address) ? $address : '' ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="other_info" class="control-label">Other Information</label>
                                        <textarea name="other_info" rows="3" id="other_info" class="form-control form-control-sm rounded-0"><?php echo isset($other_info) ? $other_info : '' ?></textarea>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <fieldset>
                                <legend class="text-muted h4">Lead's Information</legend>
                                <div class="callout rounded-0 shadow">
                                    <div class="form-group">
                                        <label for="interested_in" class="control-label">Interested In</label>
                                        <input type="text" name="interested_in" id="interested_in" class="form-control form-control-sm form-control-border" value ="<?php echo isset($interested_in) ? $interested_in : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="source_id" class="control-label">Lead Source</label>
                                        <select name="source_id" id="source_id" class="form-control form-control-sm form-control-border select2" required>
                                            <option value="" disabled <?= !isset($source_id) ? 'selected' : '' ?>></option>
                                            <?php 
                                            $source = $conn->query("SELECT * FROM `source_list` where delete_flag = 0 and `status` = 1 ".(isset($source_id)? " or id = '{$source_id}'" : "")." order by `name` asc ");
                                            while($row = $source->fetch_assoc()):
                                            ?>
                                            <option value="<?= $row['id'] ?>" <?= isset($source_id) && $source_id == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="remarks" class="control-label">Remarks</label>
                                        <textarea name="remarks" rows="3" id="remarks" class="form-control form-control-sm rounded-0" required><?php echo isset($remarks) ? $remarks : '' ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="assigned_to" class="control-label">Assigned to</label>
                                        <select name="assigned_to" id="assigned_to" class="form-control form-control-sm form-control-border select2" >
                                            <option value="" disabled <?= !isset($assigned_to) ? 'selected' : '' ?>></option>
                                            <option value="" <?= isset($assigned_to) && $user_id == null ? 'selected' : '' ?>>Unset</option>
                                            <?php 
                                            $user = $conn->query("SELECT *,CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename,'')) as fullname FROM `users` order by CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename,'')) asc ");
                                            while($row = $user->fetch_assoc()):
                                            ?>
                                            <option value="<?= $row['id'] ?>" <?= isset($assigned_to) && $assigned_to == $row['id'] ? 'selected' : '' ?>><?= $row['fullname'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="status" class="control-label">Status</label>
                                        <select name="status" id="status" class="form-control form-control-sm form-control-border select2" required>
                                            <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>New/Prospect</option>
                                            <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>Open</option>
                                            <option value="2" <?= isset($status) && $status == 2 ? 'selected' : '' ?>>Working</option>
                                            <option value="3" <?= isset($status) && $status == 3 ? 'selected' : '' ?>>Not a Target</option>
                                            <option value="4" <?= isset($status) && $status == 4 ? 'selected' : '' ?>>Disqualified</option>
                                            <option value="5" <?= isset($status) && $status == 5 ? 'selected' : '' ?>>Nurture</option>
                                            <?php if(isset($status) && $status == 6): ?>
                                            <option value="6" <?= isset($status) && $status == 6 ? 'selected' : '' ?>>Opportunity Created</option>
                                            <?php endif; ?>
                                            <option value="7" <?= isset($status) && $status == 7 ? 'selected' : '' ?>>Opportunity Lost</option>
                                            <option value="8" <?= isset($status) && $status == 8 ? 'selected' : '' ?>>Inactive</option>
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
                <button class="btn btn-primary btn-flat" type="submit" form="lead-form">Save Lead Information</button>
                <a class="btn btn-light border btn-flat" href="./?page=leads" form="lead-form">Cancel</a>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('.select2').select2({
            placeholder:'Please Select Here',
            width:'100%'
        })
        $('#lead-form').submit(function(e){
            e.preventDefault();
            var _this = $(this)
            if(_this[0].checkValidity() == false){
                _this[0].reportValidity();
                return false;
            }
            $('.pop-msg').remove()
            var el = $('<div>')
                el.addClass("pop-msg alert")
                el.hide()
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=save_lead",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
                success:function(resp){
                    if(resp.status == 'success'){
                        location.href = "./?page=opportunities";
                    }else if(!!resp.msg){
                        el.addClass("alert-danger")
                        el.text(resp.msg)
                        _this.prepend(el)
                    }else{
                        el.addClass("alert-danger")
                        el.text("An error occurred due to unknown reason.")
                        _this.prepend(el)
                    }
                    el.show('slow')
                    $('html,body,.modal').animate({scrollTop:0},'fast')
                    end_loader();
                }
            })
        })
    })
</script>