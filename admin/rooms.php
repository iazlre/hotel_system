<?php require __DIR__.'/_layout.php';

if (isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $used=$conn->query("SELECT (SELECT COUNT(*) FROM booking_list WHERE room_id=$id AND booking_status<>'Cancelled')
                       + (SELECT COUNT(*) FROM room_reservations WHERE room_id=$id AND reservation_status<>'Cancelled') c")->fetch_assoc()['c'];
    if($used>0) flash('error',"Cannot delete — $used linked records.");
    else { $conn->query("DELETE FROM rooms WHERE room_id=$id"); flash('success','Room deleted.'); }
    redirect('rooms.php');
}

$edit=null;
if ($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('rooms.php'); }
    $n=$_POST['room_number']; $t=$_POST['room_type']; $c=(int)$_POST['capacity'];
    $p=(float)$_POST['price'];
    
    if (!empty($_POST['room_id'])){
        $id=(int)$_POST['room_id'];
        // HINDI NA NAKA-INSERT ANG STATUS SA UPDATE. Mananatili kung ano ang current status niya.
        $stmt=$conn->prepare("UPDATE rooms SET room_number=?,room_type=?,capacity=?,price_per_night=? WHERE room_id=?");
        $stmt->bind_param("ssidi",$n,$t,$c,$p,$id);
    } else {
        // KAPAG BAGONG ADD, AUTOMATIC VACANT
        $stmt=$conn->prepare("INSERT INTO rooms (room_number,room_type,capacity,price_per_night,status) VALUES (?,?,?,?,'Vacant')");
        $stmt->bind_param("ssid",$n,$t,$c,$p);
    }
    if($stmt->execute()) flash('success','Saved.'); else flash('error','Save failed: '.$conn->error);
    redirect('rooms.php');
}
if (isset($_GET['edit'])) $edit=$conn->query("SELECT * FROM rooms WHERE room_id=".(int)$_GET['edit'])->fetch_assoc();

header_html('rooms','Room Management'); ?>
<div class="form-container"><h3><?=$edit?'Edit':'Add New'?> Room</h3>
<form method="POST"><?=csrf_field()?>
 <input type="hidden" name="room_id" value="<?=$edit['room_id']??''?>">
 <input name="room_number" placeholder="Room #" required value="<?=e($edit['room_number']??'')?>">
 <select name="room_type">
  <?php foreach(['Standard Room','Deluxe Suite'] as $t): ?>
   <option <?=($edit['room_type']??'')===$t?'selected':''?>><?=$t?></option>
  <?php endforeach; ?></select>
 <select name="capacity">
  <?php foreach([2,4] as $c): ?>
   <option value="<?=$c?>" <?=($edit['capacity']??0)==$c?'selected':''?>><?=$c?> persons</option>
  <?php endforeach; ?></select>
 <input type="number" step="0.01" name="price" placeholder="Price" required value="<?=$edit['price_per_night']??''?>">
 
 <!-- STATUS DROPDOWN REMOVED -->
 
 <button class="btn btn-primary"><?=$edit?'Update':'Add'?></button>
 <?php if($edit): ?><a href="rooms.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
</form></div>

<table>
<!-- STATUS COLUMN REMOVED SA TABLE -->
<tr><th>#</th><th>Number</th><th>Type</th><th>Cap</th><th>Price</th><th>Actions</th></tr>
<?php $r=$conn->query("SELECT * FROM rooms ORDER BY room_number");
while($x=$r->fetch_assoc()): ?>
<tr>
    <td><?=$x['room_id']?></td>
    <td><?=e($x['room_number'])?></td>
    <td><?=e($x['room_type'])?></td>
    <td><?=$x['capacity']?></td>
    <td><?=money($x['price_per_night'])?></td>
    <td>
        <a href="rooms.php?edit=<?=$x['room_id']?>" class="btn btn-edit btn-sm">Edit</a>
        <a href="rooms.php?delete=<?=$x['room_id']?>" class="btn btn-delete btn-sm"
         onclick="return confirm('Delete?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?></table>
<?php footer_html();