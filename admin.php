<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
require_once 'db_helper.php';

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

if (!$user || !$user['is_admin']) {
    header("Location: dashboard.php");
    exit;
}

// Handle delete course
if (isset($_GET['delete'])) {
    $courseId = intval($_GET['delete']);
    deleteAndResetAutoIncrement($conn, 'courses', 'id', $courseId);
    header("Location: admin.php");
    exit;
}

// Handle add/edit course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $deal = $conn->real_escape_string($_POST['deal']);
    $offer = $conn->real_escape_string($_POST['offer']);
    $courseId = null;
    
    if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
        // Update existing course
        $courseId = intval($_POST['course_id']);
        $conn->query("UPDATE courses SET title='$title', description='$description', price=$price, deal='$deal', offer='$offer' WHERE id=$courseId");
    } else {
        // Add new course and get the ID
        $conn->query("INSERT INTO courses (title, description, price, deal, offer) VALUES ('$title', '$description', $price, '$deal', '$offer')");
        $courseId = $conn->insert_id;
    }
    
    // Handle material file upload for new courses
    if (!isset($_POST['course_id']) && isset($_FILES['course_material']) && $_FILES['course_material']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['course_material'];
        $material_name = trim($_POST['material_name'] ?? 'Course Material');
        $material_desc = trim($_POST['material_description'] ?? '');
        
        // Allowed file types
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_types) && $file['size'] <= 100 * 1024 * 1024) {
            // Create uploads directory if not exists
            $upload_dir = 'uploads/materials/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $unique_name = uniqid() . '_' . basename($file['name']);
            $file_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Check if course_materials table exists before inserting
                $tableCheck = $conn->query("SHOW TABLES LIKE 'course_materials'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    // Insert material into database
                    $material_name = $conn->real_escape_string($material_name);
                    $material_desc = $conn->real_escape_string($material_desc);
                    
                    $insertResult = $conn->query("INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_type, file_size)
                                VALUES ($courseId, '$material_name', '$material_desc', '{$file['name']}', '$file_path', '$file_ext', {$file['size']})");
                    
                    if (!$insertResult) {
                        // If insert fails, still keep the file but log the issue
                        error_log("Failed to save material to database: " . $conn->error);
                    }
                }
            }
        }
    }
    
    header("Location: admin.php");
    exit;
}

// Get course data for editing
$editCourse = null;
if (isset($_GET['edit'])) {
    $courseId = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM courses WHERE id = $courseId");
    $editCourse = $result->fetch_assoc();
}

// Get all courses
$courseData = $conn->query("SELECT * FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - Course Management</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial;
    background: #f5f5f5;
}

