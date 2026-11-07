<?php require __DIR__.'/_layout.php';

// --- DELETE CAFE RESERVATION ---
if (isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM cafe_reservations WHERE reservation_id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        flash('success', "Café Reservation #$id deleted.");
    } else {
        flash('error', "Failed to delete: " . $conn->error);
    }
    redirect('cafe_reservations.php');
}

// --- UPDATE STATUS ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['set'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('cafe_reservations.php'); }
    $id=(int)$_POST['rid']; $st=$_POST['status'];
    $stmt=$conn->prepare("UPDATE cafe_reservations SET status=? WHERE reservation_id=?");
    $stmt->bind_param("si",$st,$id); $stmt->execute();
    
    // Auto-update table status based on reservation status
    $tid=(int)$conn->query("SELECT table_id FROM cafe_reservations WHERE reservation_id=$id")->fetch_assoc()['table_id'];
    if ($st == 'Seated') {
        $new = 'Occupied';
    } elseif ($st == 'Confirmed') {
        $new = 'Reserved';
    } else {
        $new = 'Available';
    }
    $conn->query("UPDATE cafe_tables SET status='$new' WHERE table_id=$tid");
    
    flash('success',"Reservation #$id updated to $st."); 
    redirect('cafe_reservations.php');
}

header_html('cafe','Café Reservations'); ?>

<table>
<!-- BINAGO: Update header to Actions -->
<tr><th>#</th><th>Guest</th><th>Table</th><th>Date</th><th>Time</th><th>Pax</th><th>Status</th><th>Actions</th></tr>
<?php 
$r=$conn->query("SELECT cr.*,g.first_name,g.last_name,ct.table_number FROM cafe_reservations cr
  JOIN guests g ON cr.guest_id=g.guest_id JOIN cafe_tables ct ON cr.table_id=ct.table_id
  ORDER BY cr.reservation_date DESC, cr.reservation_time DESC");
  
if($r->num_rows===0) echo "<tr><td colspan='8' class='empty'>No café reservations.</td></tr>";

while($x=$r->fetch_assoc()):
 $cls=['Confirmed'=>'bg-blue','Seated'=>'bg-yellow','Completed'=>'bg-green','Cancelled'=>'bg-red'][$x['status']] ?? 'bg-gray'; 
?>
<tr>
    <td>#<?=$x['reservation_id']?></td>
    <td><?=e($x['first_name'].' '.$x['last_name'])?></td>
    <td><?=e($x['table_number'])?></td>
    <td><?=d($x['reservation_date'])?></td>
    <td><?=date('g:i A', strtotime($x['reservation_time']))?></td>
    <td><?=$x['number_of_guests']?></td>
    <td><span class="badge <?=$cls?>"><?=e($x['status'])?></span></td>
    <td>
        <!-- BINAGO: Pinagsama ang form at delete button sa isang flex container -->
        <div style="display: flex; gap: 5px; align-items: center;">
            <form method="POST" style="display:flex; gap:5px; margin:0;">
                <?=csrf_field()?>
                <input type="hidden" name="rid" value="<?=$x['reservation_id']?>">
                <select name="status" style="padding:6px; border-radius:4px; border:1px solid #cbd5e1;">
                    <?php foreach(['Confirmed','Seated','Completed','Cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?=$x['status']===$s?'selected':''?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button name="set" class="btn btn-primary btn-sm">Save</button>
            </form>
            
            <!-- IDINAGDAG: Delete button -->
            <a href="cafe_reservations.php?delete=<?=$x['reservation_id']?>" class="btn btn-delete btn-sm" onclick="return confirm('Delete this reservation record?')">Delete</a>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php footer_html();