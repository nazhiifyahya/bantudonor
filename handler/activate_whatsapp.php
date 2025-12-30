<?php
session_start();
require_once __DIR__ . '/../models/User.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userModel = new User();

// Activate WhatsApp notification
$result = $userModel->update($_SESSION['user_id'], [
    'whatsapp_notification' => 'ya'
]);

if ($result) {
    $_SESSION['success_message'] = 'WhatsApp berhasil dihubungkan!';
} else {
    $_SESSION['error_messages'] = ['Gagal menghubungkan WhatsApp'];
}

// Redirect back to dashboard
header('Location: ../dashboard.php');
exit;
?>