/* APP BAR */
.appbar {
    background: #dc3545;
    color: white;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.appbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.appbar-right {
    display: flex;
    align-items: center;
    gap: 18px;
}

.icon {
    cursor: pointer;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

/* CONTAINER */
.container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
}

/* FORM STYLES */
.form-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
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

input, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: Arial;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn-submit {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
}

.btn-submit:hover {
    background: #218838;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    margin-left: 10px;
}

.btn-cancel:hover {
    background: #5a6268;
}

/* FILE INPUT STYLING */
input[type="file"] {
    display: block;
    width: 100%;
    padding: 10px;
    border: 2px dashed #007bff;
    border-radius: 5px;
    background: white;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

input[type="file"]:hover {
    background: #f0f8ff;
    border-color: #0056b3;
}

input[type="file"]::file-selector-button {
    background: #007bff;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-weight: bold;
    margin-right: 10px;
}

input[type="file"]::file-selector-button:hover {
    background: #0056b3;
}

/* COURSE TABLE */
.course-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.course-table th {
    background: #dc3545;
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: bold;
}

.course-table td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

.course-table tr:hover {
    background: #f9f9f9;
}

.course-table .title {
    font-weight: bold;
    color: #333;
}

.course-table .price {
    color: green;
    font-weight: bold;
}

.course-table .actions {
    display: flex;
    gap: 8px;
}

.btn-edit {
    background: #007bff;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
}

.btn-edit:hover {
    background: #0056b3;
}

.btn-delete {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}

.btn-delete:hover {
    background: #c82333;
}

.btn-add {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    margin-bottom: 20px;
}

.btn-add:hover {
    background: #218838;
}

.alert {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.no-data {
    text-align: center;
    padding: 20px;
    color: #666;
}
</style>
</head>

<body>

<!-- APP BAR -->
<div class="appbar">
    <div class="appbar-left">
        <div class="icon">Admin Panel</div>
    </div>

    <div class="appbar-right">
        <a href="upload_material.php" class="icon">Manage Materials</a>
        <a href="dashboard.php" class="icon">📊 Dashboard</a>
        <a href="auth.php?logout=1" class="icon">Logout</a>
    </div>
</div>

<!-- CONTAINER -->
<div class="container">
    <!-- ADD/EDIT COURSE FORM -->
    <div class="section">
        <div class="section-title">
            <?php echo isset($editCourse) ? "Edit Course" : "Add New Course"; ?>
        </div>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <?php if (isset($editCourse)): ?>
                    <input type="hidden" name="course_id" value="<?= $editCourse['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Course Title *</label>
                    <input type="text" id="title" name="title" required value="<?= isset($editCourse) ? htmlspecialchars($editCourse['title']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="description">Course Description *</label>
                    <textarea id="description" name="description" required><?= isset($editCourse) ? htmlspecialchars($editCourse['description']) : '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (₹) *</label>
                        <input type="number" id="price" name="price" step="0.01" required value="<?= isset($editCourse) ? $editCourse['price'] : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="deal">Deal (e.g., 20% OFF)</label>
                        <input type="text" id="deal" name="deal" value="<?= isset($editCourse) ? htmlspecialchars($editCourse['deal']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="offer">Offer (e.g., Free projects)</label>
                    <input type="text" id="offer" name="offer" value="<?= isset($editCourse) ? htmlspecialchars($editCourse['offer']) : '' ?>">
                </div>

                <!-- FILE UPLOAD SECTION - ONLY FOR NEW COURSES -->
                <?php if (!isset($editCourse)): ?>
                    <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #2196F3;">
                        <h4 style="margin-top: 0; color: #1976D2;">Course Material (Optional)</h4>
                        <p style="margin: 5px 0; font-size: 13px; color: #555;">Upload a course material file to be immediately available for students</p>
                        
                        <div class="form-group">
                            <label for="material_name">Material Name</label>
                            <input type="text" id="material_name" name="material_name" placeholder="e.g., Course Introduction Video">
                        </div>

                        <div class="form-group">
                            <label for="material_description">Material Description</label>
                            <textarea id="material_description" name="material_description" placeholder="Describe the course material..." style="min-height: 60px;"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="course_material">Select File</label>
                            <input type="file" id="course_material" name="course_material">
                            <small style="display: block; margin-top: 5px; color: #666;">Supported: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, JPG, PNG, GIF, MP4, AVI, MOV (Max 100 MB)</small>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-submit">
                    <?php echo isset($editCourse) ? "Update Course" : "Add Course"; ?>
                </button>

                <?php if (isset($editCourse)): ?>
                    <a href="admin.php" class="btn-cancel">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- COURSES LIST -->
    <!-- <div class="section">
        <div class="section-title">All Courses</div>

        <?php if ($courseData->num_rows > 0): ?>
            <table class="course-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Deal</th>
                        <th>Offer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $courseData->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td class="title"><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars(substr($row['description'], 0, 50)) ?>...</td>
                            <td class="price">₹<?= $row['price'] ?></td>
                            <td><?= $row['deal'] ? htmlspecialchars($row['deal']) : '-' ?></td>
                            <td><?= $row['offer'] ? htmlspecialchars($row['offer']) : '-' ?></td>
                            <td class="actions">
                                <a href="admin.php?edit=<?= $row['id'] ?>" class="btn-edit">✏️ Edit</a>
                                <button onclick="deleteConfirm(<?= $row['id'] ?>)" class="btn-delete">🗑️ Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No courses found. <a href="admin.php">Add the first course</a></div>
        <?php endif; ?>
    </div> -->
</div>

<script>
function deleteConfirm(courseId) {
    if (confirm('Are you sure you want to delete this course?')) {
        window.location.href = 'admin.php?delete=' + courseId;
    }
}
</script>

</body>
</html>
