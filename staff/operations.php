<?php require __DIR__.'/_layout.php';
$u=user(); $role=$u['staff_role']; $sid=(int)$u['staff_id'];

// --- Receptionist: UNDO CHECK-IN (Revert to Reservation) ---
if (isset($_GET['undo_checkin'])){
    $id=(int)$_GET['undo_checkin'];
    $b=$conn->query("SELECT * FROM booking_list WHERE booking_id=$id")->fetch_assoc();
    
    if ($b) {
        $res_id = $b['reservation_id'];
        $room_id = $b['room_id'];
        
        $conn->query("DELETE FROM booking_list WHERE booking_id=$id");
        if ($res_id) {
            $conn->query("UPDATE room_reservations SET reservation_status='Confirmed' WHERE reservation_id=$res_id");
        }
        $conn->query("UPDATE rooms SET status='Reserved' WHERE room_id=$room_id");
        flash('success', "Booking #$id has been reverted back to a Confirmed Reservation.");
    }
    redirect('operations.php');
}

// --- Receptionist: check in ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['checkin'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('operations.php'); }
    $rid=(int)$_POST['rid'];
    $in_time = $_POST['checkin_time'];
    $r=$conn->query("SELECT * FROM room_reservations WHERE reservation_id=$rid")->fetch_assoc();
    if ($r && $r['reservation_status']==='Confirmed'){
        
        $seconds_diff = strtotime($r['expected_check_out']) - strtotime($in_time);
        $nights = max(1, ceil($seconds_diff / 86400)); 
        
        $price=$conn->query("SELECT price_per_night FROM rooms WHERE room_id=".(int)$r['room_id'])->fetch_assoc()['price_per_night'];
        $total=$nights*$price;
        
        $stmt=$conn->prepare("INSERT INTO booking_list (reservation_id,guest_id,room_id,handled_by_staff_id,check_in,check_out,total_amount,payment_status,booking_status)
          VALUES (?,?,?,?,?,?,?,'Unpaid','Checked-In')");
        $stmt->bind_param("iiiissd",$rid,$r['guest_id'],$r['room_id'],$sid,$in_time,$r['expected_check_out'],$total);
        $stmt->execute();
        $conn->query("UPDATE room_reservations SET reservation_status='Converted to Booking' WHERE reservation_id=$rid");
        $conn->query("UPDATE rooms SET status='Occupied' WHERE room_id=".(int)$r['room_id']);
        flash('success','Guest checked in. Total: '.money($total));
    }
    redirect('operations.php');
}

// --- Receptionist: custom check out (AUTO-ASSIGN) ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['checkout'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('operations.php'); }
    $id=(int)$_POST['booking_id'];
    $out_time=$_POST['checkout_time'];
    $b=$conn->query("SELECT * FROM booking_list WHERE booking_id=$id")->fetch_assoc();
    
    if (strtotime($out_time) < strtotime($b['check_in'])) {
        flash('error',"Error: Check-Out date cannot be earlier than the Check-In date.");
    } else {
        if ($b && $b['booking_status']==='Checked-In'){
            $stmt=$conn->prepare("UPDATE booking_list SET check_out=?,booking_status='Checked-Out',payment_status='Paid' WHERE booking_id=?");
            $stmt->bind_param("si",$out_time,$id); $stmt->execute();
            $conn->query("UPDATE rooms SET status='Cleaning' WHERE room_id=".(int)$b['room_id']);
            
            $hk_q = $conn->query("SELECT staff_id FROM staff WHERE role='Housekeeping' AND status='Active' LIMIT 1")->fetch_assoc();
            $hk_id = $hk_q ? (int)$hk_q['staff_id'] : 'NULL';
            
            $conn->query("INSERT INTO housekeeping_logs (room_id, assigned_housekeeper_id, task_description, scheduled_date)
              VALUES (".(int)$b['room_id'].", $hk_id, 'Routine Cleaning & Bed Reset', NOW())");
              
            flash('success',"Booking #$id checked out. Room is now in Cleaning status, task assigned to Housekeeping.");
        }
    }
    redirect('operations.php');
}

// --- Café: seat / complete ---
if (isset($_GET['cafe']) && isset($_GET['to'])){
    $id=(int)$_GET['cafe']; $to=$_GET['to'];
    if (in_array($to,['Seated','Completed','Cancelled'],true)){
        $stmt=$conn->prepare("UPDATE cafe_reservations SET status=?,assigned_staff_id=? WHERE reservation_id=?");
        $stmt->bind_param("sii",$to,$sid,$id); $stmt->execute();
        $tid=(int)$conn->query("SELECT table_id FROM cafe_reservations WHERE reservation_id=$id")->fetch_assoc()['table_id'];
        $new=$to==='Seated'?'Occupied':($to==='Cancelled'?'Available':'Available');
        $conn->query("UPDATE cafe_tables SET status='$new' WHERE table_id=$tid");
        flash('success',"Café reservation #$id → $to.");
    }
    redirect('operations.php');
}

