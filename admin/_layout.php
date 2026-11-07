<?php
require_once __DIR__.'/../auth.php';
need_role('Admin');

function header_html($page,$title){
    $u=user(); $fs=flashes();
    ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title><?=e($title)?> · Admin</title>
<link rel="stylesheet" href="../style.css"></head><body>
<div class="sidebar">
    <h2>Hotel Admin</h2>
    <a href="dashboard.php"          class="<?=$page==='dashboard'?'active':''?>">Dashboard</a>
    <a href="bookings.php"           class="<?=$page==='bookings'?'active':''?>">Bookings</a>
    <a href="reservations.php"       class="<?=$page==='reservations'?'active':''?>">Room Reservations</a>
    <a href="cafe_reservations.php"  class="<?=$page==='cafe'?'active':''?>">Café Reservations</a>
    <a href="rooms.php"              class="<?=$page==='rooms'?'active':''?>">Rooms</a>
    <a href="guests.php"             class="<?=$page==='guests'?'active':''?>">Guests</a>
    <a href="staff.php"              class="<?=$page==='staff'?'active':''?>">Staff</a>
    <a href="housekeeping.php"       class="<?=$page==='housekeeping'?'active':''?>">Housekeeping</a>
    <a href="users.php"              class="<?=$page==='users'?'active':''?>">User Accounts</a>
    <div class="sidebar-footer"><div><?=e($u['name'])?></div>
        <a href="../logout.php">Sign out</a></div>
</div>
<div class="main-content">
    <h1><?=e($title)?></h1>
    <?php foreach($fs as $f): ?>
      <div class="flash flash-<?=e($f['t'])?>"><?=e($f['m'])?></div>
    <?php endforeach;
}
function footer_html(){ echo '</div></body></html>'; }
