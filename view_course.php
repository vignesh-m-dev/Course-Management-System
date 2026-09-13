<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once 'db_helper.php';

$conn = new mysqli("localhost", "root", "", "23UCS105");
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id == 0) {
    header("Location: dashboard.php");
    exit;
}

$result = $conn->query("SELECT * FROM courses WHERE id = $course_id");

if ($result->num_rows == 0) {
    header("Location: dashboard.php");
    exit;
}

$course = $result->fetch_assoc();
$discountedPrice = getDiscountedPrice($course['price'], $course['deal']);
$discountPercent = getDiscountPercent($course['deal']);

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
}

// Fetch course materials for ALL courses (not just purchased)
$materials = $conn->query("
    SELECT * FROM course_materials 
    WHERE course_id = $course_id 
    ORDER BY uploaded_date DESC
");

// Debug: Check if materials query was successful
if (!$materials) {
    // If query fails, set empty result
    error_log("Materials query error for course $course_id: " . $conn->error);
    $materials = null;
}

// Check if user has purchased this course
$userPurchased = false;
$purchaseCheck = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = " . $_SESSION['user_id'] . " AND course_id = $course_id");
if ($purchaseCheck) {
    $purchaseRow = $purchaseCheck->fetch_assoc();
    $userPurchased = $purchaseRow['count'] > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $course['title'] ?> - Course Details</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f5f5;
}

/* APP BAR */
.appbar {
    background: #007bff;
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

.back-btn {
    background: #0056b3;
    padding: 8px 15px;
    border-radius: 5px;
    font-size: 14px;
}

/* COURSE DETAILS */
.container {
    max-width: 900px;
    margin: 30px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
    font-size: 16px;
}

.back-link:hover {
    text-decoration: underline;
}

.course-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.course-title {
    font-size: 32px;
    font-weight: bold;
    margin: 0 0 15px 0;
    color: #333;
}

.course-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.price-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.original-price {
    font-size: 18px;
    color: #999;
    text-decoration: line-through;
}

.discounted-price {
    font-size: 28px;
    font-weight: bold;
    color: #d35400;
}

.discount-badge {
    display: inline-block;
    background: #d35400;
    color: white;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;
}

.deal-info {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.deal-tag {
    background: #fff3cd;
    color: #d35400;
    padding: 8px 15px;
    border-radius: 5px;
    font-weight: bold;
    font-size: 14px;
}

.offer-tag {
    background: #e8d5f2;
    color: #8e44ad;
    padding: 8px 15px;
    border-radius: 5px;
    font-weight: bold;
    font-size: 14px;
}

.course-content {
    margin-bottom: 30px;
}

.course-section-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin: 20px 0 10px 0;
}

.course-description {
    font-size: 16px;
    line-height: 1.6;
    color: #555;
    background: #f9fafb;
    padding: 15px;
    border-left: 4px solid #007bff;
    border-radius: 5px;
}

.course-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.detail-item {
    padding: 15px;
    background: #f9fafb;
    border-radius: 5px;
    border: 1px solid #e0e0e0;
}

.detail-label {
    font-weight: bold;
    color: #007bff;
    margin-bottom: 5px;
}

.detail-value {
    color: #555;
    font-size: 16px;
}

.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 30px;
}

button {
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.add-to-cart-btn {
    background: #007bff;
    color: white;
    flex: 1;
}

.add-to-cart-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,86,179,0.3);
}

.wishlist-btn {
    background: #ff4d4d;
    color: white;
    flex: 0.5;
}

.wishlist-btn:hover {
    background: #e63939;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255,77,77,0.3);
}

.payment-btn {
    background: #28a745;
    color: white;
    flex: 1;
}

.payment-btn:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.3);
}

a {
    text-decoration: none;
}

.materials-section {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}

.materials-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin: 0 0 15px 0;
}

.materials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.material-card {
    background: #f9fafb;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    transition: all 0.3s ease;
}

.material-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.material-icon {
    font-size: 28px;
    margin-bottom: 10px;
}

.material-name {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
    word-break: break-word;
}

.material-info {
    font-size: 12px;
    color: #666;
    margin-bottom: 10px;
}

.material-buttons {
    display: flex;
    gap: 8px;
}

.material-btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
}

.preview-btn-small {
    background: #17a2b8;
    color: white;
}

.preview-btn-small:hover {
    background: #138496;
}

.download-btn-small {
    background: #28a745;
    color: white;
}

.download-btn-small:hover {
    background: #218838;
}

.no-materials {
    text-align: center;
    padding: 30px;
    color: #666;
    background: #f9fafb;
    border-radius: 5px;
}

.purchase-required {
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, #fff3cd 0%, #ffe8b6 100%);
    border-radius: 5px;
    border-left: 5px solid #ffc107;
}

.purchase-required h4 {
    color: #856404;
    margin: 0 0 10px 0;
    font-size: 18px;
}

.purchase-required p {
    color: #856404;
    margin: 0;
    font-size: 14px;
}

.purchase-required button {
    background: #ffc107;
    color: #333;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 15px;
}

.purchase-required button:hover {
    background: #ffb300;
}

@media (max-width: 600px) {
    .course-details-grid {
        grid-template-columns: 1fr;
    }

    .action-buttons {
        flex-direction: column;
    }

    .course-title {
        font-size: 24px;
    }

    .discounted-price {
        font-size: 24px;
    }
}
</style>
</head>

