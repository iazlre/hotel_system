<?php require __DIR__.'/_layout.php';

if (isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $used=$conn->query("SELECT (SELECT COUNT(*) FROM booking_list WHERE handled_by_staff_id=$id)
                       + (SELECT COUNT(*) FROM room_reservations WHERE handled_by_staff_id=$id)
                       + (SELECT COUNT(*) FROM cafe_reservations WHERE assigned_staff_id=$id)
                       + (SELECT COUNT(*) FROM housekeeping_logs WHERE assigned_housekeeper_id=$id) c")->fetch_assoc()['c'];
    if($used>0) flash('error',"Cannot delete — $used linked records.");
    else { $conn->query("DELETE FROM staff WHERE staff_id=$id"); flash('success','Staff deleted.'); }
    redirect('staff.php');
}
$edit=null;
if ($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('staff.php'); }
    $fn=$_POST['first_name'];$ln=$_POST['last_name'];$em=$_POST['email'];
    $role=$_POST['role'];$st=$_POST['status'];
    
    if(!empty($_POST['staff_id'])){
        $id=(int)$_POST['staff_id'];
        // shift_schedule removed from UPDATE query
        $stmt=$conn->prepare("UPDATE staff SET first_name=?,last_name=?,email=?,role=?,status=? WHERE staff_id=?");
        $stmt->bind_param("sssssi",$fn,$ln,$em,$role,$st,$id);
    } else {
        // shift_schedule removed from INSERT query
        $stmt=$conn->prepare("INSERT INTO staff (first_name,last_name,email,role,status) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss",$fn,$ln,$em,$role,$st);
    }
    if($stmt->execute()) flash('success','Saved.'); else flash('error','Save failed: '.$conn->error);
    redirect('staff.php');
}
if (isset($_GET['edit'])) $edit=$conn->query("SELECT * FROM staff WHERE staff_id=".(int)$_GET['edit'])->fetch_assoc();

header_html('staff','Staff'); ?>
<div class="form-container"><h3><?=$edit?'Edit':'Add New'?> Staff</h3>
<form method="POST"><?=csrf_field()?>
 <input type="hidden" name="staff_id" value="<?=$edit['staff_id']??''?>">
 <input name="first_name" placeholder="First Name" required value="<?=e($edit['first_name']??'')?>">
 <input name="last_name" placeholder="Last Name" required value="<?=e($edit['last_name']??'')?>">
 <input type="email" name="email" placeholder="Email" required value="<?=e($edit['email']??'')?>">
 <select name="role">
  <?php foreach(['Receptionist','Housekeeping','Cafe Staff','Manager'] as $r): ?>
   <option <?=($edit['role']??'')===$r?'selected':''?>><?=$r?></option>
  <?php endforeach; ?></select>
 <!-- Shift Schedule Input Removed Here -->
 <select name="status">
  <?php foreach(['Active','On Leave','Inactive'] as $s): ?>
   <option <?=($edit['status']??'Active')===$s?'selected':''?>><?=$s?></option>
  <?php endforeach; ?></select>
 <button class="btn btn-primary"><?=$edit?'Update':'Add'?></button>
 <?php if($edit): ?><a href="staff.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
</form></div>

<!-- Shift Schedule column removed from table headers -->
<table><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
<?php $r=$conn->query("SELECT * FROM staff ORDER BY staff_id");
while($x=$r->fetch_assoc()): ?>
<tr><td><?=$x['staff_id']?></td><td><?=e($x['first_name'].' '.$x['last_name'])?></td>
<td><?=e($x['email'])?></td><td><span class="badge bg-blue"><?=e($x['role'])?></span></td>
<td><span class="badge <?=$x['status']==='Active'?'bg-green':($x['status']==='On Leave'?'bg-yellow':'bg-gray')?>"><?=e($x['status'])?></span></td>
<td><a href="staff.php?edit=<?=$x['staff_id']?>" class="btn btn-edit btn-sm">Edit</a>
<a href="staff.php?delete=<?=$x['staff_id']?>" class="btn btn-delete btn-sm"
 onclick="return confirm('Delete?')">Delete</a></td></tr>
<?php endwhile; ?></table>
<?php footer_html();