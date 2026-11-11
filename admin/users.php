<?php require __DIR__.'/_layout.php';

// --- CREATE USER ACCOUNT (ALL-IN-ONE) ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('users.php'); }
    $em = trim($_POST['email']); 
    $pw = $_POST['password']; 
    $role = $_POST['role'];
    $fn = trim($_POST['first_name']);
    $ln = trim($_POST['last_name']);
    $sub_role = $_POST['sub_role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    $chk_user = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $chk_user->bind_param("s", $em); $chk_user->execute();
    if ($chk_user->get_result()->num_rows > 0) {
        flash('error', "An account with this email already exists.");
        redirect('users.php');
    }

    $gid = null; $sid = null;

    if ($role === 'Staff') {
        $chk = $conn->prepare("SELECT staff_id FROM staff WHERE email = ?");
        $chk->bind_param("s", $em); $chk->execute();
        $res = $chk->get_result();
        
        if ($res->num_rows > 0) {
            $sid = $res->fetch_assoc()['staff_id'];
            $upd = $conn->prepare("UPDATE staff SET phone=? WHERE staff_id=?");
            $upd->bind_param("si", $phone, $sid); $upd->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO staff (first_name, last_name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->bind_param("sssss", $fn, $ln, $em, $phone, $sub_role);
            $stmt->execute();
            $sid = $conn->insert_id;
        }
    } elseif ($role === 'Guest') {
        $chk = $conn->prepare("SELECT guest_id FROM guests WHERE email = ?");
        $chk->bind_param("s", $em); $chk->execute();
        $res = $chk->get_result();
        
        if ($res->num_rows > 0) {
            $gid = $res->fetch_assoc()['guest_id'];
            $upd = $conn->prepare("UPDATE guests SET phone=? WHERE guest_id=?");
            $upd->bind_param("si", $phone, $gid); $upd->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO guests (first_name, last_name, email, phone, guest_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fn, $ln, $em, $phone, $sub_role);
            $stmt->execute();
            $gid = $conn->insert_id;
        }
    }

    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, password, role, guest_id, staff_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $em, $hash, $role, $gid, $sid);
    
    if($stmt->execute()) flash('success', "Account created for $em.");
    else flash('error', 'Create failed: '.$conn->error);
    redirect('users.php');
}

// --- DELETE USER ACCOUNT ---
if (isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    if($id === (int)$_SESSION['user_id']) {
        flash('error', "You can't delete your own account.");
    } else {
        $conn->query("DELETE FROM users WHERE user_id=$id");
        flash('success','User deleted.');
    }
    redirect('users.php');
}

header_html('users','User Accounts'); ?>

<style>
    /* OVERRIDE PARA MAGING HORIZONTAL AT NAKA-CENTER */
    .form-container form {
        display: flex !important;
        flex-wrap: wrap !important; /* Papasok sa linya, bababa kung puno */
        justify-content: center !important; /* Nakagitna lahat */
        align-items: flex-end !important; /* Pantay ang ilalim ng inputs at button */
        gap: 10px !important;
        width: 100%;
    }
    
    .input-group {
        display: flex;
        flex-direction: column;
        text-align: left;
        flex: 1 1 130px; /* Hahaba at liliit depende sa space */
        min-width: 130px; /* Para hindi sobrang sikip */
    }
    
    .input-group label {
        font-size: 11px;
        font-weight: bold;
        color: #475569;
        margin-bottom: 5px;
        white-space: nowrap;
    }
    
    .input-group input, .input-group select {
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13px;
        outline: none;
        width: 100%;
        box-sizing: border-box;
    }
    
    .input-group input:focus, .input-group select:focus {
        border-color: #38bdf8;
    }
    
    .btn-container {
        flex: 0 0 auto; /* Hindi mag-s-stretch ang button */
        padding-bottom: 1px; /* Pantay sa inputs */
    }
</style>

<div class="form-container">
    <h3 style="text-align: center; margin-bottom: 20px;">Create Login Account</h3>
    
    <form method="POST">
        <?=csrf_field()?>
        
        <!-- HORIZONTAL ROW OF INPUTS -->
        <div class="input-group">
            <label>First Name</label>
            <input type="text" name="first_name" placeholder="Juan" required>
        </div>
        
        <div class="input-group">
            <label>Last Name</label>
            <input type="text" name="last_name" placeholder="Dela Cruz" required>
        </div>

        <div class="input-group">
            <label>Contact</label>
            <input type="text" name="phone" placeholder="0917..." required>
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="name@email.com" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required minlength="6">
        </div>

        <div class="input-group">
            <label>Role</label>
            <select name="role" id="roleSelect" required onchange="toggleSubRole()">
                <option value="Staff">Staff</option>
                <option value="Guest">Guest</option>
                <option value="Admin">Admin</option>
            </select>
        </div>

        <div class="input-group" id="subRoleDiv">
            <label>Specific Role</label>
            <select name="sub_role" id="subRoleSelect">
                <option value="Receptionist">Receptionist</option>
                <option value="Housekeeping">Housekeeping</option>
                <option value="Cafe Staff">Cafe Staff</option>
                <option value="Manager">Manager</option>
            </select>
        </div>

        <div class="btn-container">
            <button name="create" class="btn btn-primary" style="padding: 10px 20px; height: 38px;">Create</button>
        </div>
    </form>
</div>

<table>
<tr><th>ID NUMBER</th><th>Name</th><th>Email</th><th>Contact Info</th><th>Role</th><th>Actions</th></tr>
<?php 
$r=$conn->query("
    SELECT u.*, 
           COALESCE(g.first_name, s.first_name, '—') AS fn,
           COALESCE(g.last_name,  s.last_name,  '')  AS ln,
           COALESCE(g.phone, s.phone, '—') AS contact_phone
    FROM users u
    LEFT JOIN guests g ON u.guest_id=g.guest_id
    LEFT JOIN staff  s ON u.staff_id=s.staff_id
    ORDER BY u.user_id
");
while($x=$r->fetch_assoc()): 
    $roleClass = $x['role']==='Admin' ? 'bg-red' : ($x['role']==='Staff' ? 'bg-blue' : 'bg-green');
    $fullname = trim($x['fn'].' '.$x['ln']);
    if($fullname === '—' || empty($fullname)) $fullname = 'Administrator';
?>
<tr>
    <td><?=$x['user_id']?></td>
    <td><?=e($fullname)?></td>
    <td><?=e($x['email'])?></td>
    <td><?= e($x['contact_phone']) ?></td>
    <td><span class="badge <?=$roleClass?>"><?=e($x['role'])?></span></td>
    <td>
        <a href="users.php?delete=<?=$x['user_id']?>" class="btn btn-delete btn-sm"
           onclick="return confirm('Delete this account?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<script>
function toggleSubRole() {
    let role = document.getElementById('roleSelect').value;
    let subSelect = document.getElementById('subRoleSelect');
    let subDiv = document.getElementById('subRoleDiv');
    
    if (role === 'Guest') {
        subSelect.innerHTML = '<option value="Student">Student</option><option value="Faculty">Faculty</option>';
        subDiv.style.display = 'flex';
    } else if (role === 'Staff') {
        subSelect.innerHTML = '<option value="Receptionist">Receptionist</option><option value="Housekeeping">Housekeeping</option><option value="Cafe Staff">Cafe Staff</option><option value="Manager">Manager</option>';
        subDiv.style.display = 'flex';
    } else {
        subDiv.style.display = 'none';
    }
}
window.onload = toggleSubRole;
</script>

<?php footer_html();