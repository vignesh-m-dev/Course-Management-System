<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
require_once 'db_helper.php';

// Check if user is admin
$userResult = $conn->query("SELECT is_admin FROM users WHERE user_id = " . $_SESSION['user_id']);
$user = $userResult->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: dashboard.php");
    exit;
}

// Get course ID from URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$courseId = intval($_GET['id']);
$result = $conn->query("SELECT * FROM courses WHERE id = $courseId");

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit;
}

$editCourse = $result->fetch_assoc();

// Handle update course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $deal = $conn->real_escape_string($_POST['deal']);
    $offer = $conn->real_escape_string($_POST['offer']);
    
    $updateResult = $conn->query("UPDATE courses SET title='$title', description='$description', price=$price, deal='$deal', offer='$offer' WHERE id=$courseId");
    
    if ($updateResult) {
        header("Location: dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Course</title>

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
    text-decoration: none;
    display: inline-block;
}

.btn-cancel:hover {
    background: #5a6268;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .container {
        padding: 10px;
    }

    .section-title {
        font-size: 20px;
    }
}
</style>
</head>

<body>

<!-- APP BAR -->
<div class="appbar">
    <div class="appbar-left">
        <div class="icon">Edit Course</div>
    </div>

    <div class="appbar-right">
        <a href="dashboard.php" class="icon">📊 Dashboard</a>
        <a href="auth.php?logout=1" class="icon">Logout</a>
    </div>
</div>

<!-- CONTAINER -->
<div class="container">
    <!-- EDIT COURSE FORM -->
    <div class="section">
        <div class="section-title">Edit Course</div>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="title">Course Title *</label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($editCourse['title']) ?>">
                </div>

                <div class="form-group">
                    <label for="description">Course Description *</label>
                    <textarea id="description" name="description" required><?= htmlspecialchars($editCourse['description']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (₹) *</label>
                        <input type="number" id="price" name="price" step="0.01" required value="<?= $editCourse['price'] ?>">
                    </div>

                    <div class="form-group">
                        <label for="deal">Deal (e.g., 20% OFF)</label>
                        <input type="text" id="deal" name="deal" value="<?= htmlspecialchars($editCourse['deal']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="offer">Offer (e.g., Free projects)</label>
                    <input type="text" id="offer" name="offer" value="<?= htmlspecialchars($editCourse['offer']) ?>">
                </div>

                <button type="submit" class="btn-submit">Update Course</button>
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
