<?php
// Allow CORS
//header("Access-Control-Allow-Origin: http://localhost:4200"); // Replace with your frontend URL
header("Access-Control-Allow-Origin: https://enval.in"); // Replace with your frontend URL
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Allow POST and OPTIONS methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow necessary headers
header("Access-Control-Allow-Credentials: true"); // Allow credentials

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Respond OK to preflight request
    exit();
}

// Get the raw POST data
$rawData = file_get_contents("php://input");

// Decode the JSON data
$data = json_decode($rawData, true);

if ($data) {
    // Sanitize and retrieve form data
    $name = isset($data['name']) ? htmlspecialchars($data['name']) : '';
    $designation = isset($data['designation']) ? htmlspecialchars($data['designation']) : '';
    $organization = isset($data['organization']) ? htmlspecialchars($data['organization']) : '';
    $location = isset($data['location']) ? htmlspecialchars($data['location']) : '';
    $email = isset($data['email']) ? htmlspecialchars($data['email']) : '';
    $mobile = isset($data['mobile']) ? htmlspecialchars($data['mobile']) : '';

    // Validate required fields
    if (empty($name) || empty($designation) || empty($organization) || empty($location) || empty($email) || empty($mobile)) {
        echo json_encode([
            "success" => false,
            "message" => "All fields are required."
        ]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid email format."
        ]);
        exit();
    }

    // Email details
    $to = $email;
    $subject = "New Form Submission";
    $headers = "From: enval.connect@gmail.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Prepare message
    $fullMessage = "Name: $name\nDesignation: $designation\nOrganization: $organization\nLocation: $location\nNo. of Trainees: $trainees\nMobile: $mobile\nEmail: $email\n";

    // Send email using mail() function
    if (mail($to, $subject, $fullMessage, $headers)) {
        echo json_encode([
            "success" => true,
            "message" => "Email successfully sent!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to send email."
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data."
    ]);
}
?>
