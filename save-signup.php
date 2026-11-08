<?php
/**
 * save-signup.php
 * Appends every waitlist signup to signups.txt in this same folder.
 * Upload this file to the same directory as index-launch.html on Hostinger.
 * Open signups.txt any time (via your Hostinger File Manager, or FTP) to see every entry.
 */

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Strip newlines/carriage returns so one entry can never break into multiple lines
function clean_field($value) {
    $value = trim((string) ($value ?? ''));
    $value = str_replace(["\r", "\n"], ' ', $value);
    return $value;
}

$name    = clean_field($_POST['name'] ?? '');
$email   = clean_field($_POST['email'] ?? '');
$country = clean_field($_POST['country'] ?? '');
$consent = clean_field($_POST['consent'] ?? '');

// Basic required-field validation
if ($name === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
    exit;
}

// We only email people who ticked the (never pre-ticked) consent box — see privacy-policy.html
if ($consent !== 'yes') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please tick the box to agree to receive updates before joining.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Build one line per entry — the consent time is our record of when they agreed
$timestamp = date('Y-m-d H:i:s');
$countryDisplay = $country !== '' ? $country : '(not provided)';
$line = "Name: {$name} | Email: {$email} | Country: {$countryDisplay} | Consent: yes ({$timestamp}) | Submitted: {$timestamp}" . PHP_EOL;

// Append to signups.txt in this same folder (created automatically on first signup)
$file = __DIR__ . '/signups.txt';
$result = @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save your signup. Please try again.']);
    exit;
}

echo json_encode(['success' => true]);