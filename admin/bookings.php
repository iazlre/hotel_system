<?php require __DIR__.'/_layout.php';

if (isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $conn->query("DELETE FROM booking_list WHERE booking_id=$id");
    flash('success',"Booking #$id deleted."); redirect('bookings.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('bookings.php'); }
    $id=(int)$_POST['booking_id']; $pay=$_POST['payment_status']; $st=$_POST['booking_status'];
    $stmt=$conn->prepare("UPDATE booking_list SET payment_status=?,booking_status=? WHERE booking_id=?");
    $stmt->bind_param("ssi",$pay,$st,$id); $stmt->execute();

    if (in_array($st,['Checked-Out','Cancelled'])){
        $rid=(int)$conn->query("SELECT room_id FROM booking_list WHERE booking_id=$id")->fetch_assoc()['room_id'];
        $conn->query("UPDATE rooms SET status='Cleaning' WHERE room_id=$rid");
        if ($st==='Checked-Out'){
            $conn->query("UPDATE booking_list SET check_out=NOW() WHERE booking_id=$id AND check_out IS NULL");
            $conn->query("INSERT INTO housekeeping_logs (room_id,task_description,scheduled_date)
                VALUES ($rid,'Routine Cleaning & Bed Reset',NOW())");
        }
    }
    flash('success',"Booking #$id updated."); redirect('bookings.php');
}

header_html('bookings','Booking List'); ?>
<table>
<tr><th>#</th><th>Guest</th><th>Room</th><th>In</th><th>Out</th><th>Total</th><th>Update</th><th></th></tr>
<?php $r=$conn->query("SELECT b.*,g.first_name,g.last_name,rm.room_number FROM booking_list b
  JOIN guests g ON b.guest_id=g.guest_id JOIN rooms rm ON b.room_id=rm.room_id
  ORDER BY b.booking_id DESC");
if($r->num_rows===0) echo "<tr><td colspan='8' class='empty'>No bookings.</td></tr>";
while($x=$r->fetch_assoc()): ?>
<tr><td>#<?=$x['booking_id']?></td><td><?=e($x['first_name'].' '.$x['last_name'])?></td>
<td><?=e($x['room_number'])?></td><td><?=d($x['check_in'])?></td><td><?=d($x['check_out'])?></td>
<td><?=money($x['total_amount'])?></td>
<td><form method="POST" style="display:flex;gap:5px;"><?=csrf_field()?>
 <input type="hidden" name="booking_id" value="<?=$x['booking_id']?>">
 <select name="payment_status" style="padding:5px;">
 <?php foreach(['Paid','Unpaid'] as $o): ?> <!-- Removed Partially Paid here -->
  <option <?=$x['payment_status']===$o?'selected':''?>><?=$o?></option>
 <?php endforeach; ?></select>
 <select name="booking_status" style="padding:5px;">
 <?php foreach(['Checked-In','Checked-Out','Cancelled'] as $o): ?>
  <option <?=$x['booking_status']===$o?'selected':''?>><?=$o?></option>
 <?php endforeach; ?></select>
 <button name="update" class="btn btn-primary btn-sm">Save</button></form></td>
<td><a href="bookings.php?delete=<?=$x['booking_id']?>" class="btn btn-delete btn-sm"
 onclick="return confirm('Delete?')">Delete</a></td></tr>
<?php endwhile; ?></table>
<?php footer_html();