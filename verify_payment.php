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

require 'index.php';

header('Content-Type: application/json');

try {
    $postData = json_decode(file_get_contents('php://input'), true);
    if (!$postData) {
        throw new Exception('Invalid input data');
    }

    $payment_id = $postData['razorpay_payment_id'];
    $order_id = $postData['razorpay_order_id'];
    $signature = $postData['razorpay_signature'];
    $courseTitle = $postData['course_title'];
    $userEmail = $postData['user_email'];

    if (!$payment_id || !$order_id || !$signature) {
        throw new Exception('Missing payment verification data');
    }

    $attributes = [
        'razorpay_order_id' => $order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ];

    $api->utility->verifyPaymentSignature($attributes);

    // Payment is successful, you can update your database or perform other actions
    $to = $userEmail; // Replace with recipient's 
    $subject = "Your Purchase of $courseTitle is Successful!"; 
    $headers = "From: enval.connect@gmail.com\r\n"; 
    $headers .= "Reply-To: enval.connect@gmail.com\r\n"; 
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
    $message = " 
    <html> 
    <head> 
        <title>Purchase Confirmation</title> 
    </head> 
    <body> 
        <h1>Congratulations!</h1> 
        <p>Your purchase of the course titled <strong>$courseTitle</strong> was successful.</p> 
        <p>Order ID: $order_id</p> 
        <p>If you have any questions, feel free to contact us.</p> 
    </body> 
    </html> "; 
    if (mail($to, $subject, $message, $headers)) { 
        echo json_encode(['status' => 'success', 'message' => 'Payment was successful and email sent!']); } 
        else { throw new Exception('Failed to send email.'); }
} catch (Exception $e) {
    // Payment failed or was tampered with
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Payment verification failed: ' . $e->getMessage()]);
}
?>
