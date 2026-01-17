<?php require_once('sess_auth.php'); ?>

<head>

  <style>
    :root {
      --base_url:
        <?php echo base_url ?>
      ;
    }
  </style>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>
    <?php echo $_settings->info('title') ? $_settings->info('title') . ' | ' : '' ?>
    <?php echo $_settings->info('name') ?>
  </title>
  <link rel="icon" href="<?php echo validate_image($_settings->info('logo')) ?>" />

  <!-- Google Font: Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- CSS Libraries -->
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet"
    href="<?php echo base_url ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/jqvmap/jqvmap.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/fullcalendar/main.css">
  <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.css">
  <link rel="stylesheet" href="<?php echo base_url ?>dist/css/custom.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">

  <style>
    .list-group:empty {
      content: "No data listed yet.";
      font-style: italic;
      color: #5e6366;
      text-align: center
    }
  </style>

  <!-- JS Libraries -->
  <script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
  <script src="<?php echo base_url ?>plugins/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?php echo base_url ?>plugins/sweetalert2/sweetalert2.min.js"></script>
  <script src="<?php echo base_url ?>plugins/toastr/toastr.min.js"></script>
  <script>
    var _base_url_ = '<?php echo base_url ?>';
  </script>
  <script src="<?php echo base_url ?>dist/js/script.js"></script>
</head>