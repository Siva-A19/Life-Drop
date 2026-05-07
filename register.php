<?php
session_start();
require_once 'language.php';
require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $blood_group = $_POST['blood_group'];
    $city = $_POST['city'];
    $nic = $_POST['nic'];
    $phone = $_POST['phone']; // Grab the new phone number!
    $role = 'donor'; 

    // 1. Check if Email or NIC already exists
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR nic_number = ? LIMIT 1");
    $check_stmt->bind_param("ss", $email, $nic);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "An account with this Email or NIC already exists.";
    } else {
        // 2. Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Insert the new Donor (Now including phone_number!)
        $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role, blood_group, city, nic_number, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssssssss", $name, $email, $hashed_password, $role, $blood_group, $city, $nic, $phone);
        
        if ($insert_stmt->execute()) {
            $success = "Account created successfully! You can now login.";
        } else {
            $error = "Database error. Please try again.";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lang['register_title']) ? $lang['register_title'] : 'Register'; ?> - <?php echo isset($lang['app_name']) ? $lang['app_name'] : 'Life Drop'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #333; padding: 20px; }
        
        .top-bar { position: absolute; top: 20px; right: 30px; display: flex; gap: 8px; }
        .lang-btn { background: #fff; color: #666; text-decoration: none; padding: 4px 10px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; transition: 0.3s; }
        .lang-btn:hover, .lang-active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }

        .app-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 40px; border-radius: 8px; width: 100%; max-width: 600px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        .logo-text { font-size: 24px; font-weight: 600; margin-bottom: 25px; color: #2c3e50; text-align: center; }

        .input-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .input-group { position: relative; flex: 1; min-width: 200px; }
        
        .input-field { width: 100%; padding: 12px 40px 12px 15px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; color: #333; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .input-field:focus { border-color: #0ea5e9; }
        .input-field::placeholder { color: #94a3b8; }
        
        select.input-field { appearance: none; cursor: pointer; color: #64748b; }
        select.input-field:required:invalid { color: #94a3b8; }
        option[value=""][disabled] { display: none; }

        .input-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none; }

        .btn-submit { width: 100%; max-width: 300px; margin: 20px auto 0 auto; display: block; padding: 12px; background: #0ea5e9; border: none; border-radius: 4px; color: white; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #0284c7; }

        .error-msg { background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 4px; border: 1px solid #f87171; text-align: center; font-size: 14px; margin-bottom: 20px; }
        .success-msg { background: #ecfdf5; color: #10b981; padding: 10px; border-radius: 4px; border: 1px solid #34d399; text-align: center; font-size: 14px; margin-bottom: 20px; }
        
        .help-text { font-size: 13px; color: #64748b; margin-top: 20px; text-align: center; }
        .help-text a { color: #0ea5e9; text-decoration: none; font-weight: 500; }
        .help-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="top-bar">
        <a href="?lang=en" class="lang-btn <?php if($_SESSION['lang'] == 'en') echo 'lang-active'; ?>">EN</a>
        <a href="?lang=si" class="lang-btn <?php if($_SESSION['lang'] == 'si') echo 'lang-active'; ?>">සිං</a>
        <a href="?lang=ta" class="lang-btn <?php if($_SESSION['lang'] == 'ta') echo 'lang-active'; ?>">தமிழ்</a>
    </div>

    <div class="app-card">
        <div class="logo-text">💧 <?php echo isset($lang['register_title']) ? $lang['register_title'] : 'Become a Donor'; ?></div>

        <?php if (!empty($error)) echo "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>
        <?php if (!empty($success)) echo "<div class='success-msg'><i class='fas fa-check-circle'></i> $success</div>"; ?>

        <form method="POST" action="register.php">
            <div class="input-row">
                <div class="input-group">
                    <input type="text" class="input-field" name="name" placeholder="<?php echo isset($lang['name']) ? $lang['name'] : 'Full Name'; ?> *" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
                <div class="input-group">
                    <input type="email" class="input-field" name="email" placeholder="<?php echo isset($lang['email']) ? $lang['email'] : 'Email Address'; ?> *" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="input-row">
                <div class="input-group">
                    <select class="input-field" name="blood_group" required>
                        <option value="" disabled selected><?php echo isset($lang['blood_group']) ? $lang['blood_group'] : 'Blood Group'; ?> *</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                    <i class="fas fa-tint input-icon" style="color: #ef4444;"></i>
                </div>
                <div class="input-group">
                    <input type="text" class="input-field" name="nic" placeholder="<?php echo isset($lang['nic']) ? $lang['nic'] : 'NIC Number'; ?> *" required>
                    <i class="fas fa-id-card input-icon"></i>
                </div>
            </div>

            <div class="input-row">
                <div class="input-group">
                   <!-- CHANGED class="form-control" to class="input-field" HERE -->
                   <select name="city" class="input-field" required>
                      <option value="" disabled selected>Nearest hospital...</option>
                      <option value="National Hospital Colombo">National Hospital Colombo</option>
                      <option value="Nawaloka Hospital">Nawaloka Hospital</option>
                      <option value="Jaffna Teaching Hospital">Jaffna Teaching Hospital</option>
                      <option value="Kandy General Hospital">Kandy General Hospital</option>
                      <option value="Karapitiya Teaching Hospital">Karapitiya Teaching Hospital</option>
                </select>
                    <i class="fas fa-city input-icon"></i>
                </div>
                <div class="input-group">
                    <input type="text" class="input-field" name="phone" placeholder="<?php echo isset($lang['phone_no']) ? $lang['phone_no'] : 'Phone No.'; ?> *" required>
                    <i class="fas fa-phone input-icon"></i>
                </div>
            </div>

            <div class="input-row">
                <div class="input-group">
                    <input type="password" class="input-field" name="password" placeholder="<?php echo isset($lang['password']) ? $lang['password'] : 'Password'; ?> *" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit"><?php echo isset($lang['register_btn']) ? $lang['register_btn'] : 'Create Account'; ?></button>
        </form>

        <div class="help-text">
            <?php echo isset($lang['already_account']) ? $lang['already_account'] : 'Already have an account?'; ?> <a href="login.php"><?php echo isset($lang['login_here']) ? $lang['login_here'] : 'Login Here'; ?></a>
        </div>
    </div>

</body>
</html>