<?php
session_start();
session_destroy();
header("Location: branch_manager_login.php");
exit;
