<?php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable CORS
header("Access-Control-Allow-Origin: *"); // Replace with your frontend URL
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Allow POST and OPTIONS methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow necessary headers
header("Access-Control-Allow-Credentials: true"); // Allow credentials

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Respond OK to preflight request
    exit();
}

// Include the database connection
require 'connect.php'; // Ensure this file creates the $conn object

// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to generate a random token
function generateResetToken() {
    return bin2hex(random_bytes(16));
}

// Function to store the token
function storeResetToken($userId, $token, $expiration) {
    global $conn; // Declare $conn as global
    $query = "INSERT INTO passwordresettokens (user_id, token, expiration) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $userId, $token, $expiration);
    $stmt->execute();
    $stmt->close();
}

// Function to get user by email
function getUserByEmail($email) {
    global $conn; // Declare $conn as global
    $query = "SELECT id, email FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// Function to send the password reset email
function sendPasswordResetEmail($email, $resetLink) {
    $subject = "Password Reset";
    $body = "
    Hi,
    Please click on the following link to reset your password:
    $resetLink
    This link will expire in 24 hours.";

    $headers = "From: mouni.kolisetty@gmail.com";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, $subject, $body, $headers);
}

// Handle the password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? null;

    if (!$email) {
        echo json_encode(['message' => 'Email is required.']);
        exit;
    }

    $user = getUserByEmail($email);
    if (!$user) {
        echo json_encode(['message' => 'User not found.']);
        exit;
    }

    $resetToken = generateResetToken();
    $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
    storeResetToken($user['id'], $resetToken, $expiration);

    $resetLink = "http://localhost:4200/password-reset?token={$resetToken}";
    sendPasswordResetEmail($user['email'], $resetLink);

    echo json_encode(['message' => 'Password reset email sent.']);
    exit;
}

?>
