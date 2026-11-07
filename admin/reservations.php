<?php require __DIR__.'/_layout.php';

// --- DELETE RESERVATION ---
if (isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM room_reservations WHERE reservation_id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        flash('success', "Reservation #$id deleted.");
    } else {
        flash('error', "Failed to delete: " . $conn->error);
    }
    redirect('reservations.php');
}

// --- CHECK-IN / CONVERT TO BOOKING ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['convert'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('reservations.php'); }
    $rid=(int)$_POST['rid'];
    $r=$conn->query("SELECT * FROM room_reservations WHERE reservation_id=$rid")->fetch_assoc();
    if ($r && $r['reservation_status']==='Confirmed'){
        $nights=max(1,(strtotime($r['expected_check_out'])-strtotime($r['expected_check_in']))/86400);
        $price=$conn->query("SELECT price_per_night FROM rooms WHERE room_id=".(int)$r['room_id'])->fetch_assoc()['price_per_night'];
        $total=$nights*$price; $staff=(int)($_SESSION['staff_id'] ?? 0);
        $stmt=$conn->prepare("INSERT INTO booking_list (reservation_id,guest_id,room_id,handled_by_staff_id,check_in,check_out,total_amount,payment_status,booking_status)
          VALUES (?,?,?,?,NOW(),?,?,'Unpaid','Checked-In')");
        $stmt->bind_param("iiiisd",$rid,$r['guest_id'],$r['room_id'],$staff,$r['expected_check_out'],$total);
        $stmt->execute();
        $conn->query("UPDATE room_reservations SET reservation_status='Converted to Booking' WHERE reservation_id=$rid");
        $conn->query("UPDATE rooms SET status='Occupied' WHERE room_id=".(int)$r['room_id']);
        flash('success','Reservation converted. Total: '.money($total));
    }
    redirect('reservations.php');
}

// --- CANCEL RESERVATION ---
if (isset($_GET['cancel'])){
    $rid=(int)$_GET['cancel'];
    $r=$conn->query("SELECT * FROM room_reservations WHERE reservation_id=$rid")->fetch_assoc();
    if ($r){
        $conn->query("UPDATE room_reservations SET reservation_status='Cancelled' WHERE reservation_id=$rid");
        $conn->query("UPDATE rooms SET status='Vacant' WHERE room_id=".(int)$r['room_id']);
        flash('success',"Reservation #$rid cancelled.");
    }
    redirect('reservations.php');
}

header_html('reservations','Room Reservations'); ?>
<table>
<tr><th>#</th><th>Guest</th><th>Room</th><th>In</th><th>Out</th><th>Status</th><th>Actions</th></tr>
<?php $r=$conn->query("SELECT rr.*,g.first_name,g.last_name,rm.room_number FROM room_reservations rr
  JOIN guests g ON rr.guest_id=g.guest_id JOIN rooms rm ON rr.room_id=rm.room_id
  ORDER BY rr.reservation_id DESC");
if($r->num_rows===0) echo "<tr><td colspan='7' class='empty'>No reservations.</td></tr>";
while($x=$r->fetch_assoc()):
 $cls=['Pending'=>'bg-yellow','Confirmed'=>'bg-blue','Cancelled'=>'bg-red','Converted to Booking'=>'bg-green'][$x['reservation_status']] ?? 'bg-gray'; ?>
<tr><td>#<?=$x['reservation_id']?></td><td><?=e($x['first_name'].' '.$x['last_name'])?></td>
<td><?=e($x['room_number'])?></td><td><?=dt($x['expected_check_in'])?></td>
<td><?=dt($x['expected_check_out'])?></td>
<td><span class="badge <?=$cls?>"><?=e($x['reservation_status'])?></span></td>
<td>
    <!-- IDINAGDAG: Actions container -->
    <div style="display: flex; gap: 5px; align-items: center;">
        <?php if($x['reservation_status']==='Confirmed'): ?>
            <form method="POST" style="display:inline; margin:0;"><?=csrf_field()?>
                <input type="hidden" name="rid" value="<?=$x['reservation_id']?>">
                <button name="convert" class="btn btn-primary btn-sm">Check-In</button>
            </form>
            <a href="reservations.php?cancel=<?=$x['reservation_id']?>" class="btn btn-secondary btn-sm" onclick="return confirm('Cancel?')">Cancel</a>
        <?php endif; ?>
        
        <!-- IDINAGDAG: Delete Button (Lalabas sa lahat ng records) -->
        <a href="reservations.php?delete=<?=$x['reservation_id']?>" class="btn btn-delete btn-sm" onclick="return confirm('Delete this reservation record?')">Delete</a>
    </div>
</td></tr>
<?php endwhile; ?></table>
<?php footer_html();