<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php'; // Database connection

$responseMessage = '';
$showResponse = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requiredFields = ['student-number', 'name', 'email', 'password', 'confirm-password'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $responseMessage = 'All fields are required.';
            $showResponse = true;
            break;
        }
    }

    if (!$showResponse && $_POST['password'] !== $_POST['confirm-password']) {
        $responseMessage = 'Passwords do not match.';
        $showResponse = true;
    }

    if (!$showResponse && (!isset($_FILES['id-upload']) || !isset($_FILES['selfie-upload']))) {
        $responseMessage = 'Both ID and selfie images are required.';
        $showResponse = true;
    }

    if (!$showResponse) {
        $api_key = "qlZW56fHggfx_B62eFndwAdyIK_jp3zj";
        $api_secret = "1DnNpahmwChj6Kz0vqzaOKnjhyJjmhpg";

        $idImage = $_FILES['id-upload']['tmp_name'];
        $selfieImage = $_FILES['selfie-upload']['tmp_name'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-us.faceplusplus.com/facepp/v3/compare");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'image_file1' => new CURLFile($idImage),
            'image_file2' => new CURLFile($selfieImage),
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $responseMessage = 'Face++ API error: ' . curl_error($ch);
            $showResponse = true;
        } else {
            $result = json_decode($response, true);
            if (isset($result['error_message'])) {
                $responseMessage = 'Face++ Error: ' . $result['error_message'];
                $showResponse = true;
            } else {
                $confidence = $result['confidence'] ?? 0;
                $threshold = 70;

                if ($confidence >= $threshold) {
                    $studentID = (int)$_POST['student-number'];
                    $name = $_POST['name'];
                    $email = $_POST['email'];
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

                    $uploadDir = 'C:/htdocs/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = 'id_' . time() . '_' . basename($_FILES['selfie-upload']['name']);
                    $targetPath = $uploadDir . $fileName;

                    if (!move_uploaded_file($_FILES['selfie-upload']['tmp_name'], $targetPath)) {
                        $responseMessage = 'Failed to move uploaded file.';
                        $showResponse = true;
                    } else {
                        $stmt = $conn->prepare("INSERT INTO users (student_id, password, email, full_name, profile_pic, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("issss", $studentID, $password, $email, $name, $targetPath);

                        if ($stmt->execute()) {
                            $_SESSION['registration_success'] = '✅ Registration complete. You can now log in.';
                            header('Location: login.php');
                            exit();
                        } else {
                            $responseMessage = 'Database error: ' . $stmt->error;
                            $showResponse = true;
                        }

                        $stmt->close();
                    }
                } else {
                    $responseMessage = '❌ Face verification failed. Please try again with clearer photos.';
                    $showResponse = true;
                }
            }
        }
        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="upload-container">
        <img src="img/images-removebg-preview.png" alt="Logo">
        <h2>Register Account</h2>

        <div id="error-message" class="error-message <?= $showResponse ? 'show' : ''; ?>">
            <?= $responseMessage ?>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" id="registration-form">
            <div class="input-group">
                <label for="student-number">Student ID *</label>
                <input type="number" id="student-number" name="student-number" required>
            </div>
            <div class="input-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="input-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
                <div id="password-strength-message" style="font-size: 0.9em; color: #555; margin-top: 5px;"></div>
            </div>
            <div class="input-group">
    <label for="confirm-password">Confirm Password *</label>
    <input type="password" id="confirm-password" name="confirm-password" required>
    <div id="password-match-message" style="font-size: 0.9em; margin-top: 5px;"></div> <!-- This will show the password match message -->
</div>


            <h3>Identification Verification</h3>
            <div class="input-group">
                <label for="id-upload">Upload Student ID *</label>
                <input type="file" id="id-upload" name="id-upload" accept="image/*" capture="environment" required>
                <img id="id-preview" src="#" alt="ID Preview" style="display:none; max-width: 100%; margin-top: 10px;">
            </div>
            <div class="input-group">
                <label for="selfie-upload">Upload Selfie with ID *</label>
                <input type="file" id="selfie-upload" name="selfie-upload" accept="image/*" capture="user" required>
                <img id="selfie-preview" src="#" alt="Selfie Preview" style="display:none; max-width: 100%; margin-top: 10px;">
            </div>

            <div class="privacy-agreement">
                <input type="checkbox" id="privacy" required>
                <label for="privacy">I agree to the <a href="javascript:void(0);" onclick="togglePrivacyPolicy()">Privacy Policy and User Agreement</a>.</label>
            </div>
            <button class="upload-btn" type="submit">Submit</button>

            <div style="margin-top: 20px; text-align: center;">
                <a href="login.php" style="text-decoration: none; color: #007bff;">← Back to Login</a>
            </div>
        </form>

        <div class="privacy-policy-text" id="privacy-policy-text">
            <p>Your privacy is important to us. By agreeing, you consent to our data policy for verification.</p>
            <a href="javascript:void(0);" onclick="toggleMorePrivacyText()">Read More...</a>
            <div class="extra-privacy-text" id="more-privacy-text">
                <p>If you damage any borrowed books, you must pay for replacements.</p>
                <p>Your data will not be shared with third parties without consent.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePrivacyPolicy() {
            var policy = document.getElementById("privacy-policy-text");
            policy.style.display = policy.style.display === "block" ? "none" : "block";
        }

        function toggleMorePrivacyText() {
            var moreText = document.getElementById("more-privacy-text");
            moreText.style.display = moreText.style.display === "block" ? "none" : "block";
        }

        document.getElementById('registration-form').addEventListener('submit', function(e) {
    const pw = document.getElementById('password').value;
    const cpw = document.getElementById('confirm-password').value;
    
    // Check if passwords match
    if (pw !== cpw) {
        e.preventDefault();  // Prevent form submission
        const errorMsg = document.getElementById('password-match-message');
        errorMsg.textContent = 'Passwords do not match.';
        errorMsg.style.color = 'red';
        errorMsg.style.fontSize = '0.9em';
    }
});

// Check password match while user types, with delay
let typingTimer; // Timer identifier
const delay = 1000; // Delay in milliseconds (1 second)

document.getElementById('confirm-password').addEventListener('input', function() {
    const pw = document.getElementById('password').value;
    const cpw = this.value;
    
    // Clear the previous timer (if any) to prevent multiple checks
    clearTimeout(typingTimer);

    // Set a new timer to check after the specified delay
    typingTimer = setTimeout(function() {
        const messageEl = document.getElementById('password-match-message');
        
        if (pw !== cpw) {
            messageEl.textContent = 'Passwords do not match.';
            messageEl.style.color = 'red';  // Red color for mismatch
        } else {
            messageEl.textContent = 'Passwords match!';
            messageEl.style.color = 'green';  // Green color for match
        }
    }, delay); // Run after the specified delay
});


        // Image preview
        document.getElementById('id-upload').addEventListener('change', function (event) {
            previewImage(event.target, document.getElementById('id-preview'));
        });
        document.getElementById('selfie-upload').addEventListener('change', function (event) {
            previewImage(event.target, document.getElementById('selfie-preview'));
        });

        function previewImage(input, previewElement) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        // Password strength suggestion
        document.getElementById('password').addEventListener('input', function () {
            const pw = this.value;
            const messageEl = document.getElementById('password-strength-message');

            let messages = [];
            if (pw.length < 8) messages.push('Use at least 8 characters');
            if (!/[A-Z]/.test(pw)) messages.push('Add an uppercase letter');
            if (!/[a-z]/.test(pw)) messages.push('Add a lowercase letter');
            if (!/[0-9]/.test(pw)) messages.push('Include a number');
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(pw)) messages.push('Add a special character');

            if (pw.length === 0) {
                messageEl.textContent = '';
            } else if (messages.length === 0) {
                messageEl.textContent = '✅ Strong password!';
                messageEl.style.color = 'green';
            } else {
                messageEl.innerHTML = '⚠️ ' + messages.join(', ');
                messageEl.style.color = 'orange';
            }
        });
    </script>
</body>
</html>
