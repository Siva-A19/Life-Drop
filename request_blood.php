<?php
session_start();
require_once 'language.php';
require_once 'db_connect.php';

// Security: Only logged-in Requesters can access this page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $patient_name = $_POST['patient_name'];
    $blood_group = $_POST['blood_group'];
    $hospital_name = $_POST['hospital_name'];
    $urgency_level = $_POST['urgency_level'];
    $contact_number = $_POST['contact_number']; 
    $status = 'Pending'; 

    $prescription_image = NULL;
    if (isset($_FILES['prescription']) && $_FILES['prescription']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['prescription']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = array('jpg', 'jpeg', 'png');
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['prescription']['tmp_name'], $target_file)) {
                $prescription_image = $file_name;
            } else {
                $error = "Failed to upload image. Please check folder permissions.";
            }
        } else {
            $error = "Only JPG, JPEG, and PNG files are allowed.";
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO blood_requests (user_id, patient_name, blood_group, hospital_name, urgency_level, prescription_image, status, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id, $patient_name, $blood_group, $hospital_name, $urgency_level, $prescription_image, $status, $contact_number);
        
        if ($stmt->execute()) {
            $success = "Your emergency request has been submitted successfully and is awaiting admin verification.";
        } else {
            $error = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lang['req_blood_title']) ? $lang['req_blood_title'] : 'Request Blood'; ?> - <?php echo isset($lang['app_name']) ? $lang['app_name'] : 'Life Drop'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #333; padding: 20px; }
        
        .nav-bar { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 800px; margin-bottom: 20px; }
        .btn-back { background: #fff; color: #64748b; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; border: 1px solid #cbd5e1; transition: 0.3s; }
        .btn-back:hover { background: #e2e8f0; color: #333; }
        
        .lang-switch { display: flex; gap: 8px; }
        .lang-btn { background: #fff; color: #666; text-decoration: none; padding: 4px 10px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; transition: 0.3s; }
        .lang-btn:hover, .lang-active { background: #e63946; color: #fff; border-color: #e63946; } 

        .app-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 40px; border-radius: 8px; width: 100%; max-width: 800px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        .logo-text { font-size: 24px; font-weight: 600; margin-bottom: 25px; color: #e63946; text-align: center; } 

        .input-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .input-group { position: relative; flex: 1; min-width: 200px; }
        
        .input-field { width: 100%; padding: 12px 40px 12px 15px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; color: #333; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .input-field:focus { border-color: #e63946; }
        .input-field::placeholder { color: #94a3b8; }
        
        select.input-field { appearance: none; cursor: pointer; color: #64748b; }
        option[value=""][disabled] { display: none; }

        .input-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none; }

        /* Updated File Upload Styling */
        .file-upload-wrapper { width: 100%; position: relative; border: 2px dashed #cbd5e1; border-radius: 4px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s; background: #f8fafc; margin-bottom: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 120px; }
        .file-upload-wrapper:hover { border-color: #e63946; background: #fff1f2; }
        .file-upload-wrapper input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10; }
        .file-upload-text { color: #64748b; font-size: 14px; font-weight: 500; transition: 0.3s; pointer-events: none; }
        
        /* Image Preview Box */
        #imagePreview { display: none; max-width: 100%; max-height: 150px; border-radius: 4px; margin-top: 10px; border: 1px solid #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

        .btn-submit { width: 100%; max-width: 300px; margin: 0 auto; display: block; padding: 14px; background: #e63946; border: none; border-radius: 4px; color: white; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 0.5px;}
        .btn-submit:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);}

        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 4px; border: 1px solid #f87171; text-align: center; font-size: 14px; margin-bottom: 20px; font-weight: 500;}
        .success-msg { background: #ecfdf5; color: #10b981; padding: 12px; border-radius: 4px; border: 1px solid #34d399; text-align: center; font-size: 14px; margin-bottom: 20px; font-weight: 500;}
    </style>
</head>
<body>

    <div class="nav-bar">
        <a href="my_requests.php" class="btn-back">← <?php echo isset($lang['dashboard']) ? $lang['dashboard'] : 'Dashboard'; ?></a>
        <div class="lang-switch">
            <a href="?lang=en" class="lang-btn <?php if($_SESSION['lang'] == 'en') echo 'lang-active'; ?>">EN</a>
            <a href="?lang=si" class="lang-btn <?php if($_SESSION['lang'] == 'si') echo 'lang-active'; ?>">සිං</a>
            <a href="?lang=ta" class="lang-btn <?php if($_SESSION['lang'] == 'ta') echo 'lang-active'; ?>">தமிழ்</a>
        </div>
    </div>

    <div class="app-card">
        <div class="logo-text">🚨 <?php echo isset($lang['req_blood_title']) ? $lang['req_blood_title'] : 'Emergency Blood Request'; ?></div>

        <?php if (!empty($error)) echo "<div class='error-msg'><i class='fas fa-exclamation-triangle'></i> $error</div>"; ?>
        <?php if (!empty($success)) echo "<div class='success-msg'><i class='fas fa-check-circle'></i> $success</div>"; ?>

        <form method="POST" action="request_blood.php" enctype="multipart/form-data">
            
            <div class="input-row">
                <div class="input-group">
                    <input type="text" class="input-field" name="patient_name" placeholder="<?php echo isset($lang['patient_name']) ? $lang['patient_name'] : 'Patient Name'; ?> *" required>
                    <i class="fas fa-user-injured input-icon"></i>
                </div>
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
                    <i class="fas fa-tint input-icon" style="color: #e63946;"></i>
                </div>
            </div>

            <div class="input-row">
                <div class="input-group">
                   <!-- CHANGED name="hospital_name" and class="input-field" HERE -->
                   <select name="hospital_name" class="input-field" required>
                        <option value="" disabled selected>Select the Hospital...</option>
                        <option value="National Hospital Colombo">National Hospital Colombo</option>
                        <option value="Nawaloka Hospital">Nawaloka Hospital</option>
                        <option value="Jaffna Teaching Hospital">Jaffna Teaching Hospital</option>
                        <option value="Kandy General Hospital">Kandy General Hospital</option>
                        <option value="Karapitiya Teaching Hospital">Karapitiya Teaching Hospital</option>
                    </select>
                    <i class="fas fa-hospital input-icon"></i>
                </div>
                <div class="input-group">
                    <select class="input-field" name="urgency_level" required>
                        <option value="" disabled selected><?php echo isset($lang['urgency']) ? $lang['urgency'] : 'Urgency Level'; ?> *</option>
                        <option value="Critical"><?php echo isset($lang['critical']) ? $lang['critical'] : 'Critical (Within 24 Hours)'; ?></option>
                        <option value="High"><?php echo isset($lang['high']) ? $lang['high'] : 'High (Within 2-3 Days)'; ?></option>
                        <option value="Medium"><?php echo isset($lang['medium']) ? $lang['medium'] : 'Medium (Within a Week)'; ?></option>
                    </select>
                    <i class="fas fa-clock input-icon"></i>
                </div>
            </div>

            <div class="input-row">
                <div class="input-group">
                    <input type="text" class="input-field" name="contact_number" placeholder="<?php echo isset($lang['phone_no']) ? $lang['phone_no'] : 'Contact Number'; ?> *" required>
                    <i class="fas fa-phone input-icon"></i>
                </div>
            </div>

            <div class="file-upload-wrapper">
                <input type="file" name="prescription" id="prescriptionInput" accept=".jpg, .jpeg, .png">
                
                <div class="file-upload-text" id="uploadText">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 24px; color: #94a3b8; margin-bottom: 10px; display: block;"></i>
                    <?php echo isset($lang['prescription']) ? $lang['prescription'] : 'Upload Prescription (Image)'; ?>
                </div>
                
                <img id="imagePreview" src="" alt="Preview">
            </div>

            <button type="submit" class="btn-submit">Submit for Admin Approval</button>
        </form>
    </div>

    <script>
        document.getElementById('prescriptionInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const uploadText = document.getElementById('uploadText');
            const imagePreview = document.getElementById('imagePreview');

            if (file) {
                // Change text to show success and filename
                uploadText.innerHTML = '<i class="fas fa-check-circle" style="font-size: 24px; color: #10b981; margin-bottom: 10px; display: block;"></i> <span style="color:#10b981; font-weight:600;">Attached:</span> ' + file.name;
                
                // Read the image file and show it in the preview tag
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                // Reset if they cancel the selection
                uploadText.innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size: 24px; color: #94a3b8; margin-bottom: 10px; display: block;"></i> <?php echo isset($lang['prescription']) ? $lang['prescription'] : 'Upload Prescription (Image)'; ?>';
                imagePreview.style.display = 'none';
                imagePreview.src = '';
            }
        });
    </script>
</body>
</html>