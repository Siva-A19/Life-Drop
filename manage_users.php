<?php
session_start();

// Security Check: Only Admins allowed here
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); 
    exit();
}

require_once 'db_connect.php'; 

$message = "";
$current_admin_id = $_SESSION['user_id'];

// --- HANDLE ADMIN ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_user_id']) && isset($_POST['action'])) {
    $target_id = $_POST['target_user_id'];
    $action = $_POST['action'];
    
    if ($action === 'verify') {
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ User successfully verified! They now have a trust badge.</div>";
        }
        $stmt->close();
        
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-error'>🗑️ User account permanently deleted from the system.</div>";
        }
        $stmt->close();
    }
}

// --- FETCH ALL USERS (Except the current admin) ---
$sql = "SELECT user_id, name, email, role, profile_pic, nic_number, city, is_verified 
        FROM users 
        WHERE user_id != ? 
        ORDER BY user_id DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_admin_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Life Drop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #1d3557, #e63946, #457b9d); background-attachment: fixed; min-height: 100vh; color: #fff; padding: 40px 20px; }
        
        .nav-bar { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto 30px auto; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2); padding: 15px 30px; border-radius: 12px; }
        .nav-bar h2 { font-weight: 600; font-size: 22px; margin: 0; }
        .btn-back { background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; transition: 0.3s; border: 1px solid rgba(255,255,255,0.3); }
        .btn-back:hover { background: rgba(255, 255, 255, 0.4); }

        .glass-card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; padding: 30px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); max-width: 1200px; margin: auto; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); vertical-align: middle; }
        th { color: #ffc107; font-weight: 500; }
        
        .user-cell { display: flex; align-items: center; gap: 10px; }
        .dp-mini { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.5); }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-donor { background: rgba(40, 167, 69, 0.8); }
        .badge-requester { background: rgba(220, 53, 69, 0.8); }
        .badge-admin { background: rgba(255, 193, 7, 0.8); color: #000; }
        .verified-badge { color: #4ade80; font-size: 14px; margin-left: 5px; }

        /* Action Buttons */
        .btn-action { text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; transition: all 0.3s ease; color: white; border: none; cursor: pointer; display: inline-block; margin-right: 5px; margin-bottom: 5px; }
        
        .btn-verify { background: rgba(40, 167, 69, 0.4); border: 1px solid #28a745; }
        .btn-verify:hover { background: rgba(40, 167, 69, 0.8); }

        .btn-delete { background: rgba(220, 53, 69, 0.4); border: 1px solid #dc3545; }
        .btn-delete:hover { background: rgba(220, 53, 69, 0.8); }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; max-width: 1200px; margin: 0 auto 20px auto; }
        .alert-success { background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color: #4ade80; }
        .alert-error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #ff4d4d; }
    </style>
</head>
<body>

    <div class="nav-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
           
            <h2>Life Drop</h2>
        </div>
        <a href="dashboard.php" class="btn-back">← Dashboard</a>
    </div>

    <?php if(!empty($message)) echo $message; ?>

    <div class="glass-card">
        <h2 style="margin-bottom: 10px; font-weight: 500;">👥 User Management</h2>
        <p style="color: #ddd; margin-bottom: 20px;">Verify donor NICs to give them trust badges or remove spam accounts.</p>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>City & NIC</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                            // Determine DP
                            $dp = !empty($row['profile_pic']) && file_exists("uploads/" . $row['profile_pic']) 
                                ? "uploads/" . $row['profile_pic'] 
                                : "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=random";
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <img src="<?php echo $dp; ?>" alt="DP" class="dp-mini">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                        <?php if ($row['is_verified'] == 1): ?>
                                            <span class="verified-badge" title="Verified User">✔️</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size: 14px; color: #e0e0e0;"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($row['role']); ?>">
                                    <?php echo htmlspecialchars($row['role']); ?>
                                </span>
                            </td>
                            <td style="font-size: 14px; color: #e0e0e0;">
                                📍 <?php echo !empty($row['city']) ? htmlspecialchars($row['city']) : 'N/A'; ?><br>
                                💳 <?php echo !empty($row['nic_number']) ? htmlspecialchars($row['nic_number']) : 'No NIC'; ?>
                            </td>
                            <td>
                                <form method="POST" action="manage_users.php" style="margin: 0;">
                                    <input type="hidden" name="target_user_id" value="<?php echo $row['user_id']; ?>">
                                    
                                    <?php if ($row['is_verified'] == 0 && !empty($row['nic_number'])): ?>
                                        <button type="submit" name="action" value="verify" class="btn-action btn-verify">✔️ Verify</button>
                                    <?php endif; ?>

                                    <button type="submit" name="action" value="delete" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to completely delete this user?');">🗑️ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>