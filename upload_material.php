<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once 'db_helper.php';

$conn = new mysqli("localhost", "root", "", "23UCS105");

// Create course_materials table if it doesn't exist
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'course_materials'");
if ($tableCheckResult->num_rows === 0) {
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `course_materials` (
      `material_id` int(11) NOT NULL AUTO_INCREMENT,
      `course_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `description` text,
      `file_name` varchar(255) NOT NULL,
      `file_path` varchar(255) NOT NULL,
      `file_type` varchar(50) NOT NULL,
      `file_size` int(11) NOT NULL,
      `uploaded_date` datetime DEFAULT current_timestamp(),
      PRIMARY KEY (`material_id`),
      FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->query($createTableSQL);
    
    // Create uploads directories if they don't exist
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }
    if (!is_dir('uploads/materials')) {
        mkdir('uploads/materials', 0755, true);
    }
}

// Check if user is admin
$userResult = $conn->query("SELECT is_admin FROM users WHERE user_id = " . $_SESSION['user_id']);
$user = $userResult->fetch_assoc();
$isAdmin = $user && $user['is_admin'];

if (!$isAdmin) {
    header("Location: dashboard.php");
    exit;
}

// Handle file upload
$upload_message = '';
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['material_file'])) {
    $course_id = (int)$_POST['course_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $file = $_FILES['material_file'];

    // Validation
    if (empty($title)) {
        $upload_error = "Title is required";
    } elseif ($course_id == 0) {
        $upload_error = "Please select a course";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = "File upload error: " . $file['error'];
    } else {
        // Allowed file types
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $upload_error = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
        } elseif ($file['size'] > 100 * 1024 * 1024) { // 100 MB limit
            $upload_error = "File size must be less than 100 MB";
        } else {
            // Create unique filename
            $upload_dir = 'uploads/materials/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $unique_name = uniqid() . '_' . basename($file['name']);
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Insert into database
                $title = $conn->real_escape_string($title);
                $description = $conn->real_escape_string($description);
                
                $sql = "INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_type, file_size)
                        VALUES ($course_id, '$title', '$description', '{$file['name']}', '$file_path', '$file_ext', {$file['size']})";
                
                if ($conn->query($sql) === TRUE) {
                    $upload_message = "Material uploaded successfully!";
                } else {
                    $upload_error = "Database error: " . $conn->error;
                    unlink($file_path); // Delete file if DB insert failed
                }
            } else {
                $upload_error = "Failed to move uploaded file";
            }
        }
    }
}

// Fetch all courses
$courses = $conn->query("SELECT id, title FROM courses ORDER BY title");

// Fetch all materials
$materials = $conn->query("
    SELECT cm.material_id, cm.course_id, cm.title AS material_title, cm.file_name, cm.file_size, cm.uploaded_date, c.title AS course_title
    FROM course_materials cm
    JOIN courses c ON cm.course_id = c.id
    ORDER BY cm.uploaded_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Course Materials</title>

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

.container {
    max-width: 1200px;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

h2 {
    color: #333;
    margin-top: 0;
}

.message {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 5px;
}

.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.form-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

input[type="text"],
input[type="file"],
textarea,
select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: Arial;
    font-size: 14px;
}

textarea {
    min-height: 80px;
    resize: vertical;
}

button {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

button:hover {
    background: #0056b3;
}

.materials-list {
    margin-top: 30px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #007bff;
    color: white;
    padding: 12px;
    text-align: left;
}

td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

tr:hover {
    background: #f9fafb;
}

.action-btn {
    padding: 6px 12px;
    margin-right: 5px;
    font-size: 12px;
}

.delete-btn {
    background: #dc3545;
}

.delete-btn:hover {
    background: #c82333;
}

.download-btn {
    background: #28a745;
}

.download-btn:hover {
    background: #218838;
}

.preview-btn {
    background: #17a2b8;
}

.preview-btn:hover {
    background: #138496;
}

.file-size {
    font-size: 12px;
    color: #666;
}

a {
    text-decoration: none;
}
</style>
</head>

<body>

<div class="appbar">
    <h2 style="margin: 0;">Manage Course Materials</h2>
    <a href="admin.php" class="icon">← Admin Panel</a>
</div>

<div class="container">
    <?php if ($upload_message): ?>
        <div class="message success"><?= htmlspecialchars($upload_message) ?></div>
    <?php endif; ?>

    <?php if ($upload_error): ?>
        <div class="message error"><?= htmlspecialchars($upload_error) ?></div>
    <?php endif; ?>

    <div class="form-section">
        <h3>Upload New Material</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="course_id">Select Course *</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Choose a course --</option>
                    <?php while ($row = $courses->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="title">Material Title *</label>
                <input type="text" name="title" id="title" placeholder="e.g., Lecture 1 - Introduction" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" placeholder="Describe the material content..."></textarea>
            </div>

            <div class="form-group">
                <label for="material_file">Select File *</label>
                <input type="file" name="material_file" id="material_file" required>
                <small style="color: #666;">Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, JPG, PNG, GIF, MP4 (Max 100 MB)</small>
            </div>

            <button type="submit">Upload Material</button>
        </form>
    </div>

    <div class="materials-list">
        <h3>Existing Materials</h3>
        <?php if ($materials->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Material Title</th>
                        <th>File</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $materials->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['course_title']) ?></td>
                            <td><?= htmlspecialchars($row['material_title']) ?></td>
                            <td><?= htmlspecialchars($row['file_name']) ?></td>
                            <td><span class="file-size"><?= round($row['file_size'] / 1024 / 1024, 2) ?> MB</span></td>
                            <td><?= date('M d, Y', strtotime($row['uploaded_date'])) ?></td>
                            <td>
                                <a href="preview_material.php?id=<?= $row['material_id'] ?>">
                                    <button class="action-btn preview-btn">Preview</button>
                                </a>
                                <a href="download_material.php?id=<?= $row['material_id'] ?>">
                                    <button class="action-btn download-btn">Download</button>
                                </a>
                                <a href="delete_material.php?id=<?= $row['material_id'] ?>" onclick="return confirm('Are you sure you want to delete this material?')">
                                    <button class="action-btn delete-btn">Delete</button>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">No materials uploaded yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