<body>

<!-- APP BAR -->
<div class="appbar">
    <div class="appbar-left">
        <div class="icon">Account: <?= $_SESSION['user'] ?></div>
    </div>

    <div class="appbar-right">
        <a href="dashboard.php" class="icon">Browse Courses</a>
        <a href="my_courses.php" class="icon">My Courses</a>
        <a href="cart.php" class="icon">Cart</a>
        <a href="wishlist.php" class="icon">Wishlist</a>
        <a href="order_history.php" class="icon">Orders</a>
        <a href="auth.php?logout=1" class="icon">Logout</a>
    </div>
</div>

<!-- COURSE DETAILS -->
<div class="container">
    <a href="dashboard.php" class="back-link">Back to Courses</a>

    <div class="course-header">
        <h1 class="course-title"><?= htmlspecialchars($course['title']) ?></h1>

        <div class="course-meta">
            <div class="price-section">
                <?php if ($discountPercent > 0): ?>
                    <span class="original-price">₹<?= $course['price'] ?></span>
                    <span class="discounted-price">₹<?= $discountedPrice ?></span>
                    <span class="discount-badge">SAVE <?= $discountPercent ?>%</span>
                <?php else: ?>
                    <span class="discounted-price">₹<?= $course['price'] ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="deal-info">
            <?php if (!empty($course['deal'])): ?>
                <span class="deal-tag">Deal: <?= htmlspecialchars($course['deal']) ?></span>
            <?php endif; ?>
            
            <?php if (!empty($course['offer'])): ?>
                <span class="offer-tag">Offer: <?= htmlspecialchars($course['offer']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="course-content">
        <div class="course-section-title">Course Description</div>
        <div class="course-description">
            <?= nl2br(htmlspecialchars($course['description'])) ?>
        </div>

        <div class="course-details-grid">
            <div class="detail-item">
                <div class="detail-label">Original Price</div>
                <div class="detail-value">₹<?= $course['price'] ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Final Price</div>
                <div class="detail-value">₹<?= $discountedPrice ?></div>
            </div>

            <?php if (!empty($course['deal'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Current Deal</div>
                    <div class="detail-value"><?= htmlspecialchars($course['deal']) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($course['offer'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Bonus Offer</div>
                    <div class="detail-value"><?= htmlspecialchars($course['offer']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="action-buttons">
        <a href="cart.php?add=<?= $course['id'] ?>">
            <button class="add-to-cart-btn">Add to Cart</button>
        </a>

        <a href="wishlist.php?add=<?= $course['id'] ?>">
            <button class="wishlist-btn">Add to Wishlist</button>
        </a>
    </div>

    <!-- COURSE MATERIALS SECTION
    <div class="materials-section">
        <h3 class="materials-title">Course Materials</h3>

        <?php if ($userPurchased): ?>
            <!-- Materials for purchased courses 
            <?php if ($materials && $materials->num_rows > 0): ?>
                <div class="materials-grid">
                    <?php while ($material = $materials->fetch_assoc()): ?>
                        <?php
                            // Determine file icon based on type
                            $file_type = strtolower($material['file_type']);
                            $icon = '[File]';
                            
                            if ($file_type === 'pdf') $icon = '[PDF]';
                            elseif (in_array($file_type, ['doc', 'docx'])) $icon = '[DOC]';
                            elseif (in_array($file_type, ['xls', 'xlsx'])) $icon = '[XLSX]';
                            elseif (in_array($file_type, ['ppt', 'pptx'])) $icon = '[PPT]';
                            elseif (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) $icon = '[IMAGE]';
                            elseif (in_array($file_type, ['mp4', 'avi', 'mov'])) $icon = '[VIDEO]';
                            elseif (in_array($file_type, ['zip', 'rar'])) $icon = '[ARCHIVE]';
                            elseif ($file_type === 'txt') $icon = '[TEXT]';
                        ?>
                        <div class="material-card">
                            <div class="material-icon"><?= $icon ?></div>
                            <div class="material-name"><?= htmlspecialchars($material['title']) ?></div>
                            <div class="material-info">
                                <div><?= htmlspecialchars($material['file_name']) ?></div>
                                <div><?= round($material['file_size'] / 1024, 2) ?> KB</div>
                                <div><?= date('M d, Y', strtotime($material['uploaded_date'])) ?></div>
                            </div>
                            <div class="material-buttons">
                                <a href="preview_material.php?id=<?= $material['material_id'] ?>">
                                    <button class="material-btn preview-btn-small">Preview</button>
                                </a>
                                <a href="download_material.php?id=<?= $material['material_id'] ?>">
                                    <button class="material-btn download-btn-small">Download</button>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-materials">
                    <p>No course materials available yet.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
             Message for non-purchased courses 
            <div class="purchase-required">
                <h4>Purchase This Course to Access Materials</h4>
                <p>Course materials are exclusively available for enrolled students. Purchase this course to access all learning resources, documents, and files.</p>
                <a href="cart.php?add=<?= $course['id'] ?>">
                    <button>Add to Cart & Enroll</button>
                </a>
            </div>
        <?php endif; ?>
    </div> -->
</div>

</body>
</html>
