<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get form data
$name = $_POST['name'];
$email = $_POST['email'];
$card_image_data = $_POST['card_image']; // This is the base64 image
$card_number = $_POST['card_number'];

// Validate data
if (empty($name) || empty($email)) {
    die("Please fill in all fields");
}

// 1. SAVE TO TEXT FILE
$timestamp = date('Y-m-d H:i:s');
$ip_address = $_SERVER['REMOTE_ADDR'];
$log_entry = "Timestamp: $timestamp | Name: $name | Email: $email | Card: $card_number | IP: $ip_address" . PHP_EOL;
$file_path = __DIR__ . '/data/signups.txt';

if (file_put_contents($file_path, $log_entry, FILE_APPEND | LOCK_EX)) {
    $file_saved = true;
} else {
    $file_saved = false;
}

// 2. SAVE THE CARD IMAGE
$card_filename = 'card-' . time() . '-' . preg_replace('/[^a-zA-Z0-9]/', '', $name) . '.png';
$card_filepath = __DIR__ . '/cards/' . $card_filename;

// Save the base64 image to file
if (!empty($card_image_data)) {
    // Remove the data:image/png;base64 part
    $image_data = str_replace('data:image/png;base64,', '', $card_image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $image_data = base64_decode($image_data);
    
    if (file_put_contents($card_filepath, $image_data)) {
        $card_saved = true;
    } else {
        $card_saved = false;
        // Fallback to default image
        $card_filepath = __DIR__ . '/card-template.jpg';
    }
} else {
    $card_saved = false;
    $card_filepath = __DIR__ . '/card-template.jpg';
}

// 3. SEND EMAIL
$from_email = "info@halacard.co"; // CHANGE THIS
$from_name = "Hala Card";
$to = $email;
$subject = "Your Personalized Card - Thank You for Signing Up!";

// HTML email body
$message = "
<html>
<head>
    <title>Your Personalized Card</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        h1 { margin: 0; font-size: 24px; }
        .card-details { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .card-number { font-family: monospace; font-size: 18px; color: #667eea; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Welcome, $name!</h1>
        </div>
        <div class='content'>
            <p>Good day, <strong>$name</strong>!</p>
            <p>Thank you for signing up! Your personalized card is attached to this email.</p>
            
            <div class='card-details'>
                <p><strong>Your Card Number:</strong></p>
                <p class='card-number'>$card_number</p>
            </div>
            
            <p>Keep this card for your records. You'll need it for future reference.</p>
            
            <p>Best regards,<br>The Team</p>
        </div>
        <div class='footer'>
            <p>© 2024 Your Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
";

// Plain text version
$plain_message = "Good day, $name!\n\nThank you for signing up! Your personalized card is attached to this email.\n\nYour Card Number: $card_number\n\nKeep this card for your records.\n\nBest regards,\nThe Team";

// Headers and attachment
$boundary = md5(time());
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "From: $from_name <$from_email>" . "\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"" . "\r\n";

// Read the card file
if (file_exists($card_filepath)) {
    $file_content = file_get_contents($card_filepath);
    $file_content = chunk_split(base64_encode($file_content));
    
    // Determine file type
    $file_type = ($card_saved) ? 'image/png' : 'image/jpeg';
    $file_name = ($card_saved) ? 'Your-Personalized-Card.png' : 'Your-Card.jpg';
    
    // Message with attachment
    $message_body = "--$boundary\r\n";
    $message_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message_body .= $message . "\r\n\r\n";
    $message_body .= "--$boundary\r\n";
    $message_body .= "Content-Type: $file_type; name=\"$file_name\"\r\n";
    $message_body .= "Content-Transfer-Encoding: base64\r\n";
    $message_body .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n\r\n";
    $message_body .= $file_content . "\r\n\r\n";
    $message_body .= "--$boundary--";
    
    $mail_sent = mail($to, $subject, $message_body, $headers);
    
    // Delete the temporary card file (optional - keep if you want to store them)
    // unlink($card_filepath);
} else {
    $mail_sent = false;
}

// 4. SHOW RESULT
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signup Complete</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; padding: 20px; }
        .result-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; width: 100%; text-align: center; }
        h2 { color: #333; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; margin-bottom: 15px; }
        .success-icon { color: #4CAF50; font-size: 60px; margin-bottom: 20px; }
        .card-details { background: #f0f0f0; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .card-number { font-size: 24px; color: #667eea; font-family: monospace; letter-spacing: 2px; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="result-box">
        <div class="success-icon">✓</div>
        <h2>Thank you, <?php echo htmlspecialchars($name); ?>!</h2>
        <p>Your personalized card has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
        
        <div class="card-details">
            <p><strong>Your Card Number:</strong></p>
            <div class="card-number"><?php echo $card_number; ?></div>
        </div>
        
        <p>Please check your inbox (and spam folder) for the email with your attached card.</p>
        <a href="index.html" class="button">Back to Signup</a>
    </div>
</body>
</html>