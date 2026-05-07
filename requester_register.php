<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Hardcode the role so they are automatically classified correctly
    $role = 'Requester'; 

    // 1. Basic Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // 2. Check if the email or phone already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone_number = ?");
        $check_stmt->bind_param("ss", $email, $phone);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "An account with this Email or Phone Number already exists. Please login.";
        } else {
            // 3. Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 4. Insert the new Requester into the database
            // Note: Adjust the column names if your database uses slightly different ones!
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);

            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now login.";
                // Optional: You could automatically log them in here, but redirecting to login is safer.
            } else {
                $error = "Database error: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Requester - Life Drop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #333; padding: 20px; }
        
        .app-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 40px; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); }
        .logo-text { font-size: 26px; font-weight: 700; margin-bottom: 5px; color: #8b5cf6; text-align: center; }
        .sub-text { text-align: center; color: #64748b; font-size: 14px; margin-bottom: 25px; }

        .input-group { position: relative; margin-bottom: 15px; }
        
        .input-field { width: 100%; padding: 12px 45px 12px 15px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; color: #333; font-size: 14px; outline: none; transition: border-color 0.3s, background 0.3s; }
        .input-field:focus { border-color: #8b5cf6; background: #fff; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
        .input-field::placeholder { color: #94a3b8; }
        
        .input-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none; }

        .btn-submit { width: 100%; display: block; padding: 14px; background: #8b5cf6; border: none; border-radius: 6px; color: white; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: #7c3aed; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2); }

        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 6px; border: 1px solid #f87171; text-align: center; font-size: 14px; margin-bottom: 20px; font-weight: 500; }
        .success-msg { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 6px; border: 1px solid #86efac; text-align: center; font-size: 14px; margin-bottom: 20px; font-weight: 500; }
        
        .footer-links { margin-top: 20px; text-align: center; font-size: 14px; color: #64748b; }
        .footer-links a { color: #8b5cf6; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .footer-links a:hover { color: #7c3aed; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="app-card">
        <div class="logo-text">🚑 Register as Requester</div>
        <div class="sub-text">Create an account to track your emergency requests faster.</div>

        <?php if (!empty($error)) echo "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>
        <?php if (!empty($success)) echo "<div class='success-msg'><i class='fas fa-check-circle'></i> $success <br><br><a href='login.php' style='color:#16a34a; text-decoration:underline;'>Click here to Login</a></div>"; ?>

        <form method="POST" action="requester_register.php">
            
            <div class="input-group">
                <input type="text" class="input-field" name="name" placeholder="Full Name" required>
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="input-group">
                <input type="email" class="input-field" name="email" placeholder="Email Address" required>
                <i class="fas fa-envelope input-icon"></i>
            </div>

            <div class="input-group">
                <input type="text" class="input-field" name="phone_number" placeholder="Phone Number" required>
                <i class="fas fa-phone input-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" class="input-field" name="password" placeholder="Create Password" required minlength="6">
                <i class="fas fa-lock input-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" class="input-field" name="confirm_password" placeholder="Confirm Password" required minlength="6">
                <i class="fas fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn-submit">Create Requester Account</button>
        </form>

        <div class="footer-links">
            Already have an account? <a href="login.php">Login Here</a>
        </div>
    </div>

</body>
</html>