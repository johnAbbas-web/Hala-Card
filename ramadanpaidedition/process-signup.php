<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get form data
$name = $_POST['name'];
$email = $_POST['email'];

// Validate
if (empty($name) || empty($email)) {
    die("Please fill in all fields");
}

// YOUR STRIPE PAYMENT LINK
$stripe_payment_link = "https://buy.stripe.com/test_fZu00kfzgdju0sRfS15Vu00"; // REPLACE THIS!

// Store user data temporarily (we'll use session or a temp file)
session_start();
$_SESSION['pending_name'] = $name;
$_SESSION['pending_email'] = $email;

// Create a unique identifier for this signup
$temp_id = md5(time() . $name . $email);
$_SESSION['temp_id'] = $temp_id;

// Save temporarily (in case session is lost)
$temp_data = "$temp_id|$name|$email|" . time() . PHP_EOL;
file_put_contents(__DIR__ . '/pending-signups.txt', $temp_data, FILE_APPEND);

// Add the temp_id to the Stripe URL
$redirect_url = $stripe_payment_link . "?client_reference_id=" . $temp_id;

// Redirect to Stripe
header("Location: " . $redirect_url);
exit();
?>