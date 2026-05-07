<?php
session_start();
require_once 'language.php';
require_once 'db_connect.php';

$error = '';
$success_msg = '';

// STEP 1: If they just submitted the Emergency Form on login.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone']) && isset($_POST['nic'])) {
    $_SESSION['temp_phone'] = $_POST['phone'];
    $_SESSION['temp_nic'] = $_POST['nic'];
    $_SESSION['temp_name'] = $_POST['req_name'];
    $_SESSION['temp_email'] = $_POST['req_email'] ?? '';

    $_SESSION['generated_otp'] = rand(1000, 9999);
    
    $success_msg = "<strong>[SMS SIMULATION]</strong> A PIN was sent to " . htmlspecialchars($_POST['phone']) . ". <br>Your OTP is: <span style='font-size: 20px; font-weight: bold; color: #0ea5e9;'>" . $_SESSION['generated_otp'] . "</span>";
}

// STEP 2: If they are submitting the OTP they just typed in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entered_otp'])) {
    if ($_POST['entered_otp'] == $_SESSION['generated_otp']) {
        // OTP is CORRECT! 
        $nic = $_SESSION['temp_nic'];
        $name = $_SESSION['temp_name'];
        $email = !empty($_SESSION['temp_email']) ? $_SESSION['temp_email'] : 'emergency_' . time() . '@lifedrop.local'; 
        
        $stmt = $conn->prepare("SELECT user_id, name, role FROM users WHERE nic_number = ? LIMIT 1");
        $stmt->bind_param("s", $nic);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = 'requester'; 
        } else {
            $dummy_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); 
            $role = 'requester';
            $phone = $_SESSION['temp_phone'];
            
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role, nic_number, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $name, $email, $dummy_password, $role, $nic, $phone);
            $insert_stmt->execute();
            
            $_SESSION['user_id'] = $insert_stmt->insert_id;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;
            $insert_stmt->close();
        }
        $stmt->close();
        
        unset($_SESSION['temp_phone'], $_SESSION['temp_nic'], $_SESSION['temp_name'], $_SESSION['temp_email'], $_SESSION['generated_otp']);
        
        // --- THIS IS THE MAGIC FIX ---
        // It sends them straight to the blood request form!
        header("Location: request_blood.php");
        exit();
    } else {
        $error = "Incorrect PIN. Please try again.";
        $success_msg = "<strong>[SMS SIMULATION]</strong> Your OTP is: <span style='font-size: 20px; font-weight: bold; color: #0ea5e9;'>" . $_SESSION['generated_otp'] . "</span>";
    }
}

if (!isset($_SESSION['generated_otp'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify PIN - <?php echo isset($lang['app_name']) ? $lang['app_name'] : 'Life Drop'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #333; }
        
        .app-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 40px; border-radius: 8px; width: 100%; max-width: 450px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); text-align: center; }
        
        .icon-circle { width: 60px; height: 60px; background: #e0f2fe; color: #0ea5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px auto; }
        
        h2 { font-size: 20px; color: #2c3e50; margin-bottom: 10px; font-weight: 600; }
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; }

        .input-group { position: relative; margin-bottom: 20px; }
        .input-field { width: 100%; padding: 15px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; color: #333; font-size: 18px; text-align: center; letter-spacing: 4px; outline: none; transition: 0.3s; font-weight: 500; }
        .input-field:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }

        .btn-submit { width: 100%; padding: 14px; background: #0ea5e9; border: none; border-radius: 4px; color: white; font-size: 15px; font-weight: 500; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #0284c7; }
        
        .alert-success { background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 4px; border: 1px solid #a7f3d0; text-align: center; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        .error-msg { color: #ef4444; font-size: 13px; margin-bottom: 15px; font-weight: 500; }
        
        .btn-cancel { display: inline-block; margin-top: 20px; color: #94a3b8; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .btn-cancel:hover { color: #64748b; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="app-card">
        <div class="icon-circle">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h2>Enter Verification PIN</h2>
        <p class="subtitle">Please enter the 4-digit code sent to<br><strong><?php echo htmlspecialchars($_SESSION['temp_phone']); ?></strong></p>

        <?php if (!empty($success_msg)) echo "<div class='alert-success'>$success_msg</div>"; ?>
        <?php if (!empty($error)) echo "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="POST" action="verify_otp.php">
            <div class="input-group">
                <input type="text" name="entered_otp" class="input-field" placeholder="••••" maxlength="4" autocomplete="off" autofocus required>
            </div>
            <button type="submit" class="btn-submit">Verify & Login</button>
        </form>
        
        <a href="login.php" class="btn-cancel">← Cancel and go back</a>
    </div>

</body>
</html>