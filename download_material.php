<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");

// Check if course_materials table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'course_materials'");
if ($tableCheckResult->num_rows === 0) {
    echo "Material not found";
    exit;
}

$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($material_id == 0) {
    echo "Invalid material ID";
    exit;
}

// Fetch material info
$result = $conn->query("SELECT * FROM course_materials WHERE material_id = $material_id");
if ($result->num_rows == 0) {
    echo "Material not found";
    exit;
}

$material = $result->fetch_assoc();
$file_path = $material['file_path'];

// Check if file exists
if (!file_exists($file_path)) {
    echo "File not found on server";
    exit;
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . mime_content_type($file_path));
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');

// Output file
readfile($file_path);
exit;
?>
