<?php
session_start();
require_once 'language.php'; 
require_once 'db_connect.php'; 
 
$error = '';
 
// Handle the Login form submission (Works for BOTH Donors and Registered Requesters!)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
 
    $stmt = $conn->prepare("SELECT user_id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
 
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Smart Routing: Send them to the right dashboard based on their role
            if (strtolower($user['role']) === 'donor') {
                header("Location: dashboard.php");
            } else {
                // If they are a requester, send them to the new request board we built!
                header("Location: my_requests.php"); 
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>
 
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - <?php echo isset($lang['app_name']) ? $lang['app_name'] : 'Life Drop'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        
        body { background-color: #f4f7f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #333; padding: 20px;}
        
        .top-bar { position: absolute; top: 20px; right: 30px; display: flex; gap: 8px; }
        .lang-btn { background: #fff; color: #666; text-decoration: none; padding: 4px 10px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; transition: 0.3s; }
        .lang-btn:hover, .lang-active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
 
        .app-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 40px; border-radius: 8px; width: 100%; max-width: 800px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        .logo-text { font-size: 24px; font-weight: 600; margin-bottom: 25px; color: #2c3e50; text-align: center; }
 
        /* Upgraded Toggle Container for 3 buttons */
        .toggle-container { position: relative; display: flex; background: #f1f5f9; border-radius: 8px; padding: 4px; margin: 0 auto 30px auto; max-width: 600px; border: 1px solid #e2e8f0; }
        .toggle-btn { flex: 1; padding: 10px; font-size: 14px; font-weight: 500; color: #64748b; border: none; background: transparent; cursor: pointer; position: relative; z-index: 2; transition: color 0.3s; }
        .toggle-btn.active { color: #fff; }
        /* Slider is now exactly 1/3rd of the width */
        .toggle-slider { position: absolute; top: 4px; left: 4px; width: calc(33.333% - 4px); height: calc(100% - 8px); background: #0ea5e9; border-radius: 6px; transition: transform 0.3s ease, background-color 0.3s ease; z-index: 1; }
 
        .form-container { display: none; animation: fadeIn 0.3s; }
        .form-container.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
 
        .input-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .input-group { position: relative; flex: 1; min-width: 200px; }
        
        .input-field { width: 100%; padding: 12px 40px 12px 15px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; color: #333; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .input-field:focus { border-color: #0ea5e9; }
        .input-field::placeholder { color: #94a3b8; }
        .input-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; }
 
        .btn-submit { width: 100%; max-width: 300px; margin: 20px auto 0 auto; display: block; padding: 12px; border: none; border-radius: 4px; color: white; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.3s; }
        /* Distinct button colors based on form */
        #donorForm .btn-submit { background: #0ea5e9; }
        #donorForm .btn-submit:hover { background: #0284c7; }
        #regRequesterForm .btn-submit { background: #8b5cf6; } /* Purple for returning requesters */
        #regRequesterForm .btn-submit:hover { background: #7c3aed; }
        
        .btn-pin { background: #e2e8f0; color: #94a3b8; border: none; padding: 10px 24px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; display: block; margin: 20px auto 0 auto; letter-spacing: 0.5px; }
        .btn-pin:hover { background: #cbd5e1; color: #64748b; }
 
        .error-msg { background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 4px; border: 1px solid #f87171; text-align: center; font-size: 14px; margin-bottom: 20px; }
        .help-text { font-size: 13px; color: #64748b; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
 
    <div class="top-bar">
        <a href="?lang=en" class="lang-btn <?php if(($_SESSION['lang'] ?? 'en') == 'en') echo 'lang-active'; ?>">EN</a>
        <a href="?lang=si" class="lang-btn <?php if(($_SESSION['lang'] ?? '') == 'si') echo 'lang-active'; ?>">සිං</a>
        <a href="?lang=ta" class="lang-btn <?php if(($_SESSION['lang'] ?? '') == 'ta') echo 'lang-active'; ?>">தமிழ்</a>
    </div>
 
    <div class="app-card">
        <div class="logo-text">💧 <?php echo isset($lang['app_name']) ? $lang['app_name'] : 'Life Drop'; ?></div>
 
        <!-- 3-WAY TOGGLE SWITCH -->
        <div class="toggle-container">
            <div class="toggle-slider" id="sliderBg"></div>
            
            <button class="toggle-btn active" id="btnDonor" onclick="switchMode('donor')">
                <?php echo isset($lang['donor_tab']) ? $lang['donor_tab'] : 'I am a Donor'; ?>
            </button>
            
            <button class="toggle-btn" id="btnRegRequester" onclick="switchMode('reg_requester')">
                Check Request Status
            </button>
            
            <button class="toggle-btn" id="btnRequester" onclick="switchMode('requester')">
                <?php echo isset($lang['requester_tab']) ? $lang['requester_tab'] : 'New Emergency'; ?>
            </button>
        </div>
 
        <?php if (!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>
 
        <!-- 1. DONOR LOGIN FORM -->
        <div id="donorForm" class="form-container active">
            <form method="POST" action="">
                <div class="input-row">
                    <div class="input-group" style="max-width: 400px; margin: 0 auto;">
                        <input type="email" class="input-field" name="email" placeholder="<?php echo isset($lang['email']) ? $lang['email'] : 'Email'; ?>" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
                <div class="input-row">
                    <div class="input-group" style="max-width: 400px; margin: 0 auto;">
                        <input type="password" class="input-field" name="password" placeholder="<?php echo isset($lang['password']) ? $lang['password'] : 'Password'; ?>" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><?php echo isset($lang['login_btn']) ? $lang['login_btn'] : 'Login as Donor'; ?></button>
            </form>
            <div class="help-text">
                <?php echo isset($lang['no_account']) ? $lang['no_account'] : 'No account?'; ?> <a href="register.php" style="color: #0ea5e9; text-decoration: none; font-weight: 500;"><?php echo isset($lang['register_here']) ? $lang['register_here'] : 'Register Here'; ?></a>
            </div>
        </div>

        <!-- 2. REGISTERED REQUESTER FORM (NEW!) -->
        <div id="regRequesterForm" class="form-container">
            <form method="POST" action="">
                <div class="input-row">
                    <div class="input-group" style="max-width: 400px; margin: 0 auto;">
                        <!-- Uses same 'email' and 'password' names to reuse your PHP code! -->
                        <input type="email" class="input-field" name="email" placeholder="Registered Email" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
                <div class="input-row">
                    <div class="input-group" style="max-width: 400px; margin: 0 auto;">
                        <input type="password" class="input-field" name="password" placeholder="Password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Login to Check Status</button>
            </form>
            <div class="help-text">
                Need an account? <a href="requester_register.php" style="color: #8b5cf6; text-decoration: none; font-weight: 500;">Register as Requester</a>
            </div>
        </div>
 
        <!-- 3. NEW EMERGENCY / OTP FORM -->
        <div id="requesterForm" class="form-container">
            <form method="POST" action="verify_otp.php">
                <div class="input-row">
                    <div class="input-group">
                        <input type="text" class="input-field" name="phone" placeholder="<?php echo isset($lang['phone_no']) ? $lang['phone_no'] : 'Phone No. *'; ?>" required>
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                    <div class="input-group">
                        <input type="text" class="input-field" name="nic" placeholder="<?php echo isset($lang['nic_req']) ? $lang['nic_req'] : 'NIC *'; ?>" required>
                        <i class="fas fa-id-card input-icon"></i>
                    </div>
                    <div class="input-group">
                        <input type="email" class="input-field" name="req_email" placeholder="<?php echo isset($lang['email_opt']) ? $lang['email_opt'] : 'Email (Optional)'; ?>">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
 
                <div class="input-row">
                    <div class="input-group">
                        <input type="text" class="input-field" name="req_name" placeholder="<?php echo isset($lang['name_req']) ? $lang['name_req'] : 'Name *'; ?>" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                 
                <button type="submit" class="btn-pin"><?php echo isset($lang['send_pin']) ? $lang['send_pin'] : 'SEND PIN'; ?></button>
            </form>
            <div class="help-text" style="margin-top: 15px;">
                <?php echo isset($lang['emergency_help_text']) ? $lang['emergency_help_text'] : 'Fast-track emergency access. We will verify via SMS.'; ?>
            </div>
        </div>
 
    </div>
 
    <script>
        function switchMode(mode) {
            const sliderBg = document.getElementById('sliderBg');
            const btnDonor = document.getElementById('btnDonor');
            const btnRegRequester = document.getElementById('btnRegRequester');
            const btnRequester = document.getElementById('btnRequester');
            
            const donorForm = document.getElementById('donorForm');
            const regRequesterForm = document.getElementById('regRequesterForm');
            const requesterForm = document.getElementById('requesterForm');
 
            // Reset all active classes
            btnDonor.classList.remove('active');
            btnRegRequester.classList.remove('active');
            btnRequester.classList.remove('active');
            
            donorForm.classList.remove('active');
            regRequesterForm.classList.remove('active');
            requesterForm.classList.remove('active');

            if (mode === 'donor') {
                sliderBg.style.transform = 'translateX(0)';
                sliderBg.style.background = '#0ea5e9'; // Blue
                btnDonor.classList.add('active');
                donorForm.classList.add('active');
                
            } else if (mode === 'reg_requester') {
                sliderBg.style.transform = 'translateX(100%)';
                sliderBg.style.background = '#8b5cf6'; // Purple
                btnRegRequester.classList.add('active');
                regRequesterForm.classList.add('active');
                
            } else if (mode === 'requester') {
                sliderBg.style.transform = 'translateX(200%)';
                sliderBg.style.background = '#e63946'; // Red
                btnRequester.classList.add('active');
                requesterForm.classList.add('active');
            }
        }
    </script>
</body>
</html>