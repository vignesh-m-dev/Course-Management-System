<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'db_helper.php';
$conn = new mysqli("localhost", "root", "", "23UCS105");

// Check if user is admin
$userResult = $conn->query("SELECT is_admin FROM users WHERE user_id = " . $_SESSION['user_id']);
$user = $userResult->fetch_assoc();

if (!$user || !$user['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = intval($_POST['course_id']);
    
    // Delete the course using the helper function
    if (deleteAndResetAutoIncrement($conn, 'courses', 'id', $courseId)) {
        $response = ['success' => true, 'message' => 'Course deleted successfully'];
    } else {
        $response = ['success' => false, 'message' => 'Failed to delete course'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
