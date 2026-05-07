<?php
session_start();
require_once 'db_connect.php';

// Kick out anyone not logged in, or anyone who IS NOT a donor
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'donor') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Get the Donor's city (hospital) from the database
$stmt_donor = $conn->prepare("SELECT city FROM users WHERE user_id = ?");
$stmt_donor->bind_param("i", $user_id);
$stmt_donor->execute();
$donor_result = $stmt_donor->get_result();
$donor_data = $donor_result->fetch_assoc();
$donor_city = $donor_data['city'];
$stmt_donor->close();

// 2. Fetch requests from the database
$stmt_req = $conn->prepare("SELECT * FROM blood_requests WHERE status = 'Approved' AND hospital_name = ?");
$stmt_req->bind_param("s", $donor_city);
$stmt_req->execute();
$raw_requests = $stmt_req->get_result();

// 3. THE DOUBLE LOCK (Done BEFORE we draw the HTML!)
// We put all the truly approved requests into a safe array first.
$matched_requests = [];
while ($row = $raw_requests->fetch_assoc()) {
    if (strtolower($row['status']) === 'approved') {
        $matched_requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urgent Requests - Life Drop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f8; padding: 40px 20px; color: #333; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 900px; }
        .header { background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-back { text-decoration: none; background: #e2e8f0; color: #475569; padding: 8px 16px; border-radius: 4px; font-size: 14px; }
        
        .request-card { background: #fff; padding: 25px; border-radius: 8px; border-left: 5px solid #e63946; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .request-info h3 { color: #2c3e50; margin-bottom: 5px; }
        .request-info p { color: #64748b; font-size: 14px; margin-bottom: 3px; }
        .blood-badge { background: #fee2e2; color: #e63946; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 18px; display: inline-block; margin-top: 10px; }
        
        .btn-donate { text-decoration: none; background: #e63946; color: white; padding: 12px 24px; border-radius: 4px; font-weight: 500; transition: 0.3s; }
        .btn-donate:hover { background: #dc2626; }
        
        .debug-badge { background: #000; color: #fff; padding: 3px 8px; font-size: 11px; border-radius: 4px; margin-left: 10px; font-family: monospace; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <!-- This is where the magic happens! We check the array count first. -->
            <?php if (count($matched_requests) > 0): ?>
                <h2>🩸 Urgent Requests in <?php echo htmlspecialchars($donor_city ?? 'Your Area'); ?></h2>
            <?php else: ?>
                <h2 style="color: #64748b;">🩸 No Urgent Requests</h2>
            <?php endif; ?>
            
            <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <?php 
        // If we have approved requests, loop through our safe array and display them
        if (count($matched_requests) > 0): 
            foreach ($matched_requests as $row): 
        ?>
                <div class="request-card">
                    <div class="request-info">
                        <h3>Patient: <?php echo htmlspecialchars($row['patient_name'] ?? 'Unknown'); ?>
                            <!-- DEBUG BADGE -->
                            <span class="debug-badge">DB Status: <?php echo htmlspecialchars($row['status']); ?></span>
                        </h3>
                        <p>🏥 <strong>Hospital:</strong> <?php echo htmlspecialchars($row['hospital_name'] ?? 'Not specified'); ?></p>
                        <p>📞 <strong>Contact:</strong> <?php echo htmlspecialchars($row['contact_number'] ?? 'Not provided'); ?></p>
                        <div class="blood-badge"><?php echo htmlspecialchars($row['blood_group'] ?? '-'); ?></div>
                    </div>
                    <div>
                        <a href="process_donation.php?id=<?php echo $row['request_id']; ?>" class="btn-donate">I Can Donate</a>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <!-- If the safe array is empty, show the empty message -->
            <div class="request-card" style="border-left-color: #cbd5e1; justify-content: center; text-align: center;">
                <p style="color: #64748b; font-size: 16px;">There are no verified urgent requests in your area right now. You are awesome for checking!</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>