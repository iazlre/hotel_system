<?php
require 'functions.php';
require 'db.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = "Security token expired. Please try again.";
    } else {
        $fn = trim($_POST['first_name']);
        $ln = trim($_POST['last_name']);
        $em = trim($_POST['email']);
        $ph = trim($_POST['phone']);
        $type = $_POST['guest_type'];
        $pw = $_POST['password'];
        $confirm_pw = $_POST['confirm_password'];

        // Validation
        if ($pw !== $confirm_pw) {
            $error = "Passwords do not match.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $em);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email is already registered. Please log in.";
            } else {
                // Transaction to insert into guests and users
                $conn->begin_transaction();
                try {
                    // 1. Insert into guests
                    $stmt1 = $conn->prepare("INSERT INTO guests (first_name, last_name, email, phone, guest_type) VALUES (?, ?, ?, ?, ?)");
                    $stmt1->bind_param("sssss", $fn, $ln, $em, $ph, $type);
                    $stmt1->execute();
                    
                    $guest_id = $conn->insert_id;

                    // 2. Insert into users
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $stmt2 = $conn->prepare("INSERT INTO users (email, password, role, guest_id) VALUES (?, ?, 'Guest', ?)");
                    $stmt2->bind_param("ssi", $em, $hash, $guest_id);
                    $stmt2->execute();

                    $conn->commit();
                    flash('success', 'Account created successfully! You can now log in.');
                    redirect('login.php');
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Hotel & Restaurant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box" style="max-width: 500px;">
        <h2>Create an Account</h2>
        <p style="text-align:center;color:#64748b;margin-bottom:20px;">Register as a Student or Faculty</p>
        
        <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
        
        <form method="POST">
            <?= csrf_field() ?>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="first_name" placeholder="First Name" required style="margin-bottom:0;">
                <input type="text" name="last_name" placeholder="Last Name" required style="margin-bottom:0;">
            </div>
            
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            
            <select name="guest_type" required style="width: 100%; padding: 14px; margin-bottom: 20px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none;">
                <option value="Student">Student</option>
                <option value="Faculty">Faculty</option>
            </select>
            
            <input type="password" name="password" placeholder="Password" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
            
            <button type="submit">Register</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 14px;">
            Already have an account? <a href="login.php" style="color: #38bdf8; text-decoration: none; font-weight: bold;">Log In</a>
        </div>
    </div>
</body>
</html>