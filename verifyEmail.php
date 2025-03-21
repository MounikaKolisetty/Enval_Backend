<?php
include 'connect.php';
header("Access-Control-Allow-Origin: https://enval.in"); // Replace with your frontend URL
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Allow POST and OPTIONS methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow necessary headers
header("Access-Control-Allow-Credentials: true"); // Allow credentials
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'www.enval.in',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

$headers = getallheaders();
$csrf_token = $headers['X-CSRF-Token'] ?? ($headers['X-Csrf-Token'] ?? ''); // Case handling

if (empty($csrf_token)) {
    error_log("CSRF Token Missing");
    echo json_encode(["message" => "Invalid CSRF token"]);
    http_response_code(403);
    exit();
}

session_start();
if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    error_log("CSRF Token Mismatch");
    echo json_encode(["message" => "Invalid CSRF token"]);
    http_response_code(403);
    exit();
}
// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';

if (!$token) {
    echo json_encode(["success" => false, "message" => "Invalid token."]);
    exit();
}

// Check if token exists in the database
$stmt = $conn->prepare("SELECT email, expiry FROM users WHERE token = ? AND isVerified = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $expiryTime = strtotime($row['expiry']);
    $currentTime = time();

    error_log('verify email' . $expiryTime . $currentTime);

    if ($currentTime > $expiryTime) {
        echo json_encode(["success" => false, "message" => "Expired token."]);
        exit();
    }
    // Update user as verified
    $stmt = $conn->prepare("UPDATE users SET isVerified = 1, token = NULL WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Email verified successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
}

$stmt->close();
$conn->close();
?>
