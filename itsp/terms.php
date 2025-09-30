<?php
session_start();
require_once 'config.php';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Library Management System</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <style>
        .terms-container {
            background: #1f2122;
            border-radius: 23px;
            padding: 30px;
            margin-top: 100px;
            margin-bottom: 50px;
        }
        .terms-section {
            margin-bottom: 30px;
        }
        .terms-section h3 {
            color: #ec6090;
            margin-bottom: 20px;
        }
        .terms-section p {
            color: #fff;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .terms-section ul {
            color: #fff;
            padding-left: 20px;
            margin-bottom: 15px;
        }
        .terms-section li {
            margin-bottom: 10px;
        }
        .highlight {
            color: #ec6090;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="terms-container">
                    <h2 class="text-center mb-4">Terms and Conditions</h2>
                    
                    <div class="terms-section">
                        <h3>1. Book Borrowing Rules</h3>
                        <ul>
                            <li>Students must present a valid student ID when borrowing books.</li>
                            <li>Maximum of 3 books can be borrowed at a time.</li>
                            <li>Books must be returned within the specified due date.</li>
                            <li>Late returns will incur fines as per library policy.</li>
                            <li>Damaged or lost books must be reported immediately.</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3>2. eBook Access</h3>
                        <ul>
                            <li>eBooks are available for reading online only.</li>
                            <li>Downloading of eBooks is restricted to authorized users.</li>
                            <li>Sharing of eBook access credentials is prohibited.</li>
                            <li>eBook content is for personal use only.</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3>3. Schedule and Reservations</h3>
                        <ul>
                            <li>Book reservations must be made at least 24 hours in advance.</li>
                            <li>Reserved books must be collected within 24 hours of the scheduled time.</li>
                            <li>Cancellations must be made at least 12 hours before the scheduled time.</li>
                            <li>Repeated no-shows may result in borrowing privileges being suspended.</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3>4. User Responsibilities</h3>
                        <ul>
                            <li>Users are responsible for maintaining the confidentiality of their account.</li>
                            <li>Any unauthorized use of accounts must be reported immediately.</li>
                            <li>Users must keep their contact information updated.</li>
                            <li>Users are responsible for all activities under their account.</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3>5. Library Rules</h3>
                        <ul>
                            <li>Maintain silence in the library premises.</li>
                            <li>No food or drinks allowed in the library.</li>
                            <li>Mobile phones must be kept on silent mode.</li>
                            <li>Follow the instructions of library staff.</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3>6. Penalties and Fines</h3>
                        <ul>
                            <li>Late return fine: $1 per day per book.</li>
                            <li>Lost book replacement cost: Current market value of the book.</li>
                            <li>Damaged book fine: Up to 50% of the book's value.</li>
                            <li>Repeated violations may result in suspension of library privileges.</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3>7. Privacy Policy</h3>
                        <p>We respect your privacy and are committed to protecting your personal information. Your data is used only for library management purposes and is never shared with third parties without your consent.</p>
                    </div>

                    <div class="terms-section">
                        <h3>8. Changes to Terms</h3>
                        <p>The library reserves the right to modify these terms and conditions at any time. Users will be notified of any significant changes through the library's notification system.</p>
                    </div>

                    <div class="terms-section">
                        <h3>9. Contact Information</h3>
                        <p>For any questions or concerns regarding these terms and conditions, please contact the library staff or email us at <span class="highlight">library@bestlink.edu.ph</span></p>
                    </div>

                    <div class="text-center mt-4">
                        <p class="text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html> 