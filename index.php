<?php require 'functions.php';
if (!isset($_SESSION['user_id'])) redirect('login.php');
$r=$_SESSION['role'];
// Look at the end of the next line: it now says 'guest/dashboard.php'
redirect($r==='Admin'?'admin/dashboard.php':($r==='Staff'?'staff/dashboard.php':'guest/dashboard.php'));