// --- Housekeeping: update task ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hk'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('operations.php'); }
    $id=(int)$_POST['log_id']; $st=$_POST['cleaning_status'];
    $stmt=$conn->prepare("UPDATE housekeeping_logs SET cleaning_status=?, assigned_housekeeper_id=? WHERE log_id=?");
    $stmt->bind_param("sii",$st,$sid,$id); $stmt->execute();
    if ($st==='Completed'){
        $rid=(int)$conn->query("SELECT room_id FROM housekeeping_logs WHERE log_id=$id")->fetch_assoc()['room_id'];
        $conn->query("UPDATE rooms SET status='Vacant' WHERE room_id=$rid");
    }
    flash('success','Task updated.'); redirect('operations.php');
}

header_html('ops','Operations');

if ($role==='Receptionist' || $role==='Manager'):
?>
<h2 style="font-size:18px;color:#1e293b;margin-bottom:10px;">Arrivals — Confirmed Reservations</h2>
<table style="margin-bottom:30px;">
<tr><th>ID NUMBER</th><th>Guest</th><th>Room</th><th>Expected In</th><th>Expected Out</th><th>Action (Check-In)</th></tr>
<?php 
$r=$conn->query("SELECT rr.*,g.first_name,g.last_name,rm.room_number,rm.price_per_night FROM room_reservations rr
  JOIN guests g ON rr.guest_id=g.guest_id JOIN rooms rm ON rr.room_id=rm.room_id
  WHERE rr.reservation_status='Confirmed' ORDER BY rr.expected_check_in");
if($r->num_rows===0) echo "<tr><td colspan='6' class='empty'>No pending arrivals.</td></tr>";
while($x=$r->fetch_assoc()): 
    $expected_time = strtotime($x['expected_check_in']);
    $is_early = date('Y-m-d', $expected_time) > date('Y-m-d');
    $is_late = ($expected_time < time()) && !$is_early; 
    
    $badge_style = "color:#b91c1c; font-size:10px; font-weight:bold; background:#fee2e2; padding:2px 6px; border-radius:4px; display:inline-block; margin-top:4px;";
?>
<tr>
    <td><?=$x['reservation_id']?></td>
    <td><?=e($x['first_name'].' '.$x['last_name'])?></td>
    <td><?=e($x['room_number'])?></td>
    <td>
        <?=dt($x['expected_check_in'])?>
        <?php if($is_early): ?>
            <br><span style="<?=$badge_style?>">⚠️ FUTURE DATE</span>
        <?php elseif($is_late): ?>
            <br><span style="<?=$badge_style?>">🔴 LATE</span>
        <?php endif; ?>
    </td>
    <td><?=dt($x['expected_check_out'])?></td>
    <td>
        <form method="POST" style="display:flex; gap:5px; flex-direction:column;">
            <?=csrf_field()?>
            <input type="hidden" name="rid" value="<?=$x['reservation_id']?>">
            <label style="font-size:10px;">Set Check-In Time:</label>
            <input type="datetime-local" name="checkin_time" id="in_<?=$x['reservation_id']?>" required value="<?=date('Y-m-d\TH:i')?>" style="padding:4px; font-size:12px;">
            
            <button name="checkin" class="btn <?= $is_early ? 'btn-delete' : 'btn-primary' ?> btn-sm" 
                onclick="return confirmEarlyCheckIn(this.form, '<?=$x['expected_check_in']?>', '<?=$x['expected_check_out']?>', <?=$x['price_per_night']?>)">
                <?= $is_early ? '⚠️ Early Check-In' : 'Confirm Check-In' ?>
            </button>
        </form>
    </td>
</tr>
<?php endwhile; ?></table>

<h2 style="font-size:18px;color:#1e293b;margin-bottom:10px;">Active Bookings — Check-Out</h2>
<table style="margin-bottom:30px;">
<tr><th>ID NUMBER</th><th>Guest</th><th>Room</th><th>Actual In</th><th>Expected Out</th><th>Total</th><th>Actions</th></tr>
<?php $r=$conn->query("SELECT b.*,g.first_name,g.last_name,rm.room_number FROM booking_list b
  JOIN guests g ON b.guest_id=g.guest_id JOIN rooms rm ON b.room_id=rm.room_id
  WHERE b.booking_status='Checked-In' ORDER BY b.check_in");
if($r->num_rows===0) echo "<tr><td colspan='7' class='empty'>No checked-in guests.</td></tr>";
while($x=$r->fetch_assoc()): ?>
<tr>
    <td><?=$x['booking_id']?></td>
    <td><?=e($x['first_name'].' '.$x['last_name'])?></td>
    <td><?=e($x['room_number'])?></td>
    <td><?=dt($x['check_in'])?></td>
    <td><?=dt($x['check_out'])?></td>
    <td><?=money($x['total_amount'])?></td>
    <td>
        <div style="display:flex; gap:5px; flex-direction:column;">
            <form method="POST" style="display:flex; gap:5px; margin:0;">
             <?=csrf_field()?>
             <input type="hidden" name="booking_id" value="<?=$x['booking_id']?>">
             <input type="datetime-local" name="checkout_time" min="<?=date('Y-m-d\TH:i', strtotime($x['check_in']))?>" required value="<?=date('Y-m-d\TH:i')?>" style="padding:4px; font-size:12px;">
             <button name="checkout" class="btn btn-delete btn-sm" style="width:100%;" onclick="return confirm('Check out this guest?')">Confirm Check-Out</button>
            </form>

            <a href="operations.php?undo_checkin=<?=$x['booking_id']?>" class="btn btn-secondary btn-sm" style="text-align:center; margin-top:5px;" 
               onclick="return confirm('Are you sure you want to UNDO this Check-In and revert it back to a Reservation?')">
               ↩️ Revert to Reservation
            </a>
        </div>
    </td>
</tr>
<?php endwhile; ?></table>
<?php endif;

if ($role==='Cafe Staff' || $role==='Manager'):
?>
<h2 style="font-size:18px;color:#1e293b;margin-bottom:10px;">Café Reservations</h2>
<table style="margin-bottom:30px;">
<tr><th>ID NUMBER</th><th>Guest</th><th>Table</th><th>Date</th><th>Time</th><th>Pax</th><th>Status</th><th>Actions</th></tr>
<?php $r=$conn->query("SELECT cr.*,g.first_name,g.last_name,ct.table_number FROM cafe_reservations cr
  JOIN guests g ON cr.guest_id=g.guest_id JOIN cafe_tables ct ON cr.table_id=ct.table_id
  WHERE cr.status IN ('Confirmed','Seated') ORDER BY cr.reservation_date,cr.reservation_time");
if($r->num_rows===0) echo "<tr><td colspan='8' class='empty'>No active café reservations.</td></tr>";
while($x=$r->fetch_assoc()):
 $cls=['Confirmed'=>'bg-blue','Seated'=>'bg-yellow'][$x['status']] ?? 'bg-gray'; ?>
<tr><td><?=$x['reservation_id']?></td><td><?=e($x['first_name'].' '.$x['last_name'])?></td>
<td><?=e($x['table_number'])?></td><td><?=d($x['reservation_date'])?></td>
<!-- BINAGO: Naka-12-hour format na -->
<td><?=date('g:i A', strtotime($x['reservation_time']))?></td>
<td><?=$x['number_of_guests']?></td>
<td><span class="badge <?=$cls?>"><?=e($x['status'])?></span></td>
<td>
<?php if($x['status']==='Confirmed'): ?>
 <a href="operations.php?cafe=<?=$x['reservation_id']?>&to=Seated" class="btn btn-primary btn-sm">Seat</a>
<?php elseif($x['status']==='Seated'): ?>
 <a href="operations.php?cafe=<?=$x['reservation_id']?>&to=Completed" class="btn btn-primary btn-sm"
  onclick="return confirm('Complete this table?')">Complete</a>
<?php endif; ?>
 <a href="operations.php?cafe=<?=$x['reservation_id']?>&to=Cancelled" class="btn btn-delete btn-sm"
  onclick="return confirm('Cancel?')">Cancel</a></td></tr>
<?php endwhile; ?></table>
<?php endif; ?>

<?php if ($role==='Housekeeping' || $role==='Manager'): ?>
<h2 style="font-size:18px;color:#1e293b;margin-bottom:10px;">Housekeeping Tasks</h2>
<table>
<tr><th>ID NUMBER</th><th>Room</th><th>Task</th><th>Assigned To</th><th>Scheduled</th><th>Status</th><th>Update</th></tr>
<?php 
$r=$conn->query("SELECT hl.*,rm.room_number, s.first_name, s.last_name FROM housekeeping_logs hl
  JOIN rooms rm ON hl.room_id=rm.room_id
  LEFT JOIN staff s ON hl.assigned_housekeeper_id=s.staff_id
  WHERE hl.cleaning_status<>'Completed'
  ORDER BY hl.scheduled_date");

if($r->num_rows===0) echo "<tr><td colspan='7' class='empty'>No open tasks. Well done!</td></tr>";
while($x=$r->fetch_assoc()):
 $cls=['Pending'=>'bg-yellow','In Progress'=>'bg-blue'][$x['cleaning_status']] ?? 'bg-gray'; 
 $assignee = ($x['assigned_housekeeper_id'] == $sid) ? 'You' : ($x['first_name'] ? $x['first_name'].' '.$x['last_name'] : 'Unassigned');
?>
<tr>
    <td><?=$x['log_id']?></td>
    <td><?=e($x['room_number'])?></td>
    <td><?=e($x['task_description'])?></td>
    <td><?=e($assignee)?></td>
    <td><?=dt($x['scheduled_date'])?></td>
    <td><span class="badge <?=$cls?>"><?=e($x['cleaning_status'])?></span></td>
    <td>
        <form method="POST" style="display:inline-flex;gap:5px;"><?=csrf_field()?>
         <input type="hidden" name="log_id" value="<?=$x['log_id']?>">
         <select name="cleaning_status" style="padding:4px;">
         <?php foreach(['Pending','In Progress','Completed'] as $s): ?>
          <option <?=$x['cleaning_status']===$s?'selected':''?>><?=$s?></option>
         <?php endforeach; ?></select>
         <button name="hk" class="btn btn-primary btn-sm">Save</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php endif; footer_html(); ?>s