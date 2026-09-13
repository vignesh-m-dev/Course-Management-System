<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");

// Create course_materials table if it doesn't exist
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'course_materials'");
if ($tableCheckResult->num_rows === 0) {
    header("Location: dashboard.php");
    exit;
}

$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($material_id == 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch material info
$result = $conn->query("SELECT * FROM course_materials WHERE material_id = $material_id");
if ($result->num_rows == 0) {
    header("Location: dashboard.php");
    exit;
}

$material = $result->fetch_assoc();
$file_path = $material['file_path'];
$file_type = strtolower($material['file_type']);

// Check if file exists
if (!file_exists($file_path)) {
    die("File not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Preview - <?= htmlspecialchars($material['title']) ?></title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f5f5;
}

.appbar {
    background: #007bff;
    color: white;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.icon {
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.preview-container {
    max-width: 1000px;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.preview-header {
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
}

.preview-title {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
    color: #333;
}

.preview-buttons {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

button {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.back-btn {
    background: #6c757d;
    color: white;
}

.download-btn {
    background: #28a745;
    color: white;
}

.download-btn:hover {
    background: #218838;
}

.preview-viewer {
    margin-top: 20px;
}

.pdf-viewer {
    width: 100%;
    height: 600px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.image-viewer {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
}

.text-viewer {
    background: #f9fafb;
    padding: 15px;
    border-radius: 5px;
    font-family: 'Courier New', monospace;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #ddd;
}

.unsupported {
    background: #fff3cd;
    padding: 20px;
    border-radius: 5px;
    text-align: center;
    color: #856404;
}

a {
    text-decoration: none;
}
</style>
</head>

<body>

<div class="appbar">
    <h2 style="margin: 0;">Preview Material</h2>
    <a href="javascript:history.back()" class="icon">← Back</a>
</div>

<div class="preview-container">
    <div class="preview-header">
        <h2 class="preview-title"><?= htmlspecialchars($material['title']) ?></h2>
        <p style="margin: 5px 0; color: #666;"><?= htmlspecialchars($material['description']) ?></p>
        
        <div class="preview-buttons">
            <button class="back-btn" onclick="history.back()">← Back</button>
            <a href="download_material.php?id=<?= $material_id ?>">
                <button class="download-btn">Download</button>
            </a>
        </div>
    </div>

    <div class="preview-viewer">
        <?php if ($file_type === 'pdf'): ?>
            <embed src="<?= htmlspecialchars($file_path) ?>" type="application/pdf" class="pdf-viewer">
        
        <?php elseif (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
            <img src="<?= htmlspecialchars($file_path) ?>" class="image-viewer" alt="<?= htmlspecialchars($material['title']) ?>">
        
        <?php elseif (in_array($file_type, ['txt', 'log', 'csv'])): ?>
            <div class="text-viewer"><?= htmlspecialchars(file_get_contents($file_path)) ?></div>
        
        <?php else: ?>
            <div class="unsupported">
                <h3>Preview not available for this file type</h3>
                <p>File type: <?= htmlspecialchars($file_type) ?></p>
                <a href="download_material.php?id=<?= $material_id ?>">
                    <button class="download-btn">Download File</button>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
