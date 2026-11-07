<?php
require 'functions.php'; require 'db.php';
if (isset($_SESSION['user_id'])){
    $r=$_SESSION['role'];
    redirect($r==='Admin'?'admin/dashboard.php':($r==='Staff'?'staff/dashboard.php':'guest/dashboard.php'));
}
$err=null;
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt=$conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s",$_POST['email']); $stmt->execute();
    $u=$stmt->get_result()->fetch_assoc();
    if ($u && password_verify($_POST['password'],$u['password'])){
        session_regenerate_id(true);
        $_SESSION['user_id']=$u['user_id']; $_SESSION['role']=$u['role'];
        $_SESSION['guest_id']=$u['guest_id']; $_SESSION['staff_id']=$u['staff_id'];
        if ($u['role']==='Staff' && $u['staff_id']){
            $s=$conn->query("SELECT * FROM staff WHERE staff_id=".(int)$u['staff_id'])->fetch_assoc();
            $_SESSION['staff_role']=$s['role']; $_SESSION['name']=$s['first_name'].' '.$s['last_name'];
        } elseif ($u['role']==='Guest' && $u['guest_id']){ // CHANGED HERE
            $g=$conn->query("SELECT * FROM guests WHERE guest_id=".(int)$u['guest_id'])->fetch_assoc();
            $_SESSION['name']=$g['first_name'].' '.$g['last_name'];
        } else $_SESSION['name']='Administrator';
        $r=$u['role'];
        redirect($r==='Admin'?'admin/dashboard.php':($r==='Staff'?'staff/dashboard.php':'guest/dashboard.php'));
    }
    $err='Invalid email or password.';
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login</title>
<link rel="stylesheet" href="style.css"></head>
<body class="login-body">
<div class="login-box">
    <h2> Hotel &amp; Restaurant</h2>
    <p style="text-align:center;color:#64748b;margin-bottom:20px;">Sign in to your portal</p>
    <?php if($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button>Sign In</button>
    </form>
</div></body></html>