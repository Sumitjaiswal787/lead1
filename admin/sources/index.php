<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Inter&display=swap');

/* Desktop/Web Styling (Unchanged) */
body, table, button {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(145deg,#fff6e8 60%,#faf5e3 100%);
  color: #1C1C1C;
  margin: 0;
  padding: 0;
}
.card {
  border-radius: 1rem;
  box-shadow: 0 6px 18px rgba(212,175,55,0.12);
  background: #fff;
  border: none;
}
.card-header {
  background-color: #1C1C1C;
  color: #D4AF37;
  padding: 1.15rem 2rem;
  font-family: 'Poppins', sans-serif;
  font-weight: 700;
  font-size: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 1rem 1rem 0 0;
  white-space: nowrap;
}
.card-title {
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-tools {
  white-space: nowrap;
}
.card-tools a#create_new {
  background-color: #fff;
  color: #1C1C1C;
  border-radius: 2rem;
  font-weight: 700;
  padding: 0.85rem 2rem;
  font-size: 1.25rem;
  box-shadow: 0 2px 20px rgba(212,175,55,0.16);
  border: none;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  user-select: none;
}
.card-tools a#create_new:hover,
.card-tools a#create_new:focus {
  background: #D4AF37;
  color: #fff;
  border: none;
  text-decoration: none;
}
.fa-plus {
  font-size: 1.4rem;
}

