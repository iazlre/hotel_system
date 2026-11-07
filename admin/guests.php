<?php require __DIR__.'/_layout.php';

if (isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $used=$conn->query("SELECT (SELECT COUNT(*) FROM booking_list WHERE guest_id=$id)
                       + (SELECT COUNT(*) FROM room_reservations WHERE guest_id=$id)
                       + (SELECT COUNT(*) FROM cafe_reservations WHERE guest_id=$id) c")->fetch_assoc()['c'];
    if($used>0) flash('error',"Cannot delete — $used linked records.");
    else { $conn->query("DELETE FROM guests WHERE guest_id=$id"); flash('success','Guest deleted.'); }
    redirect('guests.php');
}
$edit=null;
if ($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('guests.php'); }
    $fn=$_POST['first_name'];$ln=$_POST['last_name'];$em=$_POST['email'];$ph=$_POST['phone'];$t=$_POST['guest_type'];
    if(!empty($_POST['guest_id'])){
        $id=(int)$_POST['guest_id'];
        $stmt=$conn->prepare("UPDATE guests SET first_name=?,last_name=?,email=?,phone=?,guest_type=? WHERE guest_id=?");
        $stmt->bind_param("sssssi",$fn,$ln,$em,$ph,$t,$id);
    } else {
        $stmt=$conn->prepare("INSERT INTO guests (first_name,last_name,email,phone,guest_type) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss",$fn,$ln,$em,$ph,$t);
    }
    if($stmt->execute()) flash('success','Saved.'); else flash('error','Save failed: '.$conn->error);
    redirect('guests.php');
}
if (isset($_GET['edit'])) $edit=$conn->query("SELECT * FROM guests WHERE guest_id=".(int)$_GET['edit'])->fetch_assoc();

header_html('guests','Guests'); ?>
<div class="form-container"><h3><?=$edit?'Edit':'Add New'?> Guest</h3>
<form method="POST"><?=csrf_field()?>
 <input type="hidden" name="guest_id" value="<?=$edit['guest_id']??''?>">
 <input name="first_name" placeholder="First Name" required value="<?=e($edit['first_name']??'')?>">
 <input name="last_name" placeholder="Last Name" required value="<?=e($edit['last_name']??'')?>">
 <input type="email" name="email" placeholder="Email" required value="<?=e($edit['email']??'')?>">
 <input name="phone" placeholder="Phone" required value="<?=e($edit['phone']??'')?>">
 <select name="guest_type">
  <?php foreach(['Student','Faculty'] as $t): ?>
   <option <?=($edit['guest_type']??'')===$t?'selected':''?>><?=$t?></option>
  <?php endforeach; ?></select>
 <button class="btn btn-primary"><?=$edit?'Update':'Add'?></button>
 <?php if($edit): ?><a href="guests.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
</form></div>
<table><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Type</th><th>Actions</th></tr>
<?php $r=$conn->query("SELECT * FROM guests ORDER BY guest_id DESC");
while($x=$r->fetch_assoc()): ?>
<tr><td><?=$x['guest_id']?></td><td><?=e($x['first_name'].' '.$x['last_name'])?></td>
<td><?=e($x['email'])?></td><td><?=e($x['phone'])?></td>
<td><span class="badge <?=$x['guest_type']==='Student'?'bg-blue':'bg-yellow'?>"><?=e($x['guest_type'])?></span></td>
<td><a href="guests.php?edit=<?=$x['guest_id']?>" class="btn btn-edit btn-sm">Edit</a>
<a href="guests.php?delete=<?=$x['guest_id']?>" class="btn btn-delete btn-sm"
 onclick="return confirm('Delete?')">Delete</a></td></tr>
<?php endwhile; ?></table>
<?php footer_html();