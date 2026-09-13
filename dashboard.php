<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once 'db_helper.php';

$conn = new mysqli("localhost", "root", "", "23UCS105");

// Check if user is admin
$userResult = $conn->query("SELECT is_admin FROM users WHERE user_id = " . $_SESSION['user_id']);
$user = $userResult->fetch_assoc();
$isAdmin = $user && $user['is_admin'];

// Fetch all available courses
$courseData = $conn->query("SELECT * FROM courses ORDER BY id DESC");

// Build array of purchased course IDs
$purchasedIds = [];
$allCourses = [];
while($row = $courseData->fetch_assoc()) {
    $allCourses[] = $row;
}

// Get purchased course IDs
$tempResult = $conn->query("
    SELECT DISTINCT course_id 
    FROM orders
    WHERE user_id = " . $_SESSION['user_id']
);
while($row = $tempResult->fetch_assoc()) {
    $purchasedIds[] = $row['course_id'];
}

// Function to get material count for a course
function getMaterialCount($conn, $course_id) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'course_materials'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM course_materials WHERE course_id = $course_id");
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['count'];
        }
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"> 
<title>Dashboard</title>

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
    background: #007bff;
    color: white;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.appbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    flex: 1 1 auto;
}

.appbar-right {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}

.appbar input {
    padding: 7px;
    border-radius: 5px;
    border: none;
    width: 250px;
}

