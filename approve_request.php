<?php
require_once 'db_connect.php';

// Check if an ID was passed in the URL
if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    
    // Update the database: Change status to 'Approved'
    // NOTE: If your primary key column is named something else (like request_id), change 'id = ?' to match it.
    $stmt = $conn->prepare("UPDATE blood_requests SET status = 'Approved' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    
    if ($stmt->execute()) {
        // Success! Show an alert and send them right back to the view page
        echo "<script>
                alert('✅ Emergency Request Approved!');
                window.location.href = 'view_requests.php';
              </script>";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Error: No request ID was provided.";
}
?>