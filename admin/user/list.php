<?php
// ✅ Block access for non-admins
if ($_settings->userdata('type') != 4 && $_settings->userdata('type') != 1) {
    echo "<script>alert_toast('Access Denied!','error'); location.href = './';</script>";
    exit;
}

// ✅ Show flash success message
if ($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
    .img-avatar{
        width:45px;
        height:45px;
        object-fit:cover;
        object-position:center center;
        border-radius:100%;
    }
    .truncate-1 {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
        display: inline-block;
    }
    @media (max-width: 768px) {
        .truncate-1 {
            max-width: 100px;
        }
        .img-avatar {
            width: 35px;
            height: 35px;
        }
        .table th, .table td {
            font-size: 13px;
        }
    }
</style>

<div class="card card-outline card-primary">
	<div class="card-header d-flex justify-content-between align-items-center flex-wrap">
		<h3 class="card-title mb-2 mb-md-0">List of System Users</h3>
		<div class="card-tools">
			<a href="?page=user/manage_user" class="btn btn-flat btn-primary btn-sm">
				<span class="fas fa-plus"></span> Create New
			</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="table-responsive"><!-- ✅ Responsive wrapper -->
				<table class="table table-hover table-striped align-middle">
					<thead class="thead-light">
						<tr>
							<th>#</th>
							<th>Avatar</th>
							<th>Name</th>
							<th>Username</th>
							<th>User Type</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php 
							$i = 1;
							$current_admin_id = $_settings->userdata('id');
							$current_user_type = $_settings->userdata('type');

							$where = " WHERE 1=0"; // block by default

							if ($current_user_type == 4) {
								// Super Admin sees everyone except self
								$where = " WHERE id != '{$current_admin_id}'";
							} elseif ($current_user_type == 1) {
								// Sub-admin sees only their own staff
								$where = " WHERE admin_id = '{$current_admin_id}' AND id != '{$current_admin_id}'";
							}

							$qry = $conn->query("SELECT *, CONCAT(firstname,' ',lastname) as name FROM `users` {$where} ORDER BY CONCAT(firstname,' ',lastname) ASC");
							while($row = $qry->fetch_assoc()):
						?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td class="text-center">
								<img src="<?php echo validate_image($row['avatar']) ?>" class="img-avatar img-thumbnail p-0 border-2" alt="user_avatar">
							</td>
							<td><?php echo ucwords($row['name']) ?></td>
							<td><p class="m-0 truncate-1"><?php echo $row['username'] ?></p></td>
							<td><p class="m-0"><?php echo ($row['type'] == 1 )? "Sub Admin" : (($row['type'] == 4) ? "Super Admin" : "Staff") ?></p></td>
							<td class="text-center">
								<div class="dropdown">
									<button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
										Action
									</button>
									<div class="dropdown-menu">
										<a class="dropdown-item" href="?page=user/manage_user&id=<?php echo $row['id'] ?>">
											<span class="fa fa-edit text-primary"></span> Edit
										</a>
										<div class="dropdown-divider"></div>
										<?php if($row['status'] != 1): ?>
										<a class="dropdown-item verify_user" href="javascript:void(0)" 
										   data-id="<?= $row['id'] ?>"  
										   data-name="<?= $row['username'] ?>">
										   <span class="fa fa-check text-primary"></span> Verify
										</a>
										<div class="dropdown-divider"></div>
										<?php endif; ?>
										<a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
											<span class="fa fa-trash text-danger"></span> Delete
										</a>
									</div>
								</div>
							</td>
						</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
	$('.delete_data').click(function(){
		_conf("Are you sure to delete this User permanently?","delete_user",[$(this).attr('data-id')])
	})
	$('.verify_user').click(function(){
		_conf("Are you sure to verify <b>"+$(this).attr('data-name')+"</b>?","verify_user",[$(this).attr('data-id')])
	})

	// ✅ Responsive DataTable
	$('.table').dataTable({
		responsive: true,
		pageLength: 10,
		autoWidth: false
	});
})

function delete_user($id){
	start_loader();
	$.ajax({
		url:_base_url_+"classes/Users.php?f=delete",
		method:"POST",
		data:{id: $id},
		dataType:"json",
		error:err=>{
			console.log(err)
			alert_toast("An error occurred.",'error');
			end_loader();
		},
		success:function(resp){
			if(typeof resp== 'object' && resp.status == 'success'){
				location.reload();
			}else{
				alert_toast("An error occurred.",'error');
				end_loader();
			}
		}
	})
}
function verify_user($id){
	start_loader();
	$.ajax({
		url:_base_url_+"classes/Users.php?f=verify_user",
		method:"POST",
		data:{id: $id},
		dataType:"json",
		error:err=>{
			console.log(err)
			alert_toast("An error occurred.",'error');
			end_loader();
		},
		success:function(resp){
			if(typeof resp== 'object' && resp.status == 'success'){
				location.reload();
			}else{
				alert_toast("An error occurred.",'error');
				end_loader();
			}
		}
	})
}
</script>
