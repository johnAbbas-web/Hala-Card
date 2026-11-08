<?php
// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Get the session_id from Stripe
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : '';

// Get our temp_id (either from session or from URL if we passed it)
$temp_id = isset($_GET['temp_id']) ? $_GET['temp_id'] : (isset($_SESSION['temp_id']) ? $_SESSION['temp_id'] : '');

if (empty($temp_id)) {
    // Try to find it in pending-signups.txt by matching the most recent with same email
    $temp_id = $_SESSION['pending_temp_id'] ?? '';
}

$name = $_SESSION['pending_name'] ?? '';
$email = $_SESSION['pending_email'] ?? '';

// If no session data, try to retrieve from pending-signups.txt
if (empty($name) && !empty($temp_id)) {
    $lines = file(__DIR__ . '/pending-signups.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if ($parts[0] == $temp_id) {
            $name = $parts[1];
            $email = $parts[2];
            break;
        }
    }
}

if (empty($name) || empty($email)) {
    die("Missing user information. Please contact support.");
}

// Redirect to card generation with the user's details
header("Location: generate-card.php?name=" . urlencode($name) . "&email=" . urlencode($email) . "&paid=1&session_id=" . urlencode($session_id));
exit();
?>