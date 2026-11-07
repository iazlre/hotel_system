<?php require __DIR__.'/_layout.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('users.php'); }
    $em=trim($_POST['email']); $pw=$_POST['password']; $role=$_POST['role'];
    $gid=!empty($_POST['guest_id'])?(int)$_POST['guest_id']:null;
    $sid=!empty($_POST['staff_id'])?(int)$_POST['staff_id']:null;
    $hash=password_hash($pw,PASSWORD_DEFAULT);
    $stmt=$conn->prepare("INSERT INTO users (email,password,role,guest_id,staff_id) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssii",$em,$hash,$role,$gid,$sid);
    if($stmt->execute()) flash('success',"Account created for $em."); else flash('error','Failed: '.$conn->error);
    redirect('users.php');
}
if (isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    if($id===(int)$_SESSION['user_id']) flash('error',"Can't delete your own account.");
    else { $conn->query("DELETE FROM users WHERE user_id=$id"); flash('success','User deleted.'); }
    redirect('users.php');
}

header_html('users','User Accounts'); ?>
<div class="form-container"><h3>Create Login Account</h3>
<form method="POST"><?=csrf_field()?>
 <input type="email" name="email" placeholder="Email" required>
 <input type="password" name="password" placeholder="Password" required minlength="6">
 <select name="role">
  <option value="Staff">Staff</option>
  <option value="Guest">Guest (Student/Faculty)</option> <!-- CHANGED HERE -->
  <option value="Admin">Admin</option>
 </select>
 <select name="staff_id"><option value="">— Link Staff (optional) —</option>
  <?php $s=$conn->query("SELECT staff_id,first_name,last_name,role FROM staff");
  while($x=$s->fetch_assoc()) echo "<option value='{$x['staff_id']}'>{$x['first_name']} {$x['last_name']} ({$x['role']})</option>"; ?>
 </select>
 <select name="guest_id"><option value="">— Link Guest (optional) —</option>
  <?php $s=$conn->query("SELECT guest_id,first_name,last_name,guest_type FROM guests");
  while($x=$s->fetch_assoc()) echo "<option value='{$x['guest_id']}'>{$x['first_name']} {$x['last_name']} ({$x['guest_type']})</option>"; ?>
 </select>
 <button name="create" class="btn btn-primary">Create</button>
</form></div>
<table><tr><th>#</th><th>Email</th><th>Role</th><th>Linked</th><th></th></tr>
<?php $r=$conn->query("SELECT u.*, COALESCE(g.first_name,s.first_name,'—') fn, COALESCE(g.last_name,s.last_name,'') ln
  FROM users u LEFT JOIN guests g ON u.guest_id=g.guest_id LEFT JOIN staff s ON u.staff_id=s.staff_id ORDER BY u.user_id");
while($x=$r->fetch_assoc()): ?>
<tr><td><?=$x['user_id']?></td><td><?=e($x['email'])?></td>
<td><span class="badge <?=$x['role']==='Admin'?'bg-red':($x['role']==='Staff'?'bg-blue':'bg-green')?>"><?=e($x['role'])?></span></td>
<td><?=e(trim($x['fn'].' '.$x['ln']))?></td>
<td><a href="users.php?delete=<?=$x['user_id']?>" class="btn btn-delete btn-sm"
 onclick="return confirm('Delete account?')">Delete</a></td></tr>
<?php endwhile; ?></table>
<?php footer_html();