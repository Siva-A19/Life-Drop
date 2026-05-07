<?php
session_start();
require_once 'language.php';
require_once 'db_connect.php';

// Security Check: You can lock this down to 'admin' role only later!
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = ($_POST['action'] === 'approve') ? 'Approved' : 'Rejected';
    
    $update_stmt = $conn->prepare("UPDATE blood_requests SET status = ? WHERE request_id = ?");
    $update_stmt->bind_param("si", $new_status, $request_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Refresh the page to clear the processed request
    header("Location: admin_board.php");
    exit();
}

// Fetch only PENDING requests for the Admin to review
$query = "SELECT request_id, patient_name, blood_group, hospital_name, urgency_level, contact_number, prescription_image FROM blood_requests WHERE status = 'Pending' ORDER BY request_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verification - Life Drop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f1f5f9; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; color: #333; }
        
        .nav-bar { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1000px; margin-bottom: 30px; background: #fff; padding: 15px 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .logo { font-size: 20px; font-weight: 600; color: #0f172a; }
        .badge-admin { background: #1e293b; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; margin-left: 10px; }

        .requests-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; width: 100%; max-width: 1000px; }
        
        .request-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); display: flex; flex-direction: column;}
        
        /* Image Preview Section */
        .prescription-container { background: #f8fafc; width: 100%; height: 200px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        .prescription-img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
        .prescription-container:hover .prescription-img { transform: scale(1.05); }
        .view-full-btn { position: absolute; bottom: 10px; right: 10px; background: rgba(15, 23, 42, 0.8); color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; }
        .no-image { color: #94a3b8; font-size: 14px; display: flex; flex-direction: column; align-items: center; gap: 10px; }

        .card-body { padding: 20px; flex-grow: 1; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .patient-name { font-size: 18px; font-weight: 600; color: #1e293b; }
        .blood-badge { background: #fee2e2; color: #e63946; padding: 5px 10px; border-radius: 4px; font-size: 16px; font-weight: 600; border: 1px solid #fca5a5; }

        .info-row { display: flex; align-items: center; color: #64748b; font-size: 13px; margin-bottom: 8px; }
        .info-row i { width: 20px; color: #94a3b8; }
        .info-value { font-weight: 500; color: #333; margin-left: 5px; }

        .action-buttons { display: flex; gap: 10px; padding: 15px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .btn { flex: 1; padding: 10px; border: none; border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; text-align: center; }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-reject:hover { background: #dc2626; }

        .empty-state { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; border: 1px dashed #cbd5e1; color: #64748b; }
    </style>
</head>
<body>

    <div class="nav-bar">
        <div class="logo">🛡️ Life Drop <span class="badge-admin">Admin Board</span></div>
        <a href="dashboard.php" style="color: #64748b; text-decoration: none; font-size: 14px;">← Back to Dashboard</a>
    </div>

    <div class="requests-grid">
        <?php 
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
        ?>
                <div class="request-card">
                    
                    <div class="prescription-container">
                        <?php if (!empty($row['prescription_image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($row['prescription_image']); ?>" class="prescription-img" alt="Prescription">
                            <a href="uploads/<?php echo htmlspecialchars($row['prescription_image']); ?>" target="_blank" class="view-full-btn"><i class="fas fa-expand"></i> View Full</a>
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-file-image" style="font-size: 30px;"></i>
                                No Prescription Uploaded
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="header-row">
                            <div class="patient-name"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                            <div class="blood-badge"><?php echo htmlspecialchars($row['blood_group']); ?></div>
                        </div>
                        
                        <div class="info-row"><i class="fas fa-hospital"></i> <span class="info-value"><?php echo htmlspecialchars($row['hospital_name']); ?></span></div>
                        <div class="info-row"><i class="fas fa-phone"></i> <span class="info-value"><?php echo htmlspecialchars($row['contact_number']); ?></span></div>
                        <div class="info-row"><i class="fas fa-clock"></i> <span class="info-value" style="color: #d97706;"><?php echo htmlspecialchars($row['urgency_level']); ?> Urgency</span></div>
                    </div>

                    <form method="POST" action="admin_board.php" class="action-buttons">
                        <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                        
                        <button type="submit" name="action" value="approve" class="btn btn-approve">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        
                        <button type="submit" name="action" value="reject" class="btn btn-reject">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                </div>
        <?php 
            }
        } else {
        ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check" style="font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
                <h3>All Caught Up!</h3>
                <p>There are no pending requests to verify.</p>
            </div>
        <?php 
        } 
        ?>
    </div>

</body>
</html>