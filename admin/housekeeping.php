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
    
    if ($st==='Completed'){
        $rid=(int)$conn->query("SELECT room_id FROM housekeeping_logs WHERE log_id=$id")->fetch_assoc()['room_id'];
        $conn->query("UPDATE rooms SET status='Vacant' WHERE room_id=$rid");
    }
    flash('success','Task updated.'); redirect('housekeeping.php');
}

// --- ADD / EDIT TASK ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_task'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('housekeeping.php'); }
    $rid=(int)$_POST['room_id']; 
    $desc=trim($_POST['task_description']);
    $hk = !empty($_POST['assigned_housekeeper_id']) ? (int)$_POST['assigned_housekeeper_id'] : null;

    if (!empty($_POST['log_id'])) {
        // EDIT EXISTING TASK
        $id = (int)$_POST['log_id'];
        $stmt=$conn->prepare("UPDATE housekeeping_logs SET room_id=?, assigned_housekeeper_id=?, task_description=? WHERE log_id=?");
        $stmt->bind_param("iisi",$rid,$hk,$desc,$id);
        if($stmt->execute()) flash('success','Task updated successfully.');
        else flash('error','Update failed: '.$conn->error);
    } else {
        // ADD NEW TASK (With Auto-Assign)
        if ($hk === null) {
            $hk_res = $conn->query("SELECT staff_id FROM staff WHERE role='Housekeeping' AND status='Active' LIMIT 1")->fetch_assoc();
            if ($hk_res) $hk = (int)$hk_res['staff_id'];
        }
        $stmt=$conn->prepare("INSERT INTO housekeeping_logs (room_id,assigned_housekeeper_id,task_description,scheduled_date) VALUES (?,?,?,NOW())");
        $stmt->bind_param("iis",$rid,$hk,$desc);
        if($stmt->execute()) flash('success','Task created and auto-assigned.');
        else flash('error','Create failed: '.$conn->error);
    }
    redirect('housekeeping.php');
}

// --- GET EDIT DATA ---
$edit_task = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_task = $conn->query("SELECT * FROM housekeeping_logs WHERE log_id=$edit_id")->fetch_assoc();
}

header_html('housekeeping','Housekeeping'); ?>

<div class="form-container">
    <h3><?= $edit_task ? 'Edit Task' : 'Assign New Task' ?></h3>
    <form method="POST">
        <?=csrf_field()?>
        <input type="hidden" name="log_id" value="<?= $edit_task['log_id'] ?? '' ?>">
        
        <select name="room_id" required>
            <option value="">— Room —</option>
            <?php $r=$conn->query("SELECT room_id,room_number FROM rooms ORDER BY room_number");
            while($x=$r->fetch_assoc()): ?>
                <option value="<?=$x['room_id']?>" <?= ($edit_task && $edit_task['room_id'] == $x['room_id']) ? 'selected' : '' ?>>
                    <?=$x['room_number']?>
                </option>
            <?php endwhile; ?>
        </select>
        
        <select name="assigned_housekeeper_id">
            <option value="">— Auto-Assign (Default) —</option>
            <?php $r=$conn->query("SELECT staff_id,first_name,last_name FROM staff WHERE role='Housekeeping' AND status='Active'");
            while($x=$r->fetch_assoc()): ?>
                <option value="<?=$x['staff_id']?>" <?= ($edit_task && $edit_task['assigned_housekeeper_id'] == $x['staff_id']) ? 'selected' : '' ?>>
                    <?=$x['first_name'].' '.$x['last_name']?>
                </option>
            <?php endwhile; ?>
        </select>
        
        <input name="task_description" placeholder="Task description" required 
               value="<?= e($edit_task['task_description'] ?? 'Routine Cleaning & Bed Reset') ?>">
               
        <button name="save_task" class="btn btn-primary"><?= $edit_task ? 'Update Task' : 'Create' ?></button>
        <?php if($edit_task): ?>
            <a href="housekeeping.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<table>
<tr><th>ID NUMBER</th><th>Room</th><th>Task</th><th>Assigned To</th><th>Date</th><th>Status</th><th>Actions</th></tr>
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
    <td><?= e(trim(($x['f']??'').' '.($x['l']??''))) ?: '<span style="color:#ef4444;">Unassigned</span>' ?></td>
    <td><?=dt($x['scheduled_date'])?></td>
    <td><span class="badge <?=$cls?>"><?=e($x['cleaning_status'])?></span></td>
    <td>
        <div style="display: flex; gap: 5px; align-items: center;">
            <form method="POST" style="display:flex; gap:5px; margin:0;"><?=csrf_field()?>
             <input type="hidden" name="log_id" value="<?=$x['log_id']?>">
             <select name="cleaning_status" style="padding:6px; border-radius:4px; border:1px solid #cbd5e1;">
             <?php foreach(['Pending','In Progress','Completed'] as $s): ?>
              <option <?=$x['cleaning_status']===$s?'selected':''?>><?=$s?></option>
             <?php endforeach; ?></select>
             <button name="set" class="btn btn-primary btn-sm">Save</button>
            </form>
            
            <!-- BAGONG EDIT BUTTON -->
            <a href="housekeeping.php?edit=<?=$x['log_id']?>" class="btn btn-edit btn-sm">Edit</a>
            <a href="housekeeping.php?delete=<?=$x['log_id']?>" class="btn btn-delete btn-sm" onclick="return confirm('Delete this task record?')">Delete</a>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php footer_html();