.icon {
    cursor: pointer;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

/* COURSE LIST */
.container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.course-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.course-card {
    background: white;
    padding: 18px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.course-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    transform: translateY(-5px);
}

.course-card.purchased {
    background: linear-gradient(135deg, #f0f8ff 0%, #e6f2ff 100%);
    border-left: 5px solid #28a745;
}

.course-card.purchased.pending-payment {
    background: linear-gradient(135deg, #fffbea 0%, #fff9e0 100%);
    border-left: 5px solid #ffc107;
}

.purchased-badge {
    display: inline-block;
    background: #28a745;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 8px;
    width: fit-content;
}

.material-badge {
    display: inline-block;
    background: #ffc107;
    color: #333;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 8px;
    margin-bottom: 8px;
    width: fit-content;
}

.payment-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 8px;
    margin-bottom: 8px;
    width: fit-content;
}

.payment-pending {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
}

.payment-paid {
    background: #d4edda;
    color: #155724;
    border: 1px solid #28a745;
}

@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
    100% {
        opacity: 1;
    }
}

.payment-pending {
    animation: pulse 2s infinite;
}

.course-title {
    font-size: 20px;
    font-weight: bold;
}

.course-desc {
    font-size: 14px;
    margin: 10px 0;
}

.price {
    font-size: 18px;
    font-weight: bold;
    color: green;
}

.original-price {
    font-size: 14px;
    color: #999;
    text-decoration: line-through;
    margin-right: 10px;
}

.discounted-price {
    font-size: 18px;
    font-weight: bold;
    color: #d35400;
}

.discount-badge {
    display: inline-block;
    background: #d35400;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 8px;
}

.buttons {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    flex-wrap: nowrap;
    width: 100%;
}

.buttons a {
    flex: 1;
    display: flex;
    text-decoration: none;
}

button {
    padding: 8px 15px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s ease;
    flex: 1;
    min-width: 0;
    width: 100%;
}

button:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

.wishlist-btn {
    background: #ff4d4d;
}

.deal {
    color: #d35400;
    font-weight: bold;
}

.offer {
    color: #8e44ad;
    font-weight: bold;
}

/* RESPONSIVE DESIGN */
@media (max-width: 1200px) {
    .course-list {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }
}

@media (max-width: 768px) {
    .appbar {
        flex-direction: column;
        gap: 12px;
    }

    .appbar-left,
    .appbar-right {
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
    }

    .appbar input {
        width: 100%;
        max-width: 250px;
    }

    .course-list {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }

    .course-card {
        padding: 15px;
    }

    .buttons {
        gap: 8px;
    }

    button {
        padding: 8px 10px;
        font-size: 12px;
        min-width: 80px;
    }

    h2 {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 10px;
    }

    .course-list {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .course-card {
        padding: 12px;
    }

    .course-title {
        font-size: 18px;
    }

    .price {
        font-size: 16px;
    }

    .discount-badge {
        font-size: 10px;
        padding: 3px 6px;
    }

    .buttons {
        flex-direction: column;
        gap: 8px;
    }

    button {
        width: 100%;
        padding: 10px;
        font-size: 13px;
    }

    .appbar-right {
        font-size: 12px;
    }

    .icon {
        font-size: 12px;
        padding: 5px 8px;
    }
}

/* MODAL STYLES */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 30px;
    border-radius: 10px;
    border: 1px solid #888;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover,
.close:focus {
    color: #000;
}

.modal-header {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 15px;
    color: #333;
}

.modal-body {
    font-size: 16px;
    color: #666;
    margin-bottom: 25px;
    line-height: 1.6;
}

.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.modal-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    transition: all 0.3s ease;
}

.modal-btn-delete {
    background: #dc3545;
    color: white;
}

.modal-btn-delete:hover {
    background: #c82333;
    transform: scale(1.02);
}

.modal-btn-cancel {
    background: #6c757d;
    color: white;
}

.modal-btn-cancel:hover {
    background: #5a6268;
    transform: scale(1.02);
}

@media (max-width: 480px) {
    .modal-content {
        width: 85%;
        margin: 30% auto;
    }

    .modal-footer {
        flex-direction: column;
    }

    .modal-btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<!-- APP BAR -->
<div class="appbar">
    <div class="appbar-left">
        <div class="icon">Account: <?= $_SESSION['user'] ?></div>
        <input type="text" placeholder="Search courses..." id="searchBox">
    </div>

    <div class="appbar-right">
        <a href="my_courses.php" class="icon">My Courses</a>
        <?php if ($isAdmin): ?>
            <a href="admin.php" class="icon">Admin Panel</a>
        <?php endif; ?>
        <a href="cart.php" class="icon">Cart</a>
        <a href="wishlist.php" class="icon">Wishlist</a>
        <a href="order_history.php" class="icon">Orders</a>
        <a href="auth.php?logout=1" class="icon">Logout</a>
    </div>
</div>

<!-- COURSE LIST -->
<div class="container">
    <!-- AVAILABLE COURSES SECTION -->
    <div>
        <h2 style="margin-top: 0; color: #007bff; border-bottom: 3px solid #007bff; padding-bottom: 10px;">Available Courses</h2>
        <div class="course-list" id="courseList">
        <?php foreach($allCourses as $row): ?>
            <?php 
                $discountedPrice = getDiscountedPrice($row['price'], $row['deal']);
                $discountPercent = getDiscountPercent($row['deal']);
                $isPurchased = in_array($row['id'], $purchasedIds);
                $materialCount = getMaterialCount($conn, $row['id']);
            ?>
            <?php if (!$isPurchased): ?>
                <div class="course-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <div class="course-title"><?= $row['title'] ?></div>
                        <?php if ($isAdmin): ?>
                            <div style="display: flex; gap: 10px;">
                                <a href="edit_course.php?id=<?= $row['id'] ?>" style="text-decoration: none; cursor: pointer; font-size: 12px; color: #007bff; font-weight: bold;">Edit</a>
                                <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title']) ?>')" style="background: none; border: none; font-size: 12px; color: #dc3545; cursor: pointer; padding: 0; font-weight: bold;">Delete</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="course-desc"><?= $row['description'] ?></div>

                    <div class="price">
                        <?php if ($discountPercent > 0): ?>
                            <span class="original-price">₹<?= $row['price'] ?></span>
                            <span class="discounted-price">₹<?= $discountedPrice ?></span>
                            <span class="discount-badge">SAVE <?= $discountPercent ?>%</span>
                        <?php else: ?>
                            <span class="discounted-price">₹<?= $row['price'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="buttons">
                        <a href="view_course.php?id=<?= $row['id'] ?>" style="flex: 1;">
                            <button style="width: 100%; background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">View Details</button>
                        </a>

                        <a href="wishlist.php?add=<?= $row['id'] ?>" style="flex: 1;">
                            <button style="width: 100%; background: #ff4d4d; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">Wishlist</button>
                        </a>

                        <a href="cart.php?add=<?= $row['id'] ?>" style="flex: 1;">
                            <button style="width: 100%; background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">Add to Cart</button>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.getElementById("searchBox").addEventListener("input", function() {
    let filter = this.value.toLowerCase();
    let availableCards = document.querySelectorAll("#courseList .course-card");

    availableCards.forEach(card => {
        let title = card.querySelector(".course-title").innerText.toLowerCase();
        let description = card.querySelector(".course-desc").innerText.toLowerCase();
        
        if (title.includes(filter) || description.includes(filter)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
});

// Confirmation Modal functionality
function confirmDelete(courseId, courseTitle) {
    const modal = document.getElementById('deleteModal');
    const courseNameSpan = document.getElementById('courseNameSpan');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const closeModal = document.querySelector('.close');

    courseNameSpan.textContent = courseTitle;
    modal.style.display = 'block';

    confirmDeleteBtn.onclick = function() {
        deleteCourse(courseId);
    };

    cancelDeleteBtn.onclick = function() {
        modal.style.display = 'none';
    };

    closeModal.onclick = function() {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
}

function deleteCourse(courseId) {
    const formData = new FormData();
    formData.append('course_id', courseId);

    fetch('delete_course.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('deleteModal').style.display = 'none';
            // Remove the course card from DOM
            const courseCard = document.querySelector(`button[onclick*="confirmDelete(${courseId}"]`).closest('.course-card');
            courseCard.remove();
            alert('Course deleted successfully');
        } else {
            alert('Error: ' + (data.message || 'Failed to delete course'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the course');
    });
}
</script>

<!-- DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">Delete Course</div>
        <div class="modal-body">
            Are you sure you want to delete the course <strong>"<span id="courseNameSpan"></span>"</strong>? This action cannot be undone.
        </div>
        <div class="modal-footer">
            <button id="confirmDeleteBtn" class="modal-btn modal-btn-delete">Delete</button>
            <button id="cancelDeleteBtn" class="modal-btn modal-btn-cancel">Cancel</button>
        </div>
    </div>
</div>

</body>
</html>
