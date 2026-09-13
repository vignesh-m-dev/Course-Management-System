<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once 'db_helper.php';

$conn = new mysqli("localhost", "root", "", "23UCS105");
$user_id = $_SESSION['user_id'];

/* ADD TO CART */
if (isset($_GET['add'])) {
    $course_id = (int)$_GET['add'];

    $check = $conn->query("
        SELECT * FROM cart 
        WHERE user_id=$user_id AND course_id=$course_id
    ");

    if ($check->num_rows == 0) {
        $conn->query("
            INSERT INTO cart (user_id, course_id)
            VALUES ($user_id, $course_id)
        ");
    }
    header("Location: cart.php");
    exit;
}

/* REMOVE */
if (isset($_GET['remove'])) {
    $conn->query("
        DELETE FROM cart 
        WHERE user_id=$user_id AND course_id=".$_GET['remove']
    );
    header("Location: cart.php");
    exit;
}

/* FETCH CART */
$data = $conn->query("
    SELECT c.id, c.title, c.price, c.deal
    FROM cart ct
    JOIN courses c ON c.id = ct.course_id
    WHERE ct.user_id=$user_id
");

$total = 0;
$originalTotal = 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>My Cart</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f2f4f7;
    padding: 20px;
}

.cart-box {
    max-width: 750px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.cart-header h2 {
    margin: 0;
}

.back-link {
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin-bottom: 12px;
    border-radius: 8px;
    background: #f9fafb;
    border: 1px solid #e0e0e0;
}

.cart-title {
    font-weight: bold;
    font-size: 16px;
}

.cart-price {
    color: #28a745;
    font-weight: bold;
    margin-top: 5px;
}

.cart-original-price {
    font-size: 12px;
    color: #999;
    text-decoration: line-through;
}

.cart-discounted-price {
    font-size: 14px;
    color: #d35400;
    font-weight: bold;
    margin-left: 8px;
}

.original-total {
    font-size: 12px;
    color: #999;
    text-decoration: line-through;
}

.remove-link {
    text-decoration: none;
    color: white;
    background: #dc3545;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 14px;
}

.remove-link:hover {
    background: #b52a37;
}

.cart-total {
    margin-top: 25px;
    text-align: right;
}

.cart-total strong {
    font-size: 18px;
}

.pay-btn {
    margin-top: 10px;
    padding: 10px 18px;
    background: #007bff;
    border: none;
    color: white;
    font-size: 15px;
    border-radius: 6px;
    cursor: pointer;
}

.pay-btn:hover {
    background: #0056b3;
}

.empty {
    text-align: center;
    color: #777;
    margin-top: 40px;
}
</style>

</head>
<body>

<div class="cart-box">

    <div class="cart-header">
        <h2>My Cart</h2>
        <a href="dashboard.php" class="back-link">← Back</a>
    </div>

    <?php if ($data->num_rows > 0): ?>

        <?php while($row = $data->fetch_assoc()): ?>
            <?php 
                $discountedPrice = getDiscountedPrice($row['price'], $row['deal']);
                $total += $discountedPrice;
                $originalTotal += $row['price'];
            ?>

            <div class="cart-item">
                <div>
                    <div class="cart-title"><?= $row['title'] ?></div>
                    <div class="cart-price">
                        <?php if ($row['deal']): ?>
                            <span class="cart-original-price">₹<?= $row['price'] ?></span>
                            <span class="cart-discounted-price">₹<?= $discountedPrice ?></span>
                        <?php else: ?>
                            ₹<?= $row['price'] ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SAME REMOVE LOGIC -->
                <a href="?remove=<?= $row['id'] ?>" class="remove-link">
                    Remove
                </a>
            </div>

        <?php endwhile; ?>

        <div class="cart-total">
            <?php if ($originalTotal > $total): ?>
                <div class="original-total">Original Total: ₹<?= $originalTotal ?></div>
            <?php endif; ?>
            <strong>Total: ₹<?= $total ?></strong><br>
            <a href="payment.php">
                <button class="pay-btn">Proceed to Payment</button>
            </a>
        </div>

    <?php else: ?>
        <div class="empty">
            <h3>Your cart is empty 😔</h3>
            <a href="dashboard.php" class="back-link">Browse Courses</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
