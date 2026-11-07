<?php
require_once __DIR__.'/../auth.php';
need_login();
if ($_SESSION['role']!=='Guest') redirect('../login.php');

function header_html($page,$title){
    global $conn;
    $u=user(); $fs=flashes(); 
    $gtype = '';
    if ($u['guest_id']) {
        $g = $conn->query("SELECT guest_type FROM guests WHERE guest_id=".(int)$u['guest_id'])->fetch_assoc();
        $gtype = $g['guest_type'] ?? '';
    }
    ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title><?=e($title)?> · Portal</title>
<link rel="stylesheet" href="../style.css"></head><body>
<div class="sidebar">
    <h2>Guest Portal</h2>
    <a href="dashboard.php"    class="<?=$page==='dash'?'active':''?>">Dashboard</a>
    <a href="book_room.php"    class="<?=$page==='book'?'active':''?>">Book a Room</a>
    <a href="book_cafe.php"    class="<?=$page==='cafe'?'active':''?>">Reserve a Café Table</a>
    <a href="my_bookings.php"  class="<?=$page==='mine'?'active':''?>">My Bookings</a>
    <!-- Removed My Profile Here -->
    <div class="sidebar-footer">
        <div><?=e($u['name'])?><br><small style="color:#64748b"><?=e($gtype)?></small></div>
        <a href="../logout.php">Sign out</a>
    </div>
</div>
<div class="main-content"><h1><?=e($title)?></h1>
<?php foreach($fs as $f): ?>
  <div class="flash flash-<?=e($f['t'])?>"><?=e($f['m'])?></div>
<?php endforeach;
}
function footer_html(){ echo '</div></body></html>'; }