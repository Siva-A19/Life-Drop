<?php
session_start();
require_once 'language.php';
require_once 'db_connect.php';

// Security Check: Kick out anyone not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch current user details from the database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, role, city, blood_group FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lang['dashboard']) ? $lang['dashboard'] : 'Dashboard'; ?> - <?php echo isset($lang['app_name']) ? $lang['app_name'] : 'Life Drop'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; color: #333; }
        
        .nav-bar { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 900px; margin-bottom: 30px; background: #fff; padding: 15px 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .logo { font-size: 20px; font-weight: 600; color: #2c3e50; }
        
        .nav-actions { display: flex; gap: 15px; align-items: center; }
        .lang-switch { display: flex; gap: 8px; }
        .lang-btn { background: #fff; color: #666; text-decoration: none; padding: 4px 10px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; transition: 0.3s; }
        .lang-btn:hover, .lang-active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
        
        .btn-logout { background: #fee2e2; color: #ef4444; text-decoration: none; padding: 6px 15px; border-radius: 4px; font-size: 14px; font-weight: 500; transition: 0.3s; border: 1px solid #f87171; }
        .btn-logout:hover { background: #ef4444; color: #fff; }

        .dashboard-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; width: 100%; max-width: 900px; }
        @media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } }

        .app-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        
        /* Profile Sidebar */
        .profile-section { text-align: center; }
        .avatar-circle { width: 80px; height: 80px; background: #e0f2fe; color: #0ea5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 15px auto; font-weight: 600; }
        .profile-name { font-size: 20px; font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
        .profile-role { display: inline-block; background: #f1f5f9; color: #64748b; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; }
        .profile-detail { color: #64748b; font-size: 14px; margin-bottom: 10px; }
        .profile-detail i { width: 20px; color: #94a3b8; }

        /* Main Content Area */
        .welcome-header { font-size: 24px; font-weight: 600; color: #2c3e50; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        
        .action-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .action-info h3 { font-size: 18px; color: #2c3e50; margin-bottom: 5px; }
        .action-info p { color: #64748b; font-size: 14px; }
        
        .btn-action { text-decoration: none; padding: 12px 24px; border-radius: 4px; font-size: 14px; font-weight: 500; transition: 0.3s; color: white; }
        .btn-red { background: #e63946; }
        .btn-red:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); }
        .btn-blue { background: #0ea5e9; }
        .btn-blue:hover { background: #0284c7; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2); }

    </style>
</head>
<body>

    <div class="nav-bar">
        <div class="logo">💧 <?php echo isset($lang['app_name']) ? $lang['app_name'] : 'Life Drop'; ?></div>
        <div class="nav-actions">
            <div class="lang-switch">
                <a href="?lang=en" class="lang-btn <?php if($_SESSION['lang'] == 'en') echo 'lang-active'; ?>">EN</a>
                <a href="?lang=si" class="lang-btn <?php if($_SESSION['lang'] == 'si') echo 'lang-active'; ?>">සිං</a>
                <a href="?lang=ta" class="lang-btn <?php if($_SESSION['lang'] == 'ta') echo 'lang-active'; ?>">தமிழ்</a>
            </div>
            <a href="?logout=true" class="btn-logout"><i class="fas fa-sign-out-alt"></i> <?php echo isset($lang['logout']) ? $lang['logout'] : 'Logout'; ?></a>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <div class="app-card profile-section">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
            <div class="profile-role"><?php echo htmlspecialchars($user['role']); ?></div>
            
            <?php if (!empty($user['city'])): ?>
                <div class="profile-detail"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['city']); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($user['blood_group'])): ?>
                <div class="profile-detail"><i class="fas fa-tint" style="color:#e63946;"></i> <?php echo htmlspecialchars($user['blood_group']); ?></div>
            <?php endif; ?>
        </div>

       <div class="app-card">
            <div class="welcome-header">
                <?php echo isset($lang['welcome']) ? $lang['welcome'] : 'Welcome'; ?>, <?php echo htmlspecialchars($user['name']); ?>!
            </div>

            <?php if (strtolower($user['role']) === 'admin'): ?>
                <!-- ADMIN DASHBOARD -->
                <div class="action-box">
                    <div class="action-info">
                        <h3>🛡️ Admin Board</h3>
                        <p>Review and verify pending emergency blood requests.</p>
                    </div>
                    <a href="admin_board.php" class="btn-action btn-blue">Verify Requests</a>
                </div>

            <?php elseif (strtolower($user['role']) === 'donor'): ?>
                <!-- DONOR DASHBOARD -->
                <div class="action-box">
                    <div class="action-info">
                        <h3>🩸 Emergency Board</h3>
                        <p>View verified urgent requests in your area.</p>
                    </div>
                    <a href="view_requests.php" class="btn-action btn-red">View Requests</a>
                </div>

            <?php else: ?>
                <!-- REQUESTER DASHBOARD -->
                <div class="action-box">
                    <div class="action-info">
                        <h3>🚨 <?php echo isset($lang['need_blood']) ? $lang['need_blood'] : 'Need Blood?'; ?></h3>
                        <p>Submit an emergency request to our network.</p>
                    </div>
                    <a href="request_blood.php" class="btn-action btn-red">Request Blood Now</a>
                </div>
                
                <div class="action-box">
                    <div class="action-info">
                        <h3>📋 <?php echo isset($lang['my_requests']) ? $lang['my_requests'] : 'My Requests'; ?></h3>
                        <p>Track the status of your broadcasted emergencies.</p>
                    </div>
                    <a href="my_requests.php" class="btn-action" style="background: #e2e8f0; color: #475569;">View Status</a>
                </div>
            <?php endif; ?>
            
        </div>
    </div>

</body>
</html>