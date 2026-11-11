<?php require __DIR__.'/_layout.php'; $u=user(); $gid=(int)$u['guest_id'];

if (isset($_GET['cancel']) && isset($_GET['type'])){
    $id=(int)$_GET['cancel'];
    if ($_GET['type']==='room'){
        $conn->query("UPDATE room_reservations SET reservation_status='Cancelled' WHERE reservation_id=$id AND guest_id=$gid AND reservation_status='Confirmed'");
        $conn->query("UPDATE rooms SET status='Vacant' WHERE room_id=(SELECT room_id FROM room_reservations WHERE reservation_id=$id)");
        flash('success','Reservation cancelled.');
    } elseif ($_GET['type']==='cafe'){
        $conn->query("UPDATE cafe_reservations SET status='Cancelled' WHERE reservation_id=$id AND guest_id=$gid AND status IN ('Confirmed','Seated')");
        $conn->query("UPDATE cafe_tables SET status='Available' WHERE table_id=(SELECT table_id FROM cafe_reservations WHERE reservation_id=$id)");
        flash('success','Café reservation cancelled.');
    }
    redirect('my_bookings.php');
}

header_html('mine','My Bookings'); ?>

<h2 style="font-size:18px;color:#1e293b;margin:20px 0 10px;">Room Reservations</h2>
<table style="margin-bottom:30px;">
<!-- BINAGO: Nilagyan ng Action header -->
<tr><th>ID NUMBER</th><th>Room</th><th>In</th><th>Out</th><th>Status</th><th>Actions</th></tr>
<?php 
$r=$conn->query("SELECT rr.*,rm.room_number FROM room_reservations rr JOIN rooms rm ON rr.room_id=rm.room_id
  WHERE rr.guest_id=$gid ORDER BY rr.reservation_id DESC");
if($r->num_rows===0) echo "<tr><td colspan='6' class='empty'>No reservations yet.</td></tr>";
while($x=$r->fetch_assoc()):
 $cls=['Pending'=>'bg-yellow','Confirmed'=>'bg-blue','Cancelled'=>'bg-red','Converted to Booking'=>'bg-green'][$x['reservation_status']] ?? 'bg-gray'; ?>
<tr>
    <td><?= $x['reservation_id'] ?></td>
    <td><?=e($x['room_number'])?></td>
    <td><?=dt($x['expected_check_in'])?></td>
    <td><?=dt($x['expected_check_out'])?></td>
    <td><span class="badge <?=$cls?>"><?=e($x['reservation_status'])?></span></td>
    <td style="display:flex; gap:5px;">
        <!-- BAGONG BUTTON PARA SA RESERVATION SLIP -->
        <a href="receipt.php?type=reservation&id=<?=$x['reservation_id']?>" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration:none; padding:6px 10px;">🧾 Slip</a>
        
        <?php if($x['reservation_status']==='Confirmed'): ?>
            <a href="my_bookings.php?cancel=<?=$x['reservation_id']?>&type=room" class="btn btn-delete btn-sm" onclick="return confirm('Cancel?')">Cancel</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>

<h2 style="font-size:18px;color:#1e293b;margin:20px 0 10px;">Active / Past Stays</h2>
<table style="margin-bottom:30px;">
<tr><th>ID NUMBER</th><th>Room</th><th>In</th><th>Out</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
<?php 
$r=$conn->query("SELECT b.*,rm.room_number FROM booking_list b JOIN rooms rm ON b.room_id=rm.room_id
  WHERE b.guest_id=$gid ORDER BY b.booking_id DESC");
if($r->num_rows===0) echo "<tr><td colspan='8' class='empty'>No bookings yet.</td></tr>";
while($x=$r->fetch_assoc()):
 $cls=$x['booking_status']==='Checked-In'?'bg-blue':($x['booking_status']==='Cancelled'?'bg-red':'bg-gray'); ?>
<tr>
    <td><?= $x['booking_id'] ?></td>
    <td><?=e($x['room_number'])?></td>
    <td><?=dt($x['check_in'])?></td>
    <td><?=dt($x['check_out'])?></td>
    <td><?=money($x['total_amount'])?></td>
    <td><span class="badge <?=$x['payment_status']==='Paid'?'bg-green':($x['payment_status']==='Unpaid'?'bg-red':'bg-yellow')?>"><?=e($x['payment_status'])?></span></td>
    <td><span class="badge <?=$cls?>"><?=e($x['booking_status'])?></span></td>
    <td>
        <!-- UPDATED LINK PARA SA OFFICIAL RECEIPT -->
        <a href="receipt.php?type=booking&id=<?=$x['booking_id']?>" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration:none; padding:6px 10px;">🧾 Receipt</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<h2 style="font-size:18px;color:#1e293b;margin:20px 0 10px;">Café Reservations</h2>
<table>
<tr><th>ID NUMBER</th><th>Table</th><th>Date</th><th>Time</th><th>Pax</th><th>Status</th><th>Actions</th></tr>
<?php 
$r=$conn->query("SELECT cr.*,ct.table_number FROM cafe_reservations cr JOIN cafe_tables ct ON cr.table_id=ct.table_id
  WHERE cr.guest_id=$gid ORDER BY cr.reservation_id DESC");
if($r->num_rows===0) echo "<tr><td colspan='7' class='empty'>No café reservations.</td></tr>";
while($x=$r->fetch_assoc()):
 $cls=['Confirmed'=>'bg-blue','Seated'=>'bg-yellow','Completed'=>'bg-green','Cancelled'=>'bg-red'][$x['status']] ?? 'bg-gray'; ?>
<tr>
    <td><?= $x['reservation_id'] ?></td>
    <td><?=e($x['table_number'])?></td>
    <td><?=d($x['reservation_date'])?></td>
    <td><?=substr($x['reservation_time'],0,5)?></td>
    <td><?=$x['number_of_guests']?></td>
    <td><span class="badge <?=$cls?>"><?=e($x['status'])?></span></td>
    <td><?php if(in_array($x['status'],['Confirmed'])): ?>
        <a href="my_bookings.php?cancel=<?=$x['reservation_id']?>&type=cafe" class="btn btn-delete btn-sm" onclick="return confirm('Cancel?')">Cancel</a><?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php footer_html();