@media (min-width: 992px) {
  .mobile-leadsource-list { display: none !important; }
  table.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 20px;
    font-size: 1.1rem;
    color: #1C1C1C;
    table-layout: fixed;
  }
  thead {
    background: #1C1C1C;
  }
  thead tr { border-radius: 0; }
  thead th {
    padding: 1.2rem 1.2rem;
    color: #D4AF37;
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    background: #1C1C1C;
    text-align: left;
    border: none;
    white-space: nowrap;
    user-select: none;
  }
  tbody tr {
    background: rgba(212, 175, 55, 0.08);
    border-radius: 1.2rem;
    box-shadow: 0 10px 18px rgba(212, 175, 55, 0.15);
    transition: box-shadow 0.35s ease, background-color 0.35s ease, transform 0.35s ease;
  }
  tbody tr:hover {
    background-color: #D4AF37;
    color: #1C1C1C;
    box-shadow: 0 14px 30px rgba(212, 175, 55, 0.4);
    transform: translateY(-2px);
  }
  tbody td {
    padding: 1.3rem 1.5rem;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-family: 'Inter', sans-serif;
  }
  .badge-success {
    background: linear-gradient(135deg, #D4AF37 40%, #F1E6A0 95%);
    color: #1C1C1C;
    font-weight: 700;
    padding: 0.45em 1.2em;
    border-radius: 2rem;
    font-size: 0.95rem;
    box-shadow: 0 0 18px #D4AF37;
    min-width: 90px;
    text-align: center;
    user-select: none;
    display: inline-block;
  }
  .badge-danger {
    background: linear-gradient(135deg, #2F80ED 40%, #2296f3 95%);
    color: #fff;
    font-weight: 700;
    padding: 0.45em 1.2em;
    border-radius: 2rem;
    font-size: 0.95rem;
    box-shadow: 0 0 18px #2F80ED;
    min-width: 90px;
    text-align: center;
    user-select: none;
    display: inline-block;
  }
  .btn-flat {
    padding: 0.5rem 1.2rem;
    font-weight: 700;
    font-size: 1.05rem;
    border-radius: 1rem;
    border: none;
    user-select: none;
    box-shadow: 0 5px 18px rgba(212, 175, 55, 0.15);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
  }
  .btn-default {
    background: #fff;
    color: #1C1C1C;
    border: 2px solid #D4AF37;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 110px;
    font-size: 1.05rem;
  }
  .btn-default:hover,
  .btn-default:focus {
    background-color: #D4AF37;
    color: #1C1C1C;
    border-color: #b69317;
    box-shadow: 0 10px 40px rgba(212, 175, 55, 0.6);
    outline: none;
    transform: scale(1.05);
    text-decoration: none;
  }
  .dropdown-menu {
    border-radius: 1rem;
    box-shadow: 0 14px 50px rgba(0, 0, 0, 0.3);
    min-width: 13rem;
    background-color: #fff;
    font-family: 'Inter', sans-serif;
    font-weight: 700;
    font-size: 1rem;
  }
  .dropdown-item {
    padding: 0.7rem 2rem;
    color: #1C1C1C;
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  .dropdown-item:hover,
  .dropdown-item:focus {
    background-color: #D4AF37;
    color: #141414;
    outline: none;
    cursor: pointer;
  }
  .fa-eye { color: #D4AF37; transition: color 0.3s ease; }
  .fa-edit { color: #b69317; transition: color 0.3s ease; }
  .fa-trash { color: #2296f3; transition: color 0.3s ease; }
}

/* --- MOBILE CARD LEADS SOURCE FIX --- */
@media (max-width: 991.98px) {
  .mobile-leadsource-list {
    display: block;
    padding: 0 0.25rem;
  }
  table, thead, tbody, th, td, tr { display: none; }
  .mobile-leadsource-card {
    background: #fbf6e7;
    margin-bottom: 1.5rem;
    border-radius: 1.2rem;
    box-shadow: 0 6px 20px rgba(212,175,55,0.13);
    padding: 1.6rem 1.3rem 1.3rem 1.3rem;
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.8rem;
    user-select: none;
  }
  .mobile-leadsource-row {
    display: flex;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 0.7rem;
    word-break: break-word;
  }
  .mobile-leadsource-label {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    color: #D4AF37;
    font-size: 1.15rem;
    margin-right: 0.8rem;
    min-width: 105px;
    white-space: nowrap;
    flex-shrink: 0;
    margin-bottom: 0.2em;
  }
  .mobile-leadsource-value {
    font-family: 'Inter', sans-serif;
    color: #222;
    font-size: 1.12rem;
    word-break: break-word;
    background: none;
    line-height: 1.35;
    flex: 1 1 0;
    margin-bottom: 0.2em;
  }
  .mobile-leadsource-card .badge-success,
  .mobile-leadsource-card .badge-danger {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 65px;
    min-height: 38px;
    padding: 0 1.4em;
    border-radius: 999px;
    font-size: 1.07rem;
    font-family: 'Poppins', sans-serif;
    white-space: nowrap;
    text-align: center;
    box-sizing: border-box;
    margin-bottom: 0.25em;
    background: linear-gradient(135deg,#ffe8a3 35%,#d4af37 95%);
    color: #1c1c1c;
  }
  .mobile-leadsource-card .badge-danger {
    background: linear-gradient(135deg,#2296f3 35%,#2f80ed 95%);
    color: #fff;
  }
  .mobile-leadsource-row.status {
    align-items: center;
  }
  .mobile-leadsource-row.status .mobile-leadsource-label {
    margin-bottom: 0;
  }
  .mobile-leadsource-row.status .mobile-leadsource-value {
    margin-bottom: 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
  }
  .mobile-leadsource-action {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.25rem;
    justify-content: center;
  }
  .mobile-leadsource-action button {
    flex: 1 1 38%;
    min-width: 120px;
    max-width: 210px;
    padding: 1rem 0;
    border-radius: 2rem;
    font-size: 1.15rem;
    font-weight: 600;
    box-shadow: 0 2px 12px rgba(212,175,55,0.10);
    border: 2px solid #D4AF37;
    background: #fff;
    color: #1C1C1C;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    margin-bottom: 0.8rem;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
  }
  .mobile-leadsource-action button:hover {
    background: #D4AF37;
    color: #fff;
    box-shadow: 0 4px 14px rgba(212,175,55,0.17);
  }
}
</style>

<div class="card card-outline shadow rounded-0">
  <div class="card-header">
    <h3 class="card-title">List of Leads Sources</h3>
    <?php if($_settings->userdata('type') == 1): ?>
      <div class="card-tools">
        <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-default">
          <span class="fas fa-plus"></span> Add New Source
        </a>
      </div>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <div class="container-fluid p-3">
      <!-- Desktop Table -->
      <table class="table table-hover">
        <colgroup>
          <col width="5%">
          <col width="15%">
          <col width="25%">
          <col width="25%">
          <col width="15%">
          <col width="15%">
        </colgroup>
        <thead>
          <tr>
            <th>#</th>
            <th>Date Created</th>
            <th>Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
            $i = 1;
            $qry = $conn->query("SELECT * from `source_list` where delete_flag = 0 order by `name` asc ");
            while($row = $qry->fetch_assoc()):
          ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
            <td><p class="m-0 truncate-1"><?php echo $row['name'] ?></p></td>
            <td><p class="m-0 truncate-1"><?php echo $row['description'] ?></p></td>
            <td class="text-center">
              <?php 
                switch ($row['status']){
                  case 1:
                    echo '<span class="badge-success">Active</span>';
                    break;
                  case 0:
                    echo '<span class="badge-danger">Inactive</span>';
                    break;
                }
              ?>
            </td>
            <td align="center">
              <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Action<span class="sr-only">Toggle Dropdown</span>
              </button>
              <div class="dropdown-menu" role="menu">
                <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                  <span class="fa fa-eye"></span> View
                </a>
                <?php if($_settings->userdata('type') == 1): ?>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                  <span class="fa fa-edit"></span> Edit
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                  <span class="fa fa-trash"></span> Delete
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Mobile Cards -->
      <div class="mobile-leadsource-list">
        <?php 
          $i = 1;
          $qry = $conn->query("SELECT * from `source_list` where delete_flag = 0 order by `name` asc ");
          while($row = $qry->fetch_assoc()):
        ?>
        <div class="mobile-leadsource-card">
          <div class="mobile-leadsource-row">
            <span class="mobile-leadsource-label">#</span>
            <span class="mobile-leadsource-value"><?php echo $i++; ?></span>
          </div>
          <div class="mobile-leadsource-row">
            <span class="mobile-leadsource-label">Date Created</span>
            <span class="mobile-leadsource-value">
              <?php echo date("Y-m-d",strtotime($row['date_created'])); ?><br>
              <?php echo date("H:i",strtotime($row['date_created'])); ?>
            </span>
          </div>
          <div class="mobile-leadsource-row">
            <span class="mobile-leadsource-label">Name</span>
            <span class="mobile-leadsource-value"><?php echo $row['name'] ?></span>
          </div>
          <div class="mobile-leadsource-row">
            <span class="mobile-leadsource-label">Description</span>
            <span class="mobile-leadsource-value"><?php echo $row['description'] ?></span>
          </div>
          <div class="mobile-leadsource-row status">
            <span class="mobile-leadsource-label">Status</span>
            <span class="mobile-leadsource-value">
              <?php 
                switch ($row['status']){
                  case 1: echo '<span class="badge-success">Active</span>'; break;
                  case 0: echo '<span class="badge-danger">Inactive</span>'; break;
                }
              ?>
            </span>
          </div>
          <div class="mobile-leadsource-row mobile-leadsource-action">
            <button class="view_data" data-id="<?php echo $row['id'] ?>"><span class="fa fa-bars"></span> View</button>
            <?php if($_settings->userdata('type') == 1): ?>
              <button class="edit_data" data-id="<?php echo $row['id'] ?>"><span class="fa fa-bars"></span> Edit</button>
              <button class="delete_data" data-id="<?php echo $row['id'] ?>"><span class="fa fa-bars"></span> Delete</button>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#create_new').click(function(){
    uni_modal("Add New Source","sources/manage_source.php")
  });
  $('.view_data').click(function(){
    uni_modal("Source Details","sources/view_source.php?id="+$(this).attr('data-id'));
  });
  $('.edit_data').click(function(){
    uni_modal("Update Source Details","sources/manage_source.php?id="+$(this).attr('data-id'));
  });
  $('.delete_data').click(function(){
    _conf("Are you sure to delete this Source permanently?","delete_source",[$(this).attr('data-id')]);
  });
  $('.table').dataTable({
    columnDefs: [
      { orderable: false, targets: 5 }
    ],
    autoWidth: false,
  });
  $('.table td, .table th').addClass('py-3 px-3 align-middle')
});
function delete_source($id){
  start_loader();
  $.ajax({
    url:_base_url_+"classes/Master.php?f=delete_source",
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
