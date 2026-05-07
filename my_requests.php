<?php
session_start();
require_once 'language.php';
require_once 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Safe Query: We removed the ORDER BY to guarantee it never crashes!
$stmt = $conn->prepare("SELECT * FROM blood_requests WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Life Drop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f8; padding: 40px 20px; color: #333; display: flex; flex-direction: column; align-items: center; }
        
        .container { width: 100%; max-width: 900px; }
        
        .header { background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; }
        .header h2 { color: #2c3e50; font-weight: 600; font-size: 22px; }
        .btn-back { text-decoration: none; background: #f8fafc; color: #64748b; padding: 8px 16px; border-radius: 6px; font-size: 14px; border: 1px solid #cbd5e1; transition: 0.3s; font-weight: 500; }
        .btn-back:hover { background: #e2e8f0; color: #333; }

        /* Modern Request Cards */
        .request-grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); }
        
        .request-card { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; transition: transform 0.2s; }
        .request-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        
        .card-header { padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fdfdfd; }
        .patient-name { font-size: 18px; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .blood-badge { background: #fee2e2; color: #e63946; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 16px; border: 1px solid #fecaca; }

        .card-body { padding: 20px; }
        .info-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; color: #475569; font-size: 14px; }
        .info-row i { color: #94a3b8; width: 16px; margin-top: 3px; }
        .info-row strong { color: #334155; font-weight: 500; }

        /* Urgency Colors */
        .urgency-Critical { color: #ef4444; font-weight: 600; }
        .urgency-High { color: #f97316; font-weight: 600; }
        .urgency-Medium { color: #eab308; font-weight: 600; }

        .card-footer { padding: 15px 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        
        /* Status Badges */
        .status-badge { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .status-Pending { background: #fef9c3; color: #a16207; border: 1px solid #fde047; }
        .status-Approved { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-Fulfilled { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }

        .empty-state { text-align: center; padding: 60px 20px; background: #fff; border-radius: 10px; border: 1px dashed #cbd5e1; grid-column: 1 / -1; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 15px; }
        .empty-state h3 { color: #475569; margin-bottom: 10px; }
        .empty-state a { display: inline-block; margin-top: 15px; background: #0ea5e9; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 500; transition: 0.3s; }
        .empty-state a:hover { background: #0284c7; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2>📋 My Emergency Requests</h2>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>

        <div class="request-grid">
            <?php 
            // We convert the result to an array and reverse it so the newest requests show up first!
            $requests = [];
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
            $requests = array_reverse($requests);
            
            if (count($requests) > 0): 
                foreach ($requests as $row): 
                    // Set up safe default values
                    $urgency = $row['urgency_level'] ?? 'Medium';
                    $status = $row['status'] ?? 'Pending';
            ?>
                
                <div class="request-card">
                    <div class="card-header">
                        <div class="patient-name">
                            <i class="fas fa-procedures" style="color: #94a3b8;"></i> 
                            <?php echo htmlspecialchars($row['patient_name'] ?? 'Unknown Patient'); ?>
                        </div>
                        <div class="blood-badge">
                            <?php echo htmlspecialchars($row['blood_group'] ?? '-'); ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="info-row">
                            <i class="fas fa-hospital"></i>
                            <div><strong>Hospital:</strong> <br><?php echo htmlspecialchars($row['hospital_name'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Urgency:</strong> <br>
                                <span class="urgency-<?php echo htmlspecialchars($urgency); ?>">
                                    <?php echo htmlspecialchars($urgency); ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-row">
                            <i class="fas fa-phone-alt"></i>
                            <div><strong>Contact:</strong> <br><?php echo htmlspecialchars($row['contact_number'] ?? 'No number provided'); ?></div>
                        </div>
                        
                        <?php if (!empty($row['prescription_image'])): ?>
                        <div class="info-row" style="color: #10b981;">
                            <i class="fas fa-paperclip" style="color: #10b981;"></i>
                            <div><strong>Prescription Attached</strong></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <span class="status-badge status-<?php echo htmlspecialchars($status); ?>">
                            <?php 
                                if ($status == 'Pending') echo '<i class="fas fa-hourglass-half"></i> Awaiting Admin';
                                elseif ($status == 'Approved') echo '<i class="fas fa-check-circle"></i> Live on Board';
                                else echo '<i class="fas fa-handshake"></i> ' . htmlspecialchars($status);
                            ?>
                        </span>
                    </div>
                </div>

            <?php 
                endforeach; 
            else: 
            ?>
                <div class="empty-state">
                    <i class="fas fa-notes-medical"></i>
                    <h3>No requests found</h3>
                    <p style="color: #64748b;">You haven't broadcasted any emergency requests yet.</p>
                    <a href="request_blood.php"><i class="fas fa-plus"></i> Request Blood Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>