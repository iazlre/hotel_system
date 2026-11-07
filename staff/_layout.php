<?php
require_once __DIR__.'/../auth.php';
need_login();
if ($_SESSION['role']!=='Staff') redirect('../login.php');

function header_html($page,$title){
    $u=user(); $fs=flashes(); ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title><?=e($title)?> · Staff</title>
<link rel="stylesheet" href="../style.css"></head><body>
<div class="sidebar">
    <h2>Staff Portal</h2>
    <a href="dashboard.php"  class="<?=$page==='dashboard'?'active':''?>">Dashboard</a>
    <a href="operations.php" class="<?=$page==='ops'?'active':''?>">Operations</a>
    <div class="sidebar-footer"><div><?=e($u['name'])?><br><small style="color:#64748b"><?=e($u['staff_role'])?></small></div>
        <a href="../logout.php">Sign out</a></div>
</div>
<div class="main-content"><h1><?=e($title)?></h1>
<?php foreach($fs as $f): ?>
  <div class="flash flash-<?=e($f['t'])?>"><?=e($f['m'])?></div>
<?php endforeach;
}
function footer_html(){ echo '</div></body></html>'; }