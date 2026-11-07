<?php require __DIR__.'/_layout.php';
$s = [
 'rooms'     => $conn->query("SELECT COUNT(*) c FROM rooms")->fetch_assoc()['c'],
 'occupied'  => $conn->query("SELECT COUNT(*) c FROM rooms WHERE status='Occupied'")->fetch_assoc()['c'],
 'guests'    => $conn->query("SELECT COUNT(*) c FROM guests")->fetch_assoc()['c'],
 'bookings'  => $conn->query("SELECT COUNT(*) c FROM booking_list WHERE booking_status='Checked-In'")->fetch_assoc()['c'],
 'pending'   => $conn->query("SELECT COUNT(*) c FROM room_reservations WHERE reservation_status='Confirmed'")->fetch_assoc()['c'],
];
header_html('dashboard','Dashboard'); ?>
<div class="card-grid">
 <div class="stat-card"><h3>Total Rooms</h3><div class="value"><?=$s['rooms']?></div></div>
 <div class="stat-card"><h3>Occupied</h3><div class="value" style="color:#ef4444"><?=$s['occupied']?></div></div>
 <div class="stat-card"><h3>Total Guests</h3><div class="value" style="color:#22c55e"><?=$s['guests']?></div></div>
 <div class="stat-card"><h3>Active Bookings</h3><div class="value" style="color:#38bdf8"><?=$s['bookings']?></div></div>
 <div class="stat-card"><h3>Pending Reservations</h3><div class="value" style="color:#f59e0b"><?=$s['pending']?></div></div>
</div>
<h2 style="color:#1e293b;font-size:20px;margin-bottom:15px;">Recent Bookings</h2>
<table>
<tr><th>#</th><th>Guest</th><th>Room</th><th>Check-In</th><th>Check-Out</th><th>Total</th><th>Status</th></tr>
<?php $r=$conn->query("SELECT b.*,g.first_name,g.last_name,rm.room_number FROM booking_list b
  JOIN guests g ON b.guest_id=g.guest_id JOIN rooms rm ON b.room_id=rm.room_id
  ORDER BY b.booking_id DESC LIMIT 5");
if($r->num_rows===0) echo "<tr><td colspan='7' class='empty'>No bookings yet.</td></tr>";
while($x=$r->fetch_assoc()):
 $cls=$x['booking_status']==='Checked-In'?'bg-blue':($x['booking_status']==='Cancelled'?'bg-red':'bg-gray'); ?>
<tr><td>#<?=$x['booking_id']?></td><td><?=e($x['first_name'].' '.$x['last_name'])?></td>
<td><?=e($x['room_number'])?></td><td><?=d($x['check_in'])?></td><td><?=d($x['check_out'])?></td>
<td><?=money($x['total_amount'])?></td><td><span class="badge <?=$cls?>"><?=e($x['booking_status'])?></span></td></tr>
<?php endwhile; ?></table>
<?php footer_html();