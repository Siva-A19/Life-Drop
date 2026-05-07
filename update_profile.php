<?php
session_start();
require_once 'db_connect.php';
// Security: Kick out anyone not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// NEW: Kick out Admins! They don't need public profiles.
if ($_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nic_number = $_POST['nic_number'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    
    // Check if they uploaded a new Profile Picture
    $profile_pic_query = ""; // Default empty
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed_exts = array('jpg', 'jpeg', 'png');

        if (in_array($file_extension, $allowed_exts)) {
            $new_dp_name = 'dp_' . $user_id . '_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $new_dp_name)) {
                $profile_pic_query = ", profile_pic = '$new_dp_name'";
            }
        } else {
            $message = '<div class="alert alert-error">Invalid image format. Only JPG/PNG allowed.</div>';
        }
    }

    // UPDATED: Changed WHERE id = ? to WHERE user_id = ?
    $sql = "UPDATE users SET nic_number = ?, dob = ?, address = ?, city = ? $profile_pic_query WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nic_number, $dob, $address, $city, $user_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Profile updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-error">Error updating profile.</div>';
    }
    $stmt->close();
}

// --- FETCH CURRENT USER DATA ---
// UPDATED: Changed WHERE id = ? to WHERE user_id = ?
$stmt = $conn->prepare("SELECT name, email, role, profile_pic, nic_number, dob, address, city FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Life Drop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #1d3557, #e63946, #457b9d); background-attachment: fixed; min-height: 100vh; color: #fff; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
        
        .nav-bar { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 700px; margin-bottom: 30px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2); padding: 15px 30px; border-radius: 12px; }
        .nav-bar h2 { font-weight: 600; font-size: 20px; }
        .btn-action { background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; transition: 0.3s; border: 1px solid rgba(255,255,255,0.3); }
        .btn-action:hover { background: rgba(255, 255, 255, 0.4); }

        .glass-card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; padding: 40px; width: 100%; max-width: 700px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); }
        
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .dp-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #4ade80; }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; color: #e0e0e0; }
        .input-group input, .input-group textarea { width: 100%; padding: 12px 15px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; font-size: 14px; color: #fff; outline: none; transition: 0.3s; }
        .input-group input:focus, .input-group textarea:focus { background: rgba(255, 255, 255, 0.2); border-color: #4ade80; }
        
        .file-input { padding: 10px !important; border: 1px dashed rgba(255, 255, 255, 0.5) !important; cursor: pointer; }
        .file-input::file-selector-button { background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 8px 12px; border-radius: 6px; margin-right: 15px; cursor: pointer; }
        
        .submit-btn { width: 100%; padding: 14px; background: rgba(40, 167, 69, 0.8); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .submit-btn:hover { background: rgba(40, 167, 69, 1); transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        .alert-success { background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color: #4ade80; }
        .alert-error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #ff4d4d; }
    </style>
</head>
<body>

    <div class="nav-bar">
    <h2>Life Drop</h2>
    <a href="dashboard.php?logout=true" class="btn-action">Logout</a>
</div>
    <a href="dashboard.php?logout=true" class="btn-action" style="border: 1px solid rgba(255,255,255,0.3);">Logout</a>
</div>

    <div class="glass-card">
        <div class="profile-header">
            <?php 
                $dp_path = !empty($user['profile_pic']) && file_exists("uploads/" . $user['profile_pic']) ? "uploads/" . $user['profile_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=random";
            ?>
            <img src="<?php echo $dp_path; ?>" alt="Profile Picture" class="dp-preview">
            <div>
                <h2 style="font-weight: 500;"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p style="color: #4ade80; font-size: 14px; text-transform: uppercase; font-weight: 600;"><?php echo htmlspecialchars($user['role']); ?> Account</p>
            </div>
        </div>

        <?php echo $message; ?>

        <form method="POST" action="update_profile.php" enctype="multipart/form-data">
            
            <div class="input-group">
                <label>Upload New Profile Picture</label>
                <input type="file" name="profile_pic" class="file-input" accept="image/png, image/jpeg">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="input-group">
                    <label>NIC Number (For Verification)</label>
                    <input type="text" name="nic_number" value="<?php echo htmlspecialchars($user['nic_number'] ?? ''); ?>" placeholder="E.g. 199912345678">
                </div>
                
                <div class="input-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                </div>
            </div>

            <div class="input-group">
                <label>City / District</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="E.g. Colombo">
            </div>

            <div class="input-group">
                <label>Full Residential Address (Private)</label>
                <textarea name="address" rows="3" placeholder="Enter your full street address..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="submit-btn">💾 Save & Update Profile</button>
        </form>
    </div>

</body>
</html>