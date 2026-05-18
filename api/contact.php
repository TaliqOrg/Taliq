<?php
/*
 * Task 11: Contact Us API Endpoint
 * Author:  Fadhlallah Almohammed
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/ContactMessage.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input   = json_decode(file_get_contents('php://input'), true);
$name    = trim($input['name']    ?? '');
$email   = trim($input['email']   ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

if (empty($email) || empty($subject) || empty($message)) {
    json_response(['success' => false, 'message' => 'Email, subject, and message are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Please enter a valid email address'], 400);
}

if (strlen($message) < 10) {
    json_response(['success' => false, 'message' => 'Message must be at least 10 characters'], 400);
}

$model = new ContactMessage();
$saved = $model->save($name, $email, $subject, $message);

if ($saved) {
    json_response(['success' => true, 'message' => 'Your message has been sent. We\'ll get back to you soon!']);
} else {
    json_response(['success' => false, 'message' => 'Failed to send message. Please try again.'], 500);
}
