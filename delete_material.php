<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once 'db_helper.php';

$conn = new mysqli("localhost", "root", "", "23UCS105");

// Check if course_materials table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'course_materials'");
if ($tableCheckResult->num_rows === 0) {
    header("Location: upload_material.php");
    exit;
}

// Check if user is admin
$userResult = $conn->query("SELECT is_admin FROM users WHERE user_id = " . $_SESSION['user_id']);
$user = $userResult->fetch_assoc();
$isAdmin = $user && $user['is_admin'];

if (!$isAdmin) {
    header("Location: dashboard.php");
    exit;
}

$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($material_id == 0) {
    header("Location: upload_material.php");
    exit;
}

// Fetch material
$result = $conn->query("SELECT file_path FROM course_materials WHERE material_id = $material_id");
if ($result->num_rows == 0) {
    header("Location: upload_material.php");
    exit;
}

$material = $result->fetch_assoc();

// Delete file from server
if (file_exists($material['file_path'])) {
    unlink($material['file_path']);
}

// Delete from database
$conn->query("DELETE FROM course_materials WHERE material_id = $material_id");

header("Location: upload_material.php?deleted=1");
exit;
?>
