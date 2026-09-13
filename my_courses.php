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

// Fetch purchased courses with payment status
$purchasedCourses = $conn->query("
    SELECT DISTINCT c.id, c.title, c.description, c.price, c.deal, c.offer, o.payment_status
    FROM courses c
    INNER JOIN orders o ON c.id = o.course_id
    WHERE o.user_id = " . $_SESSION['user_id'] . "
    ORDER BY o.order_date DESC
");

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
<title>My Courses</title>

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
    background: linear-gradient(135deg, #f0f8ff 0%, #e6f2ff 100%);
    border-left: 5px solid #28a745;
}

.course-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    transform: translateY(-5px);
}

.course-card.pending-payment {
    background: linear-gradient(135deg, #fffbea 0%, #fff9e0 100%);
    border-left: 5px solid #ffc107;
}

.material-badge {
    display: inline-block;
    background: #ffc107;
    color: #333;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 8px;
    width: fit-content;
}

.payment-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
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
    margin-top: 10px;
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
    flex-wrap: wrap;
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
    min-width: 100px;
}

button:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

.deal {
    color: #d35400;
    font-weight: bold;
}

.offer {
    color: #8e44ad;
    font-weight: bold;
}

.no-courses {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 10px;
    color: #666;
}

.no-courses a {
    color: #007bff;
    text-decoration: none;
}

.no-courses a:hover {
    text-decoration: underline;
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
        <a href="dashboard.php" class="icon">Browse Courses</a>
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
    <!-- MY COURSES SECTION -->
    <?php if ($purchasedCourses && $purchasedCourses->num_rows > 0): ?>
        <div>
            <h2 style="margin-top: 0; color: #28a745; border-bottom: 3px solid #28a745; padding-bottom: 10px;">My Purchased Courses</h2>
            <div class="course-list" id="courseList">
            <?php while($row = $purchasedCourses->fetch_assoc()): ?>
                <?php 
                    $discountedPrice = getDiscountedPrice($row['price'], $row['deal']);
                    $discountPercent = getDiscountPercent($row['deal']);
                    $materialCount = getMaterialCount($conn, $row['id']);
                    $paymentStatus = $row['payment_status'] ?? 'PAID';
                ?>
                <div class="course-card <?php echo $paymentStatus === 'PENDING' ? 'pending-payment' : ''; ?>">
                    
                    <?php if ($paymentStatus === 'PENDING'): ?>
        <span class="payment-status-badge payment-pending">Payment Pending</span>
                    <?php else: ?>
        <span class="payment-status-badge payment-paid">Payment Confirmed</span>
                    <?php endif; ?>
                    
                    <?php if ($materialCount > 0): ?>
        <span class="material-badge"><?= $materialCount ?> Material<?= $materialCount > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                    
                    <div class="course-title"><?= $row['title'] ?></div>
                    <div class="course-desc"><?= $row['description'] ?></div>

                    <?php if (!empty($row['deal'])): ?>
                        <div class="deal">Deal: <?= $row['deal'] ?></div>
                    <?php endif; ?>

                    <?php if (!empty($row['offer'])): ?>
                        <div class="offer">Offer: <?= $row['offer'] ?></div>
                    <?php endif; ?>

                    <div class="buttons">
                        <a href="view_course.php?id=<?= $row['id'] ?>">
                            <button style="background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">View Details</button>
                        </a>
                        
                        <?php if ($paymentStatus === 'PENDING'): ?>
                            <a href="payment.php?course_id=<?= $row['id'] ?>">
                                <button style="background: #ffc107; color: #333; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Complete Payment</button>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="no-courses">
            <h3>You haven't purchased any courses yet.</h3>
            <p>Explore our <a href="dashboard.php">available courses</a> and start learning today!</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById("searchBox").addEventListener("input", function() {
    let filter = this.value.toLowerCase();
    let cards = document.querySelectorAll("#courseList .course-card");

    cards.forEach(card => {
        let title = card.querySelector(".course-title").innerText.toLowerCase();
        let description = card.querySelector(".course-desc").innerText.toLowerCase();
        
        if (title.includes(filter) || description.includes(filter)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
});
</script>

</body>
</html>
