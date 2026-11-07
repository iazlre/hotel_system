<?php require __DIR__.'/_layout.php';

// --- DELETE TASK ---
if (isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM housekeeping_logs WHERE log_id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        flash('success', "Task #$id deleted.");
    } else {
        flash('error', "Failed to delete: " . $conn->error);
    }
    redirect('housekeeping.php');
}

// --- UPDATE STATUS ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['set'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('housekeeping.php'); }
    $id=(int)$_POST['log_id']; $st=$_POST['cleaning_status'];
    $stmt=$conn->prepare("UPDATE housekeeping_logs SET cleaning_status=? WHERE log_id=?");
    $stmt->bind_param("si",$st,$id); $stmt->execute();
    
    // Auto-free the room if completed
    if ($st==='Completed'){
        $rid=(int)$conn->query("SELECT room_id FROM housekeeping_logs WHERE log_id=$id")->fetch_assoc()['room_id'];
        $conn->query("UPDATE rooms SET status='Vacant' WHERE room_id=$rid");
    }
    flash('success','Task updated.'); redirect('housekeeping.php');
}

// --- ADD NEW TASK ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('housekeeping.php'); }
    $rid=(int)$_POST['room_id']; $desc=trim($_POST['task_description']);
    $hk=!empty($_POST['assigned_housekeeper_id'])?(int)$_POST['assigned_housekeeper_id']:null;
    $stmt=$conn->prepare("INSERT INTO housekeeping_logs (room_id,assigned_housekeeper_id,task_description,scheduled_date) VALUES (?,?,?,NOW())");
    $stmt->bind_param("iis",$rid,$hk,$desc); $stmt->execute();
    flash('success','Task created.'); redirect('housekeeping.php');
}

header_html('housekeeping','Housekeeping'); ?>

<div class="form-container">
    <h3>Assign New Task</h3>
    <form method="POST">
        <?=csrf_field()?>
        <select name="room_id" required><option value="">— Room —</option>
        <?php $r=$conn->query("SELECT room_id,room_number FROM rooms ORDER BY room_number");
        while($x=$r->fetch_assoc()) echo "<option value='{$x['room_id']}'>{$x['room_number']}</option>"; ?>
        </select>
        <select name="assigned_housekeeper_id"><option value="">— Unassigned —</option>
        <?php $r=$conn->query("SELECT staff_id,first_name,last_name FROM staff WHERE role='Housekeeping'");
        while($x=$r->fetch_assoc()) echo "<option value='{$x['staff_id']}'>{$x['first_name']} {$x['last_name']}</option>"; ?>
        </select>
        <input name="task_description" value="Routine Cleaning & Bed Reset" required>
        <button name="add" class="btn btn-primary">Create</button>
    </form>
</div>

<table>
<!-- BINAGO: Update header to Actions -->
<tr><th>#</th><th>Room</th><th>Task</th><th>Assigned</th><th>Date</th><th>Status</th><th>Actions</th></tr>
<?php $r=$conn->query("SELECT hl.*,rm.room_number,s.first_name f,s.last_name l FROM housekeeping_logs hl
  JOIN rooms rm ON hl.room_id=rm.room_id LEFT JOIN staff s ON hl.assigned_housekeeper_id=s.staff_id
  ORDER BY hl.scheduled_date DESC");
  
if($r->num_rows===0) echo "<tr><td colspan='7' class='empty'>No tasks.</td></tr>";

while($x=$r->fetch_assoc()):
 $cls=['Pending'=>'bg-yellow','In Progress'=>'bg-blue','Completed'=>'bg-green'][$x['cleaning_status']] ?? 'bg-gray'; 
?>
<tr>
    <td><?=$x['log_id']?></td>
    <td><?=e($x['room_number'])?></td>
    <td><?=e($x['task_description'])?></td>
    <td><?=e(trim(($x['f']??'').' '.($x['l']??''))) ?: '—'?></td>
    <td><?=dt($x['scheduled_date'])?></td>
    <td><span class="badge <?=$cls?>"><?=e($x['cleaning_status'])?></span></td>
    <td>
        <!-- BINAGO: Pinagsama ang form at delete button sa isang flex container -->
        <div style="display: flex; gap: 5px; align-items: center;">
            <form method="POST" style="display:flex; gap:5px; margin:0;"><?=csrf_field()?>
             <input type="hidden" name="log_id" value="<?=$x['log_id']?>">
             <select name="cleaning_status" style="padding:6px; border-radius:4px; border:1px solid #cbd5e1;">
             <?php foreach(['Pending','In Progress','Completed'] as $s): ?>
              <option <?=$x['cleaning_status']===$s?'selected':''?>><?=$s?></option>
             <?php endforeach; ?></select>
             <button name="set" class="btn btn-primary btn-sm">Save</button>
            </form>
            
            <!-- IDINAGDAG: Delete button -->
            <a href="housekeeping.php?delete=<?=$x['log_id']?>" class="btn btn-delete btn-sm" onclick="return confirm('Delete this task record?')">Delete</a>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php footer_html();