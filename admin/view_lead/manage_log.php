<?php
require_once('../../config.php');
$lead_id = isset($_GET['lid']) ? $_GET['lid'] : '';
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `log_list` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
}
?>
<div class="container-fluid">
    <form action="" id="log-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="lead_id" value="<?php echo isset($lead_id) ? $lead_id : '' ?>">
        <div class="form-group">
            <label for="log_type" class="control-label">Log Type</label>
            <select name="log_type" id="log_type" class="form-control form-control-sm form-control-border" required>
                <option value="1" <?= isset($log_type) && $log_type == 1 ? 'selected' : '' ?>>Outbound</option>
                <option value="2" <?= isset($log_type) && $log_type == 2 ? 'selected' : '' ?>>Inbound</option>
            </select>
        </div>
        <div class="form-group">
            <label for="remarks" class="control-label">Remarks</label>
            <textarea rows="4" name="remarks" id="remarks" class="form-control form-control-sm rounded-0" placeholder="Write Remark here" required><?php echo isset($remarks) ? $remarks : '' ?></textarea>
        </div>
    </form>
</div>
<script>
    $(function(){
        $('#uni_modal').on('shown.bs.modal',function(){
            $('.select2').select2({
                placeholder:'Please select here',
                width:'100%',
                dropdownParent: $('#uni_modal')
            })
        })
        $('#uni_modal #log-form').submit(function(e){
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
                url:_base_url_+"classes/Master.php?f=save_log",
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
                        location.reload();
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