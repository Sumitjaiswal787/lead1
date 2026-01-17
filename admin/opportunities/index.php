<style>
    .img-thumb-path{
        height:100px;
        width:80px;
        object-fit:scale-down;
        object-position:center center;
    }
</style>
<div class="card card-outline card-primary rounded-0 shadow">
	<div class="card-header">
		<h3 class="card-title">List of Opportunities</h3>
		<?php if($_settings->userdata('type') == 1): ?>
		<div class="card-tools">
			<a href="./?page=opportunities/manage_opportunity" class="btn btn-flat btn-sm btn-primary"><span class="fas fa-plus"></span>  Add New Leads</a>
		</div>
		<?php endif; ?>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="row justify-content-center mb-3">
				<div class="col-lg-5 col-md-6 col-sm-12">
					<div class="input-group input-group-sm">
						<div class="input-group-prepend">
							<span class="input-group-text">Search</span>
						</div>
						<input type="search" id="search" class="form-control">
						<div class="input-group-append">
							<span class="input-group-text"><i class="fa fa-search"></i></span>
						</div>
					</div>
				</div>
			</div>
			<div class="list-group" id="lead-list">
				<?php 
				$uwhere = "";
				if($_settings->userdata('type') != 1)
				$uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";
				$users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 1 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 1 {$uwhere})");
				$user_arr = array_column($users->fetch_all(MYSQLI_ASSOC),'fullname','id');
				$leads = $conn->query("SELECT l.*,CONCAT(c.lastname,', ', c.firstname, '', COALESCE(c.middlename,'')) as client, c.email FROM `lead_list` l inner join client_list c on c.lead_id = l.id where l.in_opportunity = 1 {$uwhere} order by l.`status` asc, unix_timestamp(l.date_created) asc ");
				while($row = $leads->fetch_assoc()):
				?>
				<div class="list-group-item list-group-item-action list-item rounded-0">
					<h4 class="truncate-1"><?= $row['remarks'] ?></h4>
					<div class="row">
						<div class="col-lg-6 col-sm-12">
							<div class="d-flex">
								<div class="col-auto text-muted pl-3">Ref. Code:</div>
								<div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= $row['code'] ?></b></p></div>
							</div>
							<div class="d-flex">
								<div class="col-auto text-muted pl-3">Client:</div>
								<div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= ucwords($row['client']) ?></b></p></div>
							</div>
							<div class="d-flex">
								<div class="col-auto text-muted pl-3">Email:</div>
								<div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= $row['email'] ?></b></p></div>
							</div>
						</div>
						<div class="col-lg-6 col-sm-12">
							<div class="d-flex">
								<div class="col-auto text-muted pl-3">Interested In:</div>
								<div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= $row['interested_in'] ?></b></p></div>
							</div>
							<div class="d-flex">
								<div class="col-auto text-muted pl-3">Assigned To:</div>
								<div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= (isset($user_arr[$row['assigned_to']])) ? ucwords($user_arr[$row['assigned_to']]) : "Not Assigned Yet." ?></b></p></div>
							</div>
							<div class="d-flex">
								<div class="col-auto text-muted pl-3">Status:</div>
								<div class="col-auto flex-grow-1 flex-shrink-1">
									<?php 
										switch($row['status']){
											case 0:
												echo '<span class="badge badge-primary bg-gradient-primary px-3 rounded-pill">New/Prospect</span>';
												break;
											case 1:
												echo '<span class="badge badge-light bg-gradient-light border px-3 rounded-pill">Open</span>';
												break;
											case 2:
												echo '<span class="badge badge-primary bg-gradient-primary px-3 rounded-pill">Working</span>';
												break;
											case 3:
												echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill">Not a Target</span>';
												break;
											case 4:
												echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill">Disqualified</span>';
												break;
											case 5:
												echo '<span class="badge badge-info bg-gradient-info px-3 rounded-pill">Nurture</span>';
												break;
											case 6:
												echo '<span class="badge badge-success bg-gradient-success px-3 rounded-pill">Opportunity Created</span>';
												break;
											case 7:
												echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill">Opportunity Lost</span>';
												break;
											case 8:
												echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill">Inactive</span>';
												break;
											default:
												echo '<span class="badge badge-light bg-gradient-light border px-3 rounded-pill">N/A</span>';
													break;
										}
									?>
								</div>
							</div>
						</div>
					</div>
					<div class="clear-fix my-2"></div>
					<span class="text-muted"><em>Created by <?= isset($user_arr[$row['user_id']]) ? ucwords($user_arr[$row['user_id']]) : "N/A" ?> <?= date("D M d, Y h:i A",strtotime($row['date_created'])) ?></em></span>
					<div class="clear-fix my-2"></div>
					<div class="text-right">
						<a class="btn btn-sm btn-flat btn-light border" href="./?page=view_lead&id=<?= $row['id'] ?>"><i class="fa fa-eye"></i> View</a>
						<?php if($_settings->userdata('type') == 1): ?>
						<a class="btn btn-sm btn-flat btn-primary" href="./?page=opportunities/manage_opportunity&id=<?= $row['id'] ?>"><i class="fa fa-edit"></i> Edit</a>
						<a class="btn btn-sm btn-flat btn-danger delete_data" data-id="<?= $row['id'] ?>"><i class="fa fa-trash"></i> Delete</a>
						<?php endif; ?>
					</div>

				</div>
				<?php endwhile; ?>
			</div>
			<div class="text-center d-none" id="noData"><center>No result.</center></div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this Lead Information permanently?","delete_lead",[$(this).attr('data-id')])
		})
		$('#search').on('input',function(){
			var _search = $(this).val().toLowerCase();
			$('#lead-list .list-item').each(function(){
				var txt = $(this).text().toLowerCase()
				if(txt.includes(_search) == true){
					$(this).toggle(true)
				}else{
					$(this).toggle(false)
				}
			})
			if($('#lead-list .list-item:visible').length > 0){
				$('#noData').addClass('d-none')
			}else{
				$('#noData').removeClass('d-none')
			}
		})
	})
	function delete_lead($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_lead